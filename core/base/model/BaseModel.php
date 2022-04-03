<?php

namespace core\base\model;

use core\base\exceptions\DbException;
use core\base\model\BaseModelMethods;

//$res = $db->get($table, [
//    'fields' => ['id', 'name'],
//    'no_concat' => [true, false], если true, то не присоединять имя таблицы к полям и where
//    'where' => [
//        'name' => "O'Raily"
//    ],
////            'operand' => ['IN', '<>'],
////            'condition' => ['AND', 'OR'],
//    'order' => ['name'],
//    'order_direction' => ['DESC'],
//    'limit' => '1',
//    'join' => [
//        [
//            'table' => 'join_table1',
//            'fields' => ['id as j_id', 'name as j_name'],
//            'type' => 'left',
//            'where' => ['name' => 'Sasha'],
//            'operand' => ['='],
//            'condition' => ['OR'],
//            'on' => ['id', 'parent_id'],
//            'group_condition' => 'AND'
//        ],
//        'join_table2' => [
//            'table' => 'join_table2',
//            'fields' => ['id as j2_id', 'name as j2_name'],
//            'type' => 'left',
//            'where' => ['name' => 'Sasha'],
//            'operand' => ['<>'],
//            'condition' => ['OR'],
//            'on' => ['id', 'parent_id']
//        ]
//    ]
//]);

abstract class BaseModel extends BaseModelMethods
{
	protected $db;

	protected function connect()
	{

		//	ИНИЦИАЛИЗАЦИЯ ПОДКЛЮЧЕНИЯ ПРИ ПОМОЩИ ПОДКЛЮЧЕНИЯ БИБЛИОТЕКИ mysqli
		// обращаемся к свойству db этого класса и в него сохраняем объект подключения библиотеки mysqli (установим заглушку текущих ошибок- @)
		// класс mysqli (находится в глобальном пространстве имён) на вход принимает: которые хранятся в константах: 
		// HOST (имя хоста), USER (имя пользователя), PASS (пароль), DB_NAME (имя базы данных)
		$this->db = @new \mysqli(HOST, USER, PASS, DB_NAME);

		// проверка:если у объекта класса mysqli ($this->db) в его свойстве connect_error (хранит в себе текст ошибки подключения) что то есть 
		if ($this->db->connect_error) {
			// то выбросим (сгенерируем исключение) текст, указание кода ошибки (при подключении) в свойстве connect_errno и сообщение об ошибке (при подключении) в свойстве connect_error
			throw new DbException('Ошибка подключения к базе данных: '
				. $this->db->connect_errno . ' ' . $this->db->connect_error);
		}

		// если соединение с БД удалось, установим кодировку соединения (испольуем метод библиотеки mysqli (query()), 
		// которому на вход подаётся запрос на устаноку соединения(SET NAMES UTF8))
		$this->db->query("SET NAMES UTF8");
	}

	/**
	 * @param $query
	 * @param string $crud = r - SELECT / c - INSERT / u - UPDATE / d - DELETE
	 * @param false $return_id
	 * @return array|bool|mixed
	 * @throws DbException
	 */

	// Создадим метод query() с одноимённым названием (как и метод в классе (библиотеке) mysqli, использованный ранее)
	// сделаем его финальным (не дадим возможности переопределять его в дочерних классах)
	// на вход передадим: переменную с запросом ($query), метод которым будем этот запрос осуществлять (переменная $crud со значением по умолчанию: r (read- чтение)), идентификатор вставки $return_id со значением false
	final public function query($query, $crud = 'r', $return_id = false)
	{
		//Сделаем запрос к базе данных:
		// в переменную $result сохраним результат работы метода query() класса mysqli, обратившись к нему через объект этого 
		// класса, который хранится в свойстве $this->db (на вход подаётся переменная $query)
		//Т.е. в переменную $result приходит объект, содержащий в себе выборку из базы данных
		$result = $this->db->query($query);

		// обработаем запрос
		// проерка: если у объекта класса mysqli, который хранится в свойстве $this->db, в свойстве affected_rows (эффективные 
		// ряды затронутые выборкой) хранится значение -1
		if ($this->db->affected_rows === -1) {
			// то БД вернёт нам ошибку (выбросит исключение): текст, сам запрос, код ошибки (в запросе) в св-ве  errno объекта
			// $this->db, сообщение об ошибке (в запросе) в св-ве error объекта $this->db
			throw new DbException('Ошибка в SQL запросе: '
				. $query . ' - ' . $this->db->errno . ' ' . $this->db->error);
		}

		// при помощи оператора множественного выбора switch проверим что находится в переменной $crud (проверяется равенство указанного на входе значения (здесь- $crud), значениям в каждом кейсе) 
		// каждый следующий кейс проверяется, если не выполнен(не было равенства) предыдущий
		switch ($crud) {
				// проверим кейс: r (чтение)
			case 'r':
				// если в св-во num_rows нашего объекта $result (содержащего в себе выборку из базы данных) что то пришло из БД
				if ($result->num_rows) {

					// то в переменной $res объявляем массив
					$res = [];
					// проходимся в цикле for по данному массиву
					for ($i = 0; $i < $result->num_rows; $i++) {

						// массив $res[] заполняем тем, что вытащит метод fetch_assoc() объекта $result (т.е. в понимаемом виде вернёт
						// массив каждого ряда выбоорки, который хранился в объекте $result)
						$res[] = $result->fetch_assoc();
					}
					return $res;
				}
				return false;
				break;

				// проверим кейс: с (создание)
			case 'c':
				// если переменная $return_id = true
				if ($return_id) {
					return $this->db->insert_id;
				}
				return true;
				break;
				// во всех остальных случаях, выполнится код по умолчанию
			default:
				return true;
				break;
		}
	}

