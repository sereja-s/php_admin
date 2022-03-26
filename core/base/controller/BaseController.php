<?php

namespace core\base\controller;

use core\base\exceptions\RouteException;
use core\base\model\UserModel;
use core\base\settings\Settings;

// абстрактный класс (нельзя создавать объекты класса, можно только наследовать)
// будет вытаскивать данные ( формировать запросы к моделям), подключать виды и др.

abstract class BaseController
{
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
			// Используем расширение PHP: Reflection и его класс: ReflectionMethod (класс отвечающий за проверку и работу с методом)\
			// (при создании объекта класса ReflectionMethod, он на стадии конмтруктора осуществляет базовый поиск метода (здесь- request) в указанном классе (здесь имя класса- $controller))
			// (кроме того что мы находим метод (здесь- request), также при помощи объекта класса ReflectionMethod. который у нас создаётся в пременной (здесь- $object) можем вызвыать найденный метод)

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
	public function request($args)
	{
		// примем массив аргументов, чтобы с этими параметрами можно было работать в методах класса, объект которого (здесь- new $controller) был создан
		$this->parameters = $args['parameters'];

		// в переменные поместим то что хранится в ячейках массива $args: имя входного метода 'inputMethod' (он будет 
		// формировать параметр запроса модели, которая будет работать с базой данных (её таблицами), будет обрабатывать полученные от модели данные) 
		// и имя выходного метода 'outputMethod' (решает вопросы с подключением вида)
		$inputData = $args['inputMethod'];
		$outputData = $args['outputMethod'];

		// вызовем входно метод inputData(), который в качестве строки хранится в переменной $inputData
		// (этот метод заполнит какие то свойства, что то вытащит из базы данных, проведёт некие преобразования, вычисления, может подключить иные методы и др.)
		$data = $this->$inputData();

		if (method_exists($this, $outputData)) {

			// выходной меод outputData() соберёт шаблоны (шаблонизирует все те данные , которые собрал входной метод inputData())
			// и вернёт их в переменную $page
			$page = $this->$outputData($data);
			if ($page) {
				$this->page = $page;
			}
		} elseif ($data) {
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

	// вы зовем метод render(), который будет собирать страницу (метод шаблонизатор)
	// (на вход передаём два не обязательных параметра (могут быть пустыми): 1-ый- путь по которому искать шаблон, который надо подключить
	// и 2-ой- массив параметров (данных), который в этот шаблон необходимо передать)
	protected function render($path = '', $parameters = [])
	{
		extract($parameters);

		if (!$path) {
			$class = new \ReflectionClass($this);
			$space = str_replace('\\', '/', $class->getNamespaceName() . '\\');
			$routes = Settings::get('routes');

			if ($space === $routes['user']['path']) {
				$template = TEMPLATE;
			} else {
				$template = ADMIN_TEMPLATE;
			}

			$path = $template . $this->getController();
		}

		ob_start();
		if (!@include_once $path . '.php') {
			throw new RouteException('Отсутствует шаблон: ' . $path);
		}
		return ob_get_clean();
	}

	protected function getPage()
	{
		if (is_array($this->page)) {
			foreach ($this->page as $block) {
				echo $block;
			}
		} else {
			echo $this->page;
		}
		exit();
	}

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
