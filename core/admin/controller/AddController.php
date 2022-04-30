<?php

namespace core\admin\controller;

use core\base\settings\Settings;

// класс добавления данных
class AddController extends BaseAdmin
{
	protected $action = 'add';

	protected function inputData()
	{
		if (!$this->userId) {
			$this->execBase();
		}

		$this->checkPost();

		// метод, формирует колонки, которые нам нужны и выбирает имя таблицы из параметров (или берёт таблицу по умолчанию)
		$this->createTableData();

		// метод для получения внешних данных
		$this->createForeignData();

		$this->createMenuPosition();

		$this->createRadio();

		// метод, который будет формировать наши данные (раскидывать их по блокам)
		// (создание выходных данных)
		$this->createOutputData();

		$this->createManyToMany();

		return $this->expansion();
	}
}
