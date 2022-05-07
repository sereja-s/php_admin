<?php

namespace core\admin\controller;

use core\base\settings\Settings;

// класс добавления данных
class AddController extends BaseAdmin
{
	// для корректного формирования пути для добавления данных в шаблоне объявим свойство
	protected $action = 'add';

	protected function inputData()
	{
		if (!$this->userId) {
			$this->execBase();
		}

		// вызовем метод, для определения пришло ли что-нибудь через массив: Post
		$this->checkPost();

		// вызовем метод, формирует колонки, которые нам нужны и выбирает имя таблицы из параметров (или берёт таблицу по умолчанию)
		$this->createTableData();

		// вызовем метод для получения внешних данных
		$this->createForeignData();

		// вызовем метод для формирования первичных данных для сортировки информации в таблицах базы данных
		$this->createMenuPosition();

		// вызовем метод формирования ключей и значений для input type radio (кнопок переключателей (да, нет и т.д.))
		$this->createRadio();

		// вызовем метод, который будет формировать наши данные (раскидывать их по блокам)
		// (создание выходных данных)
		$this->createOutputData();

		$this->createManyToMany();

		return $this->expansion();
	}
}