	/**
	 * @param $table
	 * @param array $set
	 */

	final public function get($table, $set = [])
	{

		$fields = $this->createFields($set, $table);

		$order = $this->createOrder($set, $table);

		$where = $this->createWhere($set, $table);

		if (!$where) {
			$new_where = true;
		} else {
			$new_where = false;
		}

		$join_arr = $this->createJoin($set, $table, $new_where);

		$fields .= $join_arr['fields'];
		$join = $join_arr['join'];
		$where .= $join_arr['where'];

		$fields = rtrim($fields, ',');

		$limit = $set['limit'] ? 'LIMIT ' . $set['limit'] : '';

		$query = "SELECT $fields FROM $table $join $where $order $limit";

		if (!empty($set['return_query'])) {
			return $query;
		}

		$res = $this->query($query);

		if (isset($set['join_structure']) && $set['join_structure'] && $res) {
			$res = $this->joinStructure($res, $table);
		}

		return $res;
	}

	final public function add($table, $set = [])
	{
		$set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
		$set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

		if (!$set['fields'] && !$set['files']) {
			return false;
		}

		$set['return_id'] = $set['return_id'] ? true : false;
		$set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

		$insert_arr = $this->createInsert($set['fields'], $set['files'], $set['except']);

		$query = "INSERT INTO $table {$insert_arr['fields']} VALUES {$insert_arr['values']}";

		return $this->query($query, 'c', $set['return_id']);
	}

	final public function edit($table, $set = [])
	{
		$set['fields'] = (is_array($set['fields']) && !empty($set['fields'])) ? $set['fields'] : $_POST;
		$set['files'] = (is_array($set['files']) && !empty($set['files'])) ? $set['files'] : false;

		if (!$set['fields'] && !$set['files']) {
			return false;
		}

		$set['except'] = (is_array($set['except']) && !empty($set['except'])) ? $set['except'] : false;

		if (!$set['all_rows']) {
			if ($set['where']) {
				$where = $this->createWhere($set);
			} else {
				$columns = $this->showColumns($table);

				if (!$columns) {
					return false;
				}

				if ($columns['id_row'] && $set['fields'][$columns['id_row']]) {
					$where = 'WHERE ' . $columns['id_row'] . '=' . $set['fields'][$columns['id_row']];
					unset($set['fields'][$columns['id_row']]);
				}
			}
		}

		$update = $this->createUpdate($set['fields'], $set['files'], $set['except']);

		$query = "UPDATE $table SET $update $where";

		return $this->query($query, 'u');
	}

	public function delete($table, $set = [])
	{
		$table = trim($table);
		$where = $this->createWhere($set, $table);

		$columns = $this->showColumns($table);

		if (!$columns) {
			return false;
		}

		if (is_array($set['fields']) && !empty($set['fields'])) {
			if ($columns['id_row']) {
				$key = array_search($columns['id_row'], $set['fields']);

				if ($key !== false) {
					unset($set['fields'][$key]);
				}
			}

			$fields = [];

			foreach ($set['fields'] as $field) {
				$fields[$field] = $columns[$field]['Default'];
			}

			$update = $this->createUpdate($fields, false, false);

			$query = "UPDATE $table SET $update $where";
		} else {
			$join_arr = $this->createJoin($set, $table);
			$join = $join_arr['join'];
			$join_tables = $join_arr['tables'];

			$query = 'DELETE ' . $table . $join_tables . ' FROM ' . $table . ' ' . $join . ' ' . $where;
		}

		return $this->query($query, 'u');
	}

