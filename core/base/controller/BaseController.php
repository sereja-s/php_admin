<?php

namespace core\base\controller;

use core\base\exceptions\RouteException;
use core\base\model\UserModel;
use core\base\settings\Settings;

// абстрактный класс (нельзя создавать объекты класса, можно только наследовать)
// будет вытаскивать данные ( формировать запросы к моделям), подключать виды и др.

abstract class BaseController
{
	// подключим трейт (trait)
	use \core\base\controller\BaseMethods;

	// в переменной $page будем хранить страницу сайта (Шапка, подвал, сайдбар и др.)
	protected $page;

	protected $header;
	protected $content;
	protected $footer;

	// свойство для хранения ошибок
	protected $errors;

	protected $controller;
	protected $inputMethod; // свойство в котором будет храниться метод, который будет собирать данные из базы данных
	protected $outputMethod; //свойство в котором будет храниться имя метода, который будет отвечать за подключение видов
	protected $parameters;

	protected $template;
	protected $styles;
	protected $scripts;

	protected $userId;
	protected $data;

	protected $ajaxData;

	public function route()
	{
		// в свойстве $this->controller (здесь будет подано правильное имя класса) меняем знак / на \ (спец. символ экранируем)
		$controller = str_replace('/', '\\', $this->controller);

		try {
			// Используем расширение PHP: Reflection и его класс: ReflectionMethod (класс отвечающий за проверку и работу с методом)

			// (при создании объекта класса ReflectionMethod, он на стадии конмтруктора осуществляет базовый поиск метода 
			// (здесь- request) в указанном классе (здесь имя класса- $controller))
			// (кроме того что мы находим метод (здесь- request), также при помощи объекта класса ReflectionMethod. который у нас 
			// создаётся в пременной (здесь- $object) можем вызвыать найденный метод)

			// в переменной $object мы сохраним объект класса ReflectionMethod (конструктор этого класса на вход принимает:
			// 1-ым параметром: имя класса в строковом виде (здесь- $controller) или объект класса) 
			// и 2-ым параметром: имя метода, который мы ищем в этом классе (здесь- request)			
			$object = new \ReflectionMethod($controller, 'request');

			// создадим массив аргументов, при помощи которого передадим методу request() свойства: parameters, inputMethod, outputMethod (объявеленных ранеее)
			$args = [
				'parameters' => $this->parameters,
				'inputMethod' => $this->inputMethod,
				'outputMethod' => $this->outputMethod
			];

			// Вызовем метод request() на исполнение (заполнит массив с параметрами)
			// (вызовом методов в (классе ReflectionMethod) занимается метод invoke())

			// Метод invoke() 1-ым параметром принимает: объект класса (здесь- new $controller), у которого необходимо вызвать
			// метод указанный в конструкторе (здесь- request) при создании объекта класса ReflectionMethod и 2-ым необязательным 
			// параметром может принимать: переменное число аргументов
			$object->invoke(new $controller, $args);

			// перехватываем исключение класса \ReflectionException 
			// (в $e должен прийти объект класса \ReflectionException- (сообщение об ошибке))
		} catch (\ReflectionException $e) {
			// метод getMessage() находится в родительском классе Exception и получает сообзение об ошибке, 
			// которое было выброшено через throw (здесь- в файле internal_settings.php)
			throw new RouteException($e->getMessage());
		}
	}

