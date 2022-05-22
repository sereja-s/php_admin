<?php

namespace core\admin\controller;

use core\base\exceptions\RouteException;
use mysql_xdevapi\Exception;

// Контроллер редактирования данных в административной панели
class EditController extends BaseAdmin
{
	// свойство необходимое для отправки форм
	protected $action = 'edit';

	protected function inputData()
	{
		if (!$this->userId) {
			$this->execBase();
		}

		$this->checkPost();

		$this->createTableData();

		$this->createData();

		$this->createForeignData();

		$this->createMenuPosition();

		$this->createRadio();

		$this->createOutputData();

		$this->createManyToMany();

		$this->template = ADMIN_TEMPLATE . 'add';

		return $this->expansion();
	}

	// метод который будет получать данные из БД
	protected function createData()
	{
		// очистим и получим $id в переменную
		// is_numeric()— определяет, является ли переменная числом или числовой строкой
		$id = is_numeric($this->parameters[$this->table]) ?
			$this->clearNum($this->parameters[$this->table]) :
			$this->clearStr($this->parameters[$this->table]);

		if (!$id) {
			throw new RouteException('Не корректный идентификатор - ' . $id .
				' при редактировании таблицы - ' . $this->table);
		}

		// получим данные
		$this->data = $this->model->get($this->table, [
			'where' => [$this->columns['id_row'] => $id]
		]);

		// положим данные в нулевую ячейку
		$this->data && $this->data = $this->data[0];
	}
}
