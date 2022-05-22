<?php

namespace core\base\settings;

use core\base\controller\Singleton;

// класс настроек
class Settings
{
	use Singleton; // используем шаблон, запрещающий создавать более одного объекта класса

	// спецификатор доступа private даёт возможность читать, но запрещает перезаписывать это свойство в коде (т.к. это глобальные настройки проекта) 

	// запишем(сохраним) в свойствах массивы

	private $routes = [

		// запишем(сохраним) маршруты в виде массивов
		// для административной части сайта
		'admin' => [
			// перечислим ячейки массива
			'alias' => 'admin',
			'path' => 'core/admin/controller/',
			'hrUrl' => false, // для админки отключили человеко-понятные адреса ссылок
			'routes' => []
		],
		// для настройек сайта
		'settings' => [
			'path' => 'core/base/settings/'
		],
		// для плагинов
		'plugins' => [
			'path' => 'core/plugins/',
			'hrUrl' => false,
			'dir' => false
		],
		// для пользовательской части сайта
		'user' => [
			'path' => 'core/user/controller/',
			'hrUrl' => true, // для пользовательской части включили человеко-понятные адреса ссылок
			'routes' => []
		],
		// для раздела по умолчанию
		'default' => [
			'controller' => 'IndexController',
			// метод по умолчанию, который вызовется у контроллера
			'inputMethod' => 'inputData',
			// метод по умолчанию для вывода данных в пользовательскую часть
			'outputMethod' => 'outputData'
		]
	];

	// свойство: расширение (путь к папке где хранятся расширения) 
	private $expansion = 'core/admin/expansion/';

	private $messages = 'core/base/messages/';

	// таблица по умолчанию
	private $defaultTable = 'goods';

	private $formTemplates = PATH . 'core/admin/view/include/form_templates/';

	private $projectTables = [
		'catalog' => ['name' => 'Каталог'],
		'goods' => ['name' => 'Товары', 'img' => 'pages.png'],
		'filters' => ['name' => 'Фильтры', 'img' => 'pages.png'],
		'articles' => ['name' => 'Статьи'],
		'information' => ['name' => 'Информация'],
		'socials' => ['name' => 'Социальные сети'],
		'settings' => ['name' => 'Настройки системы']
	];

	// свойство массив шаблонов
	private $templateArr = [

		// массив вида: 'название шаблона' => массив с полями для которых должен быть подключен соответствующий шаблон

		'text' => ['name', 'phone', 'email', 'alias', 'external_alias'],
		'textarea' => ['keywords', 'content', 'address', 'description', 'address'],
		'radio' => ['visible', 'show_top_menu'],
		'checkboxlist' => ['filters'],
		'select' => ['menu_position', 'parent_id'],
		'img' => ['img', 'main_img'],
		'gallery_img' => ['gallery_img', 'new_gallery_img']
	];

	// св-во, позволяющее переводить поля административной панели из файла настроек
	private $translate = [
		// каждое поле тоже представляет собой массив, в котором можно указать два элемента (название элемента, комментарий)
		'name' => ['Название', 'Не более 100 символов'],
		'keywords' => ['Ключевые слова', 'Не более 70 символов'],
		'content' => ['Описание'],
		'description' => ['SEO описание'],
		'phone' => ['Телефон'],
		'email' => ['Электронная почта'],
		'address' => ['Адрес'],
		'alias' => ['Ссылка ЧПУ'],
		'external_alias' => ['Внешняя ссылка'],
		'img' => ['Изображение'],
		'visible' => ['Видимость'],
		'menu_position' => ['Позиция в списке'],
		'show_top_menu' => ['Показывать в верхнем меню']
	];

	// св-во, в котором будет храниться массив шаблонов в которых выводятся файлы
	private $fileTemplates = ['img', 'gallery_img'];

	// св-во, в котором будут храниться значения для input type radio (кнопок переключателей (да, нет и т.д.))
	private $radio = [
		'visible' => ['НЕТ', 'ДА', 'default' => 'ДА'],
		'show_top_menu' => ['НЕТ', 'ДА', 'default' => 'ДА']
	];

	// св-во, в котором будет храниться информация о корневых таблицах
	private $rootItems = [
		'name' => 'Корневая',
		'tables' => ['articles', 'filters', 'catalog']
	];

	private $manyToMany = [
		// массив содержит название таблиц, которые связаны
		'goods_filters' => ['goods', 'filters'] // 'type' => 'child' || 'root'
	];

	// св-во, в котором будут храниться блоки
	private $blockNeedle = [
		'vg-rows' => [],
		'vg-img' => ['img', 'main_img'],
		'vg-content' => ['content']
	];

	// свойство, в котором будет храниться массив полей, которые мы будем валидировать
	private $validation = [
		'name' => ['empty' => true, 'trim' => true],
		'price' => ['int' => true],
		'login' => ['empty' => true, 'trim' => true],
		'password' => ['crypt' => true, 'empty' => true],
		'keywords' => ['count' => 70, 'trim' => true],
		'description' => ['count' => 160, 'trim' => true]
	];