	// в результате вызовется метод request() и из массива аргументов $args заполнится св-во $this->parameters, которое уже будет доступно (например в IndexController)
	// метод request() будет также подключать другие методы
	public function request($args)
	{
		// примем массив аргументов, чтобы с этими параметрами можно было работать в методах класса, объект которого (здесь- new $controller) был создан
		$this->parameters = $args['parameters'];

		// в переменные поместим то что хранится в ячейках массива $args: имя входного метода 'inputMethod' (он будет 
		// формировать параметр запроса модели, которая будет работать с базой данных (её таблицами), будет обрабатывать полученные от модели данные) 
		// и имя выходного метода 'outputMethod' (решает вопросы с подключением вида)
		$inputData = $args['inputMethod'];
		$outputData = $args['outputMethod'];

		// вызовем входной метод $inputData() для сбора данных, который в качестве строки хранится в переменной $inputData
		// (этот метод заполнит какие то свойства, что то вытащит из базы данных, проведёт некие преобразования, вычисления, может подключить иные методы и др.)
		// в переменную $data сохраним результат работы ф-ии $inputData()
		$data = $this->$inputData();

		// Учтём ситуацию, когда нам не нужно, будет использовать второй метод (здесь- $this->$outputData($data)) 
		// например генерируем 404 страницу (выведем только сообщение и не нужно подключать header и footer)

		// функция php method_exists()— Проверяет, существует ли метод в данном классе
		// проверим существует ли метод (переданный в качестве строки 2-ым параметром (здесь- outputData())), в классе объекта (здесь- $this), переданного 1-ым параметром
		// $this- ссылается на объект класса из класса которого функция request() была вызвана
		if (method_exists($this, $outputData)) {

			// выходной меод $outputData() возвращает собранные данные (здесь- полученные в переменной $data, поданной а вход) 
			// другим методам т.е. соберёт шаблоны (шаблонизирует все те данные, которые собрал входной метод inputData())
			// и вернёт их в переменную $page
			$page = $this->$outputData($data);
			// если отработали оба метода $inputData() и $outputData()
			if ($page) {
				// то в переменную $this->page вернём готовую страницу
				$this->page = $page;
			}
			// если мы отработали только с одним методом (здесь- $this->$inputData()) и в переменную дату что то уже вернули
		} elseif ($data) {
			// то в переменную $this->page вернём, только то что пришло
			$this->page = $data;
		}

		// если в процессе выполнения методов возникли ошибки, которые надо залогировать (т.е. если в переменной $this->errors что то есть)
		if ($this->errors) {
			// обратимся к методу writeLog(), в который передадим свойство $this->errors
			$this->writeLog($this->errors);
		}

		// вызовем метод, который покажет то, что нам нужно
		$this->getPage();
	}

	// вызовем метод render(), который будет собирать страницу (метод ШАБЛОНИЗАТОР)

