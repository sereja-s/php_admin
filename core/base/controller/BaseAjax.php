<?php

namespace core\base\controller;

use core\base\settings\Settings;

// класс распределит запросы учитывая административная это панель или пользовательская часть сайта 
class BaseAjax extends BaseController
{
	// метод определит какой контроллер подключать
	public function route()
	{
		$route = Settings::get('routes');
		$controller = $route['user']['path'] . 'AjaxController';
		// данные могут прийти из js могут прийти ПОСТом или ГЕТом
		// сделаем проверку каким способои данные пришли
		$data = $this->isPost() ? $_POST : $_GET;

		// сделаем проверку и сгенерируем уникальный токен (применим на странице: login.php для защиты от роботизированного подбора пароля)
		if (!empty($data['ajax']) && $data['ajax'] === 'token') {
			return $this->generateToken();
		}

		$httpReferer = str_replace('/', '\/', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['SERVER_NAME'] . PATH . $route['admin']['alias']);

		// если существует ячейка: $data['ADMIN_MODE']
		if (isset($data['ADMIN_MODE']) || preg_match('/^' . $httpReferer . '(\/?|$)/', $_SERVER['HTTP_REFERER'])) {
			// разрегистрируем (удалим) ячейку: $data['ADMIN_MODE'] У нас она просто флаг
			unset($data['ADMIN_MODE']);

			// и переопределяем переменную: $controller
			$controller = $route['admin']['path'] . 'AjaxController';
		}

		$controller = str_replace('/', '\\', $controller);
		$ajax = new $controller;
		$ajax->ajaxData = $data;

		$res = $ajax->ajax();

		if (is_array($res) || is_object($res)) {
			$res = json_encode($res);
		} elseif (is_int($res)) {
			$res = (float)$res;
		}

		return $res;
	}

	// функция генерирующая уникальный токен
	protected function generateToken()
	{
		return $_SESSION['token'] = md5(mt_rand(0, 999999) . microtime());
	}
}