	// Объявим метод, который будет возвращать указанные выше свойства
	// на вход этому методу (в параметры) мы передаём свойство, которое мы хотим получить
	static public function get($property)
	{	// метод get() обращается к методу instance() данного класса (этот метод возвращает свойство в которое будет записана 
		// (сохранена) ссылка на объект данного класса) и мы можем обратиться к этому объекту и к его свойству, имя которого пришло на вход функции get()
		return self::instance()->$property;
	}

	// определим функцию (метод), которая будет клеять (объединять) свойства, например из массива шаблонов и свойства из массива 
	// плагина, если такие ему понадобятся (чтобы нам не приходилось дублировать свойства вручную)7
	// (в параметры приходит класс с которым мы работаем)
	public function clueProperties($class)
	{
		// определим массив свойств, которые будут возвращаться (например ShopSettings и тогда мы можем вызвать статический метод get(),который нам вернёт то или иное свойство)
		$baseProperties = [];

		// так как перед вызовом функции clueProperties() в классе ShopSettings в файле BaseSettings.php, был создан объект данного класса, 
		// в этой функции теперь доступно использование ключевого слова $this которая ссылается на объект нашего класса
		// пройдёмся в цикле по объекту нашего класса $this как имя(название) свойства $name и значения свойства $item
		foreach ($this as $name => $item) {
			// на каждой итерации цикла в переменную $property сохраним свойства класса, который мы передали в параметры функции 
			// (указываем переданный класс и вызываем у него метод get() на вход которого передаём имя свойства, которое у нас пришло в 
			// качестве имени свойства $name)
			$property = $class::get($name);

			// поверка условия: функция is_array() проверяет: является ли массивом то, что приходит ей на вход 
			// (т.е. являются ли свойство private $templateArr из файла Settings.php и свойство private $templateArr ($property) из 
			// файла ShopSettings.php ($item) массивами)
			if (is_array($property) && is_array($item)) {
				// то нам нужно их клеить (соединять)
				$baseProperties[$name] = $this->arrayMergeRecursive($this->$name, $property);
				// уходим на следующую итерацию цикла
				continue;
			}
			// проверка: если пришло не описанные свойства, а что то иное из свойств основного объекта настроек
			if (!$property) {
				// берём наш результирующий массив $baseProperties и в его ячейку $name запишем, то что находится в свойстве основного 
				// объекта настроек
				$baseProperties[$name] = $this->$name;
			}
		}
		// вернём наш результирующий массив $baseProperties
		return $baseProperties;
	}

	// объявим функцию которая будет склеивать массивы свойств 
	// (в параметры(на вход) ей ничего не передаём, т.к. аргументы (здесь- массивы свойств) передавались на вход при вызове функции 
	// arrayMergeRecursive() и уже попали в память) Из памяти их вытащит функция php: func_get_args()
	public function arrayMergeRecursive()
	{
		// объявим переменную в которую сохраним результат работы функции php: func_get_args(), т.е. получим аргументы функции
		$arrays = func_get_args();

		// в переменную $base сохраним результат работы функции php: array_shift(), которая возвращает первый элемент массива поданного на вход(здесь- массив $namе) 
		// и при этом удаляет его из $arrays (здесь- останется только второй элемент массива: массив $property)  
		$base = array_shift($arrays);

		// в цикле мы должны пройтись по массиву $arrays и забирать из него оставшийся массив $array
		foreach ($arrays as $array) {
			// в цикле мы должны пройтись по массиву $array как ключ(имя) $key и значение $value
			foreach ($array as $key => $value) {
				// поверка условия: функция is_array() проверяет: является ли массивом то, что приходит ей на вход, т.е. является ли 
				// массивами $value и $base (здесь нам нужен его конкретный элемент с таким же именем ($key) как и у массива $array)
				// (т.е. если это одинаковые свойства)
				if (is_array($value) && is_array($base[$key])) {
					// то ничего не будем делать, а рекурсивно вызовем наш метод arrayMergeRecursive()
					// обратимся к массиву $base и его ячейке $key и сохраняем результат работы функции arrayMergeRecursive(), на вход 
					// которой подаём: массив $base и его ячейке $key. а также значение $value
					$base[$key] = $this->arrayMergeRecursive($base[$key], $value);
				} else {
					// проверка: нумерованный ли массив ()
					//  функция php: is_int() проверяет целое ли число сюда пришло или пытается строку вида (например '1') привести к целочисленному типу если да:
					if (is_int($key)) {
						// то выполним ещё одну проверку: если не существует такой элемент в массиве
						// первым аргументом мы передаём значение, которое мы ищем ($value), а вторым- массив в котором осуществлем поиск ($base)
						if (!in_array($value, $base)) {
							// если такого значения ($value) не существует. то мы закинем его в проверяемый массив ($base)
							array_push($base, $value);
						}
						// уходим на следующую итерацию цикла
						continue;
					}
					// если ключ числовой, а не строковый, то мы его дожны переопределить, а не просто добавить этот элемент
					// перезапишем в массиве $base его ячейку $key значением $value;
					$base[$key] = $value;
				}
			}
		}
		// возвращаем массив $base
		return $base;
	}
}