	// (на вход передаём два не обязательных параметра (могут быть пустыми): 1-ый- путь по которому искать шаблон, который надо подключить
	// и 2-ой- массив параметров (данных), который в этот шаблон необходимо передать)
	protected function render($path = '', $parameters = [])
	{
		// разберём массив параметров, поданных на вход используя ф-ию php: extract(), которая в памяти текущей символьной 
		// таблицы создаёт переменные (вида: ключ => значение) из массива, поданного на вход (здесь- $parameters) в области
		// видимости функции render() т.е.доступные только внутри этой функции
		extract($parameters);

		// если путь не пришёл, то
		if (!$path) {
			// сохраним в переменной объект класса ReflectionClass(), который предоставляет информацию о классе
			// в параметры (на вход) передадим ключевое слово $this (ссылку на объект класса, класс которого мы хотим исследовать)
			// т.е. в $this хранится объект класса, из которого вызвали
			$class = new \ReflectionClass($this);

			// Мы должны получить пространство имён для класса, указателем на обеъкт которого является ключевое слово: $this
			// (для этого используем расширение php: Reflection и его ф-ию: getNamespaceName(), обращаясь через объект класса: $class)
			// (здесь запись $class->getNamespaceName() вернёт: core\user\controller (для класса IndexController))
			// в переменную $space охраним результат работы ф-ии php: str_replace() которая заменяет все вхождения строки поиска 
			// на строку замены (здесь ищем \, меняем на /, ищем в пространстве имён класса (здесь- IndexController) в конце 
			// конкатенируем \) при этом символ \ экранируем символом \						
			$space = str_replace('\\', '/', $class->getNamespaceName() . '\\');
			// в переменной $routes сохраним полученное в классе Settings свойство routes
			$routes = Settings::get('routes');

			// проверим равно ли то что получили в пременную $space пути к пользовательской части
			if ($space === $routes['user']['path']) {
				// то подключаем путь к польховательскому шаблону (по умолчанию)
				$template = TEMPLATE;
			} else {
				$template = ADMIN_TEMPLATE;
			}

			$path = $template . $this->getController();
		}

		// исользуем функцию PHP, которая открывает текущий буфер обмена
		// (пока активна буферизация вывода, скрипт не отправляет вывод (кроме заголовков), вместо этого вывод сохраняется во внутреннем буфере)
		ob_start();
		// после того как мы вывели данные в буфер обмена, надо их объединить с переменными здесь- ($parameters)
		// необходимо подключить шаблон

		// условие: если не подключили файл с указанным расширением (здесь- $path . '.php'), т.е. шаблона нет
		// (оператор include_once включает и оценивает указанный файл во время выполнения скрипта и если код из файла уже был включен, он не будет включен снова) с указанным расширением
		// знак @ заглушит возможные ошибки (если файл не будет найден)
		if (!@include_once $path . '.php') {
			// будет выброшено(сгенерирвано) исключение
			throw new RouteException('Отсутствует шаблон: ' . $path);
		}

		// если файл шаблона есть, получим данные которые залетели в буфер обмена, там подключится шаблон (при его подключении обращаемся к переменным (внутри шаблона они будут доступны))
		// далее мы должны вернуть данные из буфера обмена и закрыть (удалить) его
		return ob_get_clean();
	}

	// метод, который покажет всё (выведет на экран результат)
	protected function getPage()
	{
		// проверяем: если на вход функции php: is_array подан массив
		if (is_array($this->page)) {
			// то пройдёмся в цикле по свойству $this->page и выведем последовательно (здесь- в переменной $block), то что хранится в каждом элементе его массива
			foreach ($this->page as $block) {
				echo $block;
			}
			// иначе если это не массив, а строка
		} else {
			echo $this->page;
		}
		exit();
	}

	// метод init() будет инициализировать стили и скрипты указанные в сore>base>settings>internal_settings.php в константах 
	// (для административной и пользовательской части)
	// в качестве не обязательного парметра на вход передадим переменную $admin и поставим её в значение false
	protected function init($admin = false)
	{
		if (!$admin) {
			if (USER_CSS_JS['styles']) {
				foreach (USER_CSS_JS['styles'] as $item) {
					$this->styles[] = (!preg_match('/^\s*https?:\/\//i', $item) ? PATH . TEMPLATE : '') . trim($item, '/');
				}
			}

			if (USER_CSS_JS['scripts']) {
				foreach (USER_CSS_JS['scripts'] as $item) {
					$this->scripts[] = (!preg_match('/^\s*https?:\/\//i', $item) ? PATH . TEMPLATE : '') . trim($item, '/');
				}
			}
		} else {
			if (ADMIN_CSS_JS['styles']) {
				foreach (ADMIN_CSS_JS['styles'] as $item) {
					$this->styles[] = (!preg_match('/^\s*https?:\/\//i', $item) ? PATH . ADMIN_TEMPLATE : '') . trim($item, '/');
				}
			}
			if (ADMIN_CSS_JS['scripts']) {
				foreach (ADMIN_CSS_JS['scripts'] as $item) {
					$this->scripts[] = (!preg_match('/^\s*https?:\/\//i', $item) ? PATH . ADMIN_TEMPLATE : '') . trim($item, '/');
				}
			}
		}
	}

	protected function checkAuth($type = false)
	{
		if (!($this->userId = UserModel::instance()->checkUser(false, $type))) {
			$type && $this->redirect(PATH);
		}

		if (property_exists($this, 'userModel')) {
			$this->userModel = UserModel::instance();
		}
	}
}
