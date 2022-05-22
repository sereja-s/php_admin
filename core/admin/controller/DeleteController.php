<?php

namespace core\admin\controller;

use Cassandra\Set;
use core\base\settings\Settings;

class DeleteController extends BaseAdmin
{
	protected function inputData()
	{
		if (!$this->userId) {
			$this->execBase();
		}

		// вызовем метод: createTableData() и получим св-во: $this->table
		$this->createTableData();

		// проверим пришёл ли массив с параметрами и его ячейка: [$this->table] и не пусто ли там (иначе нечего удалять)
		if (!empty($this->parameters[$this->table])) {
			// очистим и получим $id в переменную
			// is_numeric() — определяет, является ли переменная числом или числовой строкой
			$id = is_numeric($this->parameters[$this->table]) ?
				$this->clearNum($this->parameters[$this->table]) :
				$this->clearStr($this->parameters[$this->table]);

			if ($id) {
				// получим данные из таблицы (по условию)
				$this->data = $this->model->get($this->table, [
					'where' => [$this->columns['id_row'] => $id]
				]);

				if ($this->data) {

					// то начинаем процедуру удаления 

					$this->data = $this->data[0];

					// если в свойстве: $this->parameters больше одной ячейки (элемента) массива
					// count() — подсчитывает все элементы в массиве или в объекте
					if (count($this->parameters) > 1) {
						// значит нужно удалить только элемент, а не всю запись в БД
						$this->checkDeleteFile();
					}

					// получим свойство: $settings (если оно есть и заполнено) иначе создадим такой объект класса: Settings
					$settings = $this->settings ?: Settings::instance();

					// в переменную получим свойство: fileTemplates
					$files = $settings::get('fileTemplates');

					if ($files) {

						foreach ($files as $file) {

							// в цикле пройдёмся по свойству: templateArr (его ячейке: $file) будем получать поля: $item
							foreach ($settings::get('templateArr')[$file] as $item) {

								// если не пусто в соответствующей ячейке (в $this->data[$item])
								if (!empty($this->data[$item])) {

									// json_decode()- декодирует json-строку (галерея и список файлов хранятся json-строкой),
									// а единичное изображение-обычной строкой
									// указали 2-ым параметром: true, чтобы пришёл ассоциативный массив (если 1-ым параметром 
									// передали json-строку) Если передали строку вернётся строка
									$fileData = json_decode($this->data[$item], true) ?: $this->data[$item];

									if (is_array($fileData)) {

										foreach ($fileData as $f) {
											// символ @ глушит ошибки если ф-ия: unlink не найдёт файл
											// unlink() — удаляет файл На вход: место хранения файла
											@unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $f);
										}
									} else {
										@unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $fileData);
									}
								}
							}
						}
					}

					// Далее при удалении необходимо перестроить все элементы в таблице БД

					if (!empty($this->data['menu_position'])) {

						$where = [];

						if (!empty($this->data['parent_id'])) {
							// посчитаем количество записей в таблице БД относительно parent_id
							// вернём нулевой элемент (его ячейку: ['count'])
							$pos = $this->model->get($this->table, [
								'fields' => ['COUNT(*) as count'],
								'where' => ['parent_id' => $this->data['parent_id']],
								'no_concat' => true
							])[0]['count'];

							$where = ['where' => 'parent_id'];

							// иначе (если ячейки: data['parent_id'] нет или пустая) условие: where не указываем
						} else {

							$pos = $this->model->get($this->table, [
								'fields' => ['COUNT(*) as count'],
								'no_concat' => true
							])[0]['count'];
						}

						// Переопределим данные в таблице: до удаления данных, перставляем элемент на самую последнюю позицию
						// (когда мы его удалим, его позиция уйдёт и все предыдущие позиции останутся (пересчитаются друг за другом))

						// вызываем метод модели (на вход 1-таблица, 2-поле которое пересчитываем, 3-условие (если есть), 4-конечная позаиция 5- сформированая переменная (с parent_id))
						$this->model->updateMenuPosition($this->table, 'menu_position', [$this->columns['id_row'] => $id], $pos, $where);
					}

					if ($this->model->delete($this->table, ['where' => [$this->columns['id_row'] => $id]])) {
						$tables = $this->model->showTables();

						if (in_array('old_alias', $tables)) {
							$this->model->delete('old_alias', [
								'where' => [
									'table_name' => $this->table,
									'table_id' => $id
								]
							]);
						}

						$manyToMany = $settings::get('manyToMany');

						if ($manyToMany) {
							foreach ($manyToMany as $mTable => $tables) {
								$targetKey = array_search($this->table, $tables);

								if ($targetKey !== false) {
									$this->model->delete($mTable, [
										'where' => [$tables[$targetKey] . '_' . $this->columns['id_row'] => $id]
									]);
								}
							}
						}

						$_SESSION['res']['answer'] = '<div class="success">' . $this->messages['deleteSuccess'] . '</div>>';

						$this->redirect($this->adminPath . 'show/' . $this->table);
					}
				}
			}
		}

		$_SESSION['res']['answer'] = '<div class="error">' . $this->messages['deleteFail'] . '</div>>';
		$this->redirect();
	}

	protected function checkDeleteFile()
	{
		unset($this->parameters[$this->table]);

		$updateFlag = false;

		foreach ($this->parameters as $row => $item) {
			$item = base64_decode($item);

			if (!empty($this->data[$row])) {
				$data = json_decode($this->data[$row], true);

				if ($data) {
					foreach ($data as $key => $value) {
						if ($item === $value) {
							$updateFlag = true;

							@unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $item);
							unset($data[$key]);

							$this->data[$row] = $data ? json_encode($data) : 'NULL';

							break;
						}
					}
				} elseif ($this->data[$row] === $item) {
					$updateFlag = true;

					@unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $item);

					$this->data[$row] = 'NULL';
				}
			}
		}

		if ($updateFlag) {
			$this->model->edit($this->table, [
				'fields' => $this->data
			]);

			$_SESSION['res']['answer'] = '<div class="success">' . $this->messages['editSuccess'] . '</div>>';
		} else {
			$_SESSION['res']['answer'] = '<div class="error">' . $this->messages['editFail'] . '</div>>';
		}

		$this->redirect();
	}
}
