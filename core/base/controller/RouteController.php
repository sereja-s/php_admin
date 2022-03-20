<?php

namespace core\base\controller;

use core\base\exceptions\RouteException;
use core\base\settings\Settings;

// точка входа в нашу систему контроллеров
class RouteController extends BaseController
{

	use Singleton;

	// свойство маршруты
	protected $routes;

	// конструктор класса
	private function __construct()
	{
		// получим адресную строку (сохраняем ячейку 'REQUEST_URI' суперглобального массива СЕРВЕР)
		// в этой ячейке хранится: / и весь дальнеший адрес
		$adress_str = $_SERVER['REQUEST_URI'];

		if ($_SERVER['QUERY_STRING']) {
			$adress_str = substr($adress_str, 0, strpos($adress_str, $_SERVER['QUERY_STRING']) - 1);
		}

		// В переменную $path сохраним обрезанную строку (без знака /), в которой содержится имя выполнения скрипта
		// (в ячейке PHP_SELF глобального массива $_SERVER)

		// функция php: substr() возвращает подстроку строки Здесь-  $_SERVER['PHP_SELF'], начинающейся с (здесь- 0) символа по счету и длиной strrpos($_SERVER['PHP_SELF'], 'index.php') символов.
		// (здесь- функция php: strrpos() Возвращает номер позиции последнего вхождения index.php относительно начала строки $_SERVER['PHP_SELF'])
		$path = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], 'index.php'));

		// условие: если переменная $path равна константе PATH (описана в файле: config.php)
		if ($path === PATH) {

			// сделаем проверку: есть ли в конце адресной строки знак / 
			// при этом знак / сразу после домена необходим (ставится системой автоматически и указыввает на корень сайта) 
			if (
				// функция php: strrpos() ищет последнее вхождение подстроки (здесь- /) в строке (здесь- $adress_str) и возвращает номер позиции
				// функция php: strlen() показвает длину строки (массива символов (здесь- $adress_str)) 
				// т.к. у массива нумерация начинается с 0, а длина строки меряется с 1, учтём это и запишем -1 
				strrpos($adress_str, '/') === strlen($adress_str) - 1 &&
				// если выполнится первое условие, мы должны знать, что это не корень сайта (там знак / стоит всегда по умолчанию)
				strrpos($adress_str, '/') !== strlen(PATH) - 1
			) {

				// Направим пользователя на страницу по ссылке без этого символа (/) с помощью функции php: redirect()
				// 1-ым параметром будет функция php: rtrim(), которая обрезает концевые пробелы в начале и конце строки, 
				// а также символы в конце строки, которые указаны в качестве второго не обязательного параметра на входе (здесь- /)
				// 2-ым параметром функции php: redirect() укажем: 301- код ответа сервера (будет отправлен браузеру)
				$this->redirect(rtrim($adress_str, '/'), 301);
			}

			// в свойстве routes сохраним маршруты (обратились к классу Settings и его статическому методу get())
			$this->routes = Settings::get('routes');

			// проверка : описаны ли маршруты (если нет- получим сообщение)
			if (!$this->routes) {
				throw new RouteException('Отсутствуют маршруты в базовых настройках', 1);
			}

			// создаём переменную в которой преобразуем в массив адресную строку и разбираем по разделителю (здесь- /)
			// функция php: explode()- перобразует строку (2-ой параметр) в массив по заданному разделителю (1-ый параметр)
			// у нас адресная строка будет возвращена, сразу начиная 1-го символа, после знака / функцией substr() и только затем преобазована
			$url = explode('/', substr($adress_str, strlen(PATH)));

			// проверим не в админку ли хочет попасть пользователь
			// если запрос в админку
			if ($url[0] && $url[0] === $this->routes['admin']['alias']) {

				array_shift($url);

				if ($url[0] && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . $this->routes['plugins']['path'] . $url[0])) {
					$plugin = array_shift($url);
					$pluginSettings = $this->routes['settings']['path'] . ucfirst($plugin . 'Settings');

					if (file_exists($_SERVER['DOCUMENT_ROOT'] . PATH . $pluginSettings . '.php')) {
						$pluginSettings = str_replace('/', '\\', $pluginSettings);
						$this->routes = $pluginSettings::get('routes');
					}

					$dir = $this->routes['plugins']['dir'] ? '/' . $this->routes['plugins']['dir'] . '/' : '/';
					$dir = str_replace('//', '/', $dir);

					$this->controller = $this->routes['plugins']['path'] . $plugin . $dir;

					$hrUrl = $this->routes['plugins']['hrUrl'];
					$route = 'plugins';
				} else {
					$this->controller = $this->routes['admin']['path'];
					$hrUrl = $this->routes['admin']['hrUrl'];
					$route = 'admin';
				}
			} else {

				// В переменную сохраним то что находится в переменной $routes (ячейке user, в его ячейке hrUrl) 
				// (чтобы система понимала работать ей с ЧПУ или нет)
				$hrUrl = $this->routes['user']['hrUrl'];

				// определим откуда подключать контроллер (здесь укажем базовый маршрут)
				$this->controller = $this->routes['user']['path'];

				// укажем для кого создаём маршрут
				$route = 'user';
			}

			// вызовем метод, который будет создавать маршрут Передаём: 1- маршрут который надо создать (описание) и 
			// 2- массив из которого маршрут будет создан
			$this->createRoute($route, $url);

			if ($url[1]) {
				$count = count($url);
				$key = '';

				if (!$hrUrl) {
					$i = 1;
				} else {
					$this->parameters['alias'] = $url[1];
					$i = 2;
				}

				for (; $i < $count; $i++) {
					if (!$key) {
						$key = $url[$i];
						$this->parameters[$key] = '';
					} else {
						$this->parameters[$key] = $url[$i];
						$key = '';
					}
				}
			}
		} else {
			throw new RouteException('Не корректная директория сайта', 1);
		}
	}

	// метод, который будет создавать маршрут
	// где $var- маршрут который надо создать (описание), $arr- массив из которого маршрут будет создан
	private function createRoute($var, $arr)
	{
		// определили массив явно (показывая, что у нас может быть этот массив)
		$route = [];

		// если не пуст нулевой элемент массива
		if (!empty($arr[0])) {

			// проверка: существует ли для ячейки $var алиас маршрутов
			// если существует, то подключить контроллеры и методы согласно алиаса маршрутов
			if ($this->routes[$var]['routes'][$arr[0]]) {
				// разберём маршрут по разделителю /
				$route = explode('/', $this->routes[$var]['routes'][$arr[0]]);

				// к уже указанному выше базовому маршруту добавим название контроллера 
				// (предварительно преобразовав первую букву в заглавную с помощью ф-ции php: ucfirst() и там же добавив слово Controller)
				$this->controller .= ucfirst($route[0] . 'Controller');
			} else {
				//иначе
				// если не существует для ячейки $var алиас маршрутов
				$this->controller .= ucfirst($arr[0] . 'Controller');
			}
		} else {
			//иначе
			// если пуст нулевой элемент массива (запрашиается корень сайта), подключаем контроллер по умолчанию (здесь- IndexController)
			$this->controller .= $this->routes['default']['controller'];
		}

		// Определим какие подключатся методы 

		// условие: если у нас прописаны методы, необходмые для алиасов 
		// в файле Settings.php в массиве private $routes, в соответствующей ячейке маршрута 'routes' => [] (здесь этот массив объявлен как $route = [] т.е. в нём что-то есть;)
		// (при этом 1-я ячейка будет с входным методом, выходной метод будет занимать 2-ю ячейку)
		// тогда они подключаются,
		// иначе (если у нас не прописаны такие методы в файле Settings.php), 
		// подключатся методы по умолчанию: inputMethod и outputMethod
		$this->inputMethod = $route[1] ? $route[1] : $this->routes['default']['inputMethod'];
		$this->outputMethod = $route[2] ? $route[2] : $this->routes['default']['outputMethod'];

		return;
	}
}
