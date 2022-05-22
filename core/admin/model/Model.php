<?php

namespace core\admin\model;

use core\base\controller\Singleton;
use core\base\exceptions\RouteException;
use core\base\model\BaseModel;
use core\base\settings\Settings;

class Model extends BaseModel
{
	use Singleton;

	// метод модели показывающий внешние ключи таблиц в БД
	public function showForeignKeys($table, $key = false)
	{

		$db = DB_NAME;

		if ($key) {
			$where = "AND COLUMN_NAME = '$key' LIMIT 1";
		}

		// в переменной сохраним запрос к информационной БД (её таблице: KEY_COLUMN_USAGE) В условии: WHERE укажем назвааание БД 
		// и таблиц где искать , а также: CONSTRAINT_NAME <> 'PRIMARY' (т.е. не нужны первичные ключи)
		// ещё одно условие: REFERENCED_TABLE_NAME is not null $where" (т.е.таблица на которую мы ссылаемся должна быть не  пустая)

		// выбираем в информационной БД: information_schema следующие поля:
		// COLUMN_NAME (имя колонки (поле), которая ссылается на внешнюю таблицу)
		// REFERENCED_TABLE_NAME (имя таблицы на которую ссылаемся)
		// REFERENCED_COLUMN_NAME (имя колонки (поле), на которое ссылается)
		$query = "SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE 
                  WHERE TABLE_SCHEMA = '$db' AND TABLE_NAME = '$table' AND
                  CONSTRAINT_NAME <> 'PRIMARY' AND REFERENCED_TABLE_NAME is not null $where";

		return $this->query($query);
	}

	// метод модели формирования позиции вывода записей из базы данных
	// на вход: 1- $table (таблица с которой работаем), 2- $row (по умолчанию: menu_position), 3- $where (инструкция 
	// (если id пришёл в соответствующий метод в BaseAdmin)), 4- $end_pos (конечная позиция, на которую у нас встанет // текущая запись), 5- $update_rows (массив для сортировки (например относительно parent_id) )
	public function updateMenuPosition($table, $row, $where, $end_pos, $update_rows = [])
	{
		if ($update_rows && isset($update_rows['where'])) {

			$update_rows['operand'] = isset($update_rows['operand']) ? $update_rows['operand'] : ['='];

			// если пришла инструкция в переменную: $where
			if ($where) {
				// то получим старые данные из таблицы
				// (в переменную вернём нулевой элемент)
				$old_data = $this->get($table, [
					// поля: то что лежит в ячейке: $update_rows['where']- например parent_id и в переменой: $row- по 
					// умолчанию: menu_position
					'fields' => [$update_rows['where'], $row],
					// в инструкцию положим массив
					'where' => $where
				])[0];

				// в стартовую позицию положим то что лежит в ячейке: $old_data[$row] (т.е. какая позиция в 
				// menu_position уже есть у элемента в БД, такая и придёт)
				$start_pos = $old_data[$row];

				// если ПОСТ который пришёл отличается от того который был (например сменилась родительская категория и соответственно parent_id) 
				if ($old_data[$update_rows['where']] !== $_POST[$update_rows['where']]) {
					// в переменную получим из таблицы, количество элементов (в родтельской категории, котора была)
					$pos = $this->get($table, [
						'fields' => ['COUNT(*) as count'],
						'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
						'no_concat' => true
					])[0]['count'];

					// проверим не является ли старая позиция элемента последним элементом (если да, то не надо 
					// модифицировать старые данные)

					// если позиция элемента не последняя
					if ($start_pos != $pos) {
						// в переменную сохраним сформированную строку запроса для инструкций WHERE к БД
						$update_where = $this->createWhere([
							'where' => [$update_rows['where'] => $old_data[$update_rows['where']]],
							'operand' => $update_rows['operand']
						]);

						// выполним запрос, который изменит последовательность (menu_position) у всей таблицы 
						// относительно: parent_id
						$query = "UPDATE $table SET $row = $row - 1 $update_where AND $row <= $pos AND $row > $start_pos";

						// вызовем метод
						$this->query($query, 'u');
					}

					// получим другие стартовые позиции (относително нового parent_id)
					$start_pos = $this->get($table, [
						'fields' => ['COUNT(*) as count'],
						'where' => [$update_rows['where'] => $_POST[$update_rows['where']]],
						'no_concat' => true
					])[0]['count'] + 1;
				}
			} else {
				$start_pos = $this->get($table, [
					'fields' => ['COUNT(*) as count'],
					'where' => [$update_rows['where'] => $_POST[$update_rows['where']]],
					'no_concat' => true
				])[0]['count'] + 1;
			}

			// Далее сформируем корректирующий запрос

			if (array_key_exists($update_rows['where'], $_POST)) {

				$where_equal = $_POST[$update_rows['where']];
			} elseif (isset($old_data[$update_rows['where']])) {

				$where_equal = $old_data[$update_rows['where']];
			} else {

				$where_equal = NULL;
			}

			// в переменную сохраним сформированную строку запроса для инструкций WHERE к БД
			$db_where = $this->createWhere([
				'where' => [$update_rows['where'] => $where_equal],
				'operand' => $update_rows['operand']
			]);

			// иначе (если в $update_rows ничего не пришло и ячейка: $update_rows['where'] = null)
		} else {

			// если пришла инструкция в переменную: $where
			if ($where) {
				// получим первичное значение (место где элемент был раньше)
				// в переменную: $start_pos вернём нулевой элемент (то что лежит в ячейке: [$row])
				$start_pos = $this->get($table, [
					'fields' => [$row],
					'where' => $where
				])[0][$row];
				// иначе
			} else {
				// то мы добавляем данные и чтобы получить стартовую позицию: посчитаем поля, активируем флаг: no_concat
				// в переменную: $start_pos вернём нулевой элемент (то что лежит в ячейке: [$count] увеличеной на единицу)
				$start_pos = $this->get($table, [
					'fields' => ['COUNT(*) as count'],
					'no_concat' => true
				])[0]['count'] + 1;
			}
		}

		// если переменная: $db_where сформирована и отлична от null, со сохраним её в переменной: $db_where и 
		// конкатенируем строку: пробелAND, иначе в переменную: $db_where положим строку: WHERE
		$db_where = isset($db_where) ? $db_where . ' AND' : 'WHERE';


		if ($start_pos < $end_pos) {
			// запрос к БД (если номер позиции элемена в таблице стал больше)
			$query = "UPDATE $table SET $row = $row - 1 $db_where $row <= $end_pos AND $row > $start_pos";
		} elseif ($start_pos > $end_pos) {
			// запрос к БД (если номер позиции элемена в таблице стал меньше)
			$query = "UPDATE $table SET $row = $row + 1 $db_where $row >= $end_pos AND $row < $start_pos";
			// иначе (если позиции равны)
		} else {
			// ничего не изменится
			return;
		}
		// вернём результат
		return $this->query($query, 'u');
	}