	public function buildUnion($table, $set)
	{
		if (array_key_exists('fields', $set) && $set['fields'] === null) {
			return $this;
		}

		if (!array_key_exists('fields', $set) || empty($set['fields'])) {
			$set['fields'] = [];

			$columns = $this->showColumns($table);

			unset($columns['id_row'], $columns['multi_id_row']);

			foreach ($columns as $row => $item) {
				$set['fields'][] = $row;
			}
		}

		$this->union[$table] = $set;
		$this->union[$table]['return_query'] = true;
		return $this;
	}

	public function getUnion($set = [])
	{
		if (!$this->union) {
			return false;
		}

		$unionType = ' UNION ' . (!empty($set['type']) ? strtoupper($set['type']) . ' ' : '');

		$maxCount = 0;
		$maxTableCount = '';

		foreach ($this->union as $key => $item) {
			$count = count($item['fields']);
			$joinFields = '';

			if (!empty($item['join'])) {
				foreach ($item['join'] as $table => $data) {
					if (array_key_exists('fields', $data) && $data['fields']) {
						$count += count($data['fields']);
						$joinFields = $table;
					} elseif (!array_key_exists('fields', $data) || (!$joinFields['data'] || $data['fields'] === null)) {
						$columns = $this->showColumns($table);
						unset($columns['id_row'], $columns['multi_id_row']);

						$count += count($columns);

						foreach ($columns as $field => $value) {
							$this->union[$key]['join'][$table]['fields'][] = $field;
						}

						$joinFields = $table;
					}
				}
			} else {
				$this->union[$key]['no_concat'] = true;
			}

			if ($count > $maxCount || ($count === $maxCount && $joinFields)) {
				$maxCount = $count;
				$maxTableCount = $key;
			}

			$this->union[$key]['lastJoinTable'] = $joinFields;
			$this->union[$key]['countFields'] = $count;
		}

		$query = '';

		if ($maxCount && $maxTableCount) {
			$query .= '(' . $this->get($maxTableCount, $this->union[$maxTableCount]) . ')';
			unset($this->union[$maxTableCount]);
		}

		foreach ($this->union as $key => $item) {
			if (isset($item['countFields']) && $item['countFields'] < $maxCount) {
				for ($i = 0; $i < $maxCount - $item['countFields']; $i++) {
					if ($item['lastJoinTable']) {
						$item['join'][$item['lastJoinTable']]['fields'][] = null;
					} else {
						$item['fields'][] = null;
					}
				}
			}

			$query && $query .= $unionType;
			$query .= '(' . $this->get($key, $item) . ')';
		}

		$order = $this->createOrder($set);

		$limit = !empty($set['limit']) ? 'LIMIT ' . $set['limit'] : '';

		if (method_exists($this, 'createPagination')) {
			$this->createPagination($set, "($query)", $limit);
		}

		$query .= " $order $limit";

		$this->union = [];

		return $this->query(trim($query));
	}

	final public function showColumns($table)
	{
		if (!isset($this->tableRows[$table]) || !$this->tableRows[$table]) {
			$checkTable = $this->createTableAlias($table);

			if ($this->tableRows[$checkTable['table']]) {
				return $this->tableRows[$checkTable['alias']] = $this->tableRows[$checkTable['table']];
			}

			$query = "SHOW COLUMNS FROM {$checkTable['table']}";
			$res = $this->query($query);

			$this->tableRows[$checkTable['table']] = [];

			if ($res) {
				foreach ($res as $row) {
					$this->tableRows[$checkTable['table']][$row['Field']] = $row;
					if ($row['Key'] === 'PRI') {
						if (!isset($this->tableRows[$checkTable['table']]['id_row'])) {
							$this->tableRows[$checkTable['table']]['id_row'] = $row['Field'];
						} else {
							if (!isset($this->tableRows[$checkTable['table']]['multi_id_row'])) {
								$this->tableRows[$checkTable['table']]['multi_id_row'][] = $this->tableRows[$checkTable['table']]['id_row'];
								$this->tableRows[$checkTable['table']]['multi_id_row'][] = $row['Field'];
							}
						}
					}
				}
			}
		}

		if (isset($checkTable) && $checkTable['table'] !== $checkTable['alias']) {
			return $this->tableRows[$checkTable['alias']] = $this->tableRows[$checkTable['table']];
		}

		return $this->tableRows[$table];
	}

	final public function showTables()
	{
		$query = 'SHOW TABLES';

		$tables = $this->query($query);

		$table_arr = [];

		if ($tables) {
			foreach ($tables as $table) {
				$table_arr[] = reset($table);
			}
		}

		return $table_arr;
	}
}
