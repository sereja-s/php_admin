<?php

namespace core\admin\controller;

use core\base\controller\BaseController;
use core\admin\model\Model;
use core\base\model\BaseModel;
use core\base\settings\Settings;

class IndexController extends BaseController
{
	protected function inputData()
	{
		// пропишем путь куда мы будем перенапралять на контроллер (будем считать по умолчанию) и сохраним его в переменной: $redirect-
		// константа PATH (корень сайта), далее обратимся к настройкам: Settings, там к методу get() и получить массив: routes 
		// (нужны только: его ячейка ['admin'] и далее ячейка ['alias'] и затем конкатенируем название контроллера после слеша: /show)
		$redirect = PATH . Settings::get('routes')['admin']['alias'] . '/show';

		// обратимся к методу redirect(), который перенаправляет пользователя (на вход передаём: путь (переменную $redirect))
		$this->redirect($redirect);
	}
}

// Используемые методы (CRUD): 

// add (create)- добавить (создать)
// edit (update)- редактировать (обновить)
// get (read)- получить (прочитать)
// delete- удалить