	public function search($data, $currentTable = false, $qty = false)
	{
		$dbTables = $this->showTables();
		$data = addslashes($data);

		$arr = preg_split('/(,|\.)?\s+/', $data, 0, PREG_SPLIT_NO_EMPTY);
		$searchArr = [];
		$order = [];

		for (;;) {
			if (!$arr) {
				break;
			}

			$searchArr[] = implode(' ', $arr);
			unset($arr[count($arr) - 1]);
		}

		$correctCurrentTable = false;
		$projectTables = Settings::get('projectTables');

		if (!$projectTables) {
			throw new RouteException('Ошибка поиска нет разделов в админ панели');
		}

		foreach ($projectTables as $table => $item) {
			if (!in_array($table, $dbTables)) {
				continue;
			}

			$searchRows = [];
			$orderRows = ['name'];
			$fields = [];
			$columns = $this->showColumns($table);

			$fields[] = $columns['id_row'] . ' as id';

			$fieldName = isset($columns['name']) ? "CASE WHEN {$table}.name <> '' THEN {$table}.name " : '';

			foreach ($columns as $col => $value) {
				if ($col !== 'name' && stripos($col, 'name') !== false) {
					if (!$fieldName) {
						$fieldName = 'CASE ';
					}

					$fieldName .= "WHEN {$table}.$col <> '' THEN {$table}.$col ";
				}

				if (
					isset($value['Type']) &&
					(stripos($value['Type'], 'char') !== false ||
						stripos($value['Type'], 'text') !== false)
				) {
					$searchRows[] = $col;
				}
			}

			if ($fieldName) {
				$fields[] = $fieldName . 'END as name';
			} else {
				$fields[] = $columns['id_row'] . ' as name';
			}

			$fields[] = "('$table') AS table_name";

			$res = $this->createWhereOrder($searchRows, $searchArr, $orderRows, $table);

			$where = $res['where'];
			!$order && $order = $res['order'];

			if ($table === $currentTable) {
				$correctCurrentTable = true;
				$fields[] = "('current_table') AS current_table";
			}

			if ($where) {
				$this->buildUnion($table, [
					'fields' => $fields,
					'where' => $where,
					'no_concat' => true
				]);
			}
		}

		$orderDirection = null;

		if ($order) {
			$order = ($correctCurrentTable ? 'current_table DESC, ' : '') . '(' . implode('+', $order) . ')';
			$orderDirection = 'DESC';
		}

		$result = $this->getUnion([
			//'type' => 'all',
			//'pagination' => [],
			//'limit' => 3,
			'order' => $order,
			'order_direction' => $orderDirection
		]);

		if ($result) {
			foreach ($result as $index => $item) {
				$result[$index]['name'] .= '(' .
					(isset($projectTables[$item['table_name']]['name'])
						? $projectTables[$item['table_name']]['name']
						: $item['table_name']) . ')';

				$result[$index]['alias'] = PATH .
					Settings::get('routes')['admin']['alias'] . '/edit/' . $item['table_name'] . '/' . $item['id'];
			}
		}

		return $result ?: [];
	}

	protected function createWhereOrder($searchRows, $searchArr, $orderRows, $table)
	{
		$where = '';
		$order = [];

		if ($searchRows && $searchArr) {
			$columns = $this->showColumns($table);

			if ($columns) {
				$where = '(';

				foreach ($searchRows as $row) {
					$where .= '(';

					foreach ($searchArr as $item) {
						if (in_array($row, $orderRows)) {
							$str = "($row LIKE '%$item%')";

							if (!in_array($str, $order)) {
								$order[] = $str;
							}
						}

						if (isset($columns[$row])) {
							$where .= "{$table}.$row LIKE '%$item%' OR ";
						}
					}

					$where = preg_replace('/\)?\s*or\s*\(?$/i', '', $where) . ') OR ';
				}

				$where && $where = preg_replace('/\s*or\s*$/i', '', $where) . ')';
			}
		}

		return compact('where', 'order');
	}
}
