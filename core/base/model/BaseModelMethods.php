<?php

namespace core\base\model;



abstract class BaseModelMethods
{
	protected $sqlFunc = ['NOW()'];
	protected $tableRows;
	protected $union = [];

	protected function createFields($set, $table = false, $join = false)
	{
		if (array_key_exists('fields', $set) && $set['fields'] === null) {
			return '';
		}

		$concat_table = '';
		$alias_table = $table;

		if (!$set['no_concat']) {
			$arr = $this->createTableAlias($table);
			$concat_table = $arr['alias'] . '.';
			$alias_table = $arr['alias'];
		}


		// в переменную $fields сохраним пустую строку
		$fields = '';
		$join_structure = false;

		if (($join || isset($set['join_structure']) && $set['join_structure']) && $table) {
			$join_structure = true;
			$this->showColumns($table);

			if (isset($this->tableRows[$table]['multi_id_row'])) {
				$set['fields'] = [];
			}
		}

		if (!isset($set['fields']) || !is_array($set['fields']) || !$set['fields']) {
			if (!$join) {
				$fields = $concat_table . '*,';
			} else {
				foreach ($this->tableRows[$alias_table] as $key => $item) {
					if ($key !== 'id_row' && $key !== 'multi_id_row') {
						$fields .= $concat_table . $key . ' as TABLE' . $alias_table . 'TABLE_' . $key . ',';
					}
				}
			}
		} else {
			$id_field = false;

			// проходим по массиву $set (его ячейкам fields) как $field 
			// На каждой итерации значение текущего элемента массива $set['fields'] присваивается переменной $field
			foreach ($set['fields'] as $field) {
				if ($join_structure && !$id_field && $this->tableRows[$alias_table] === $field) {
					$id_field = true;
				}

				if ($field || $field === null) {
					if ($field === null) {
						$fields .= "NULL,";
						continue;
					}

					if ($join && $join_structure) {
						if (preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $matches)) {
							$fields .= $concat_table . $matches[1] . ' as TABLE' . $alias_table . 'TABLE_' . $matches[2] . ',';
						} else {
							$fields .= $concat_table . $field . ' as TABLE' . $alias_table . 'TABLE_' . $field . ',';
						}
					} else {
						$fields .= (!preg_match('/(\([^()]*\))|(case\s+.+?\s+end)/i', $field) ? $concat_table : '') . $field . ',';
					}
				}
			}

			if (!$id_field && $join_structure) {
				if ($join) {
					$fields .= $concat_table . $this->tableRows[$alias_table]['id_row']
						. ' as TABLE' . $alias_table . 'TABLE_' . $this->tableRows[$alias_table]['id_row'] . ',';
				} else {
					$fields .= $concat_table . $this->tableRows[$alias_table]['id_row'] . ',';
				}
			}
		}

		return $fields;
	}

	protected function createOrder($set, $table = false)
	{
		$table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
			? $this->createTableAlias($table)['alias'] . '.' : '';

		// сформируем пкстую строковую переменную $order_by
		$order_by = '';

		if (isset($set['order']) && $set['order']) {
			$set['order'] = (array)$set['order'];

			$set['order_direction'] = (isset($set['order_direction']) && $set['order_direction'])
				? (array)$set['order_direction'] : ['ASC'];

			// что бы каждый раз не делать проверку пришло ли что-нибудь в переменную $order_by (пусто или нет), сразу занесём в неё строк:у ORDER BY 
			$order_by = 'ORDER BY ';

			// объявим переменную $direct_count и изначально поставим в значение: ноль
			$direct_count = 0;

			// запускаем цикл foreach (перебирает массив, задаваемый с помощью $set['order'] 
			// На каждой итерации значение текущего элемента (в ячейке order из массива в переменной $set) присваивается переменной $order)
			foreach ($set['order'] as $order) {

				// проверим существует ли элемент массива order_direction с таким же порядковым номером, как элемент массива order
				// ( здесь элемент с номером ноль будет всегда (даже по умолчанию в $set['order_direction'] что то будет изначально))
				// т.е. если в элементе order_direction массива $set, есть ячейка direct_count
				if ($set['order_direction'][$direct_count]) {

					// то в переменную $order_direction сохраним этот элемент массива
					// ф-ия php: strtoupper()  — Преобразует строку в верхний регистр
					$order_direction = strtoupper($set['order_direction'][$direct_count]);
					// затем увеличиваем счётчик
					$direct_count++;
				} else {
					// иначе положим (сохраним) в переменную $order_direction предыдущий элемент массива $set['order_direction']
					$order_direction = strtoupper($set['order_direction'][$direct_count - 1]);
				}

				if (in_array($order, $this->sqlFunc)) {
					$order_by .= $order . ',';
					// is_int() — Проверяет, является ли переменная целым числом
				} elseif (is_int($order)) {
					$order_by .= $order . ' ' . $order_direction . ',';
				} else {

					// в переменную $order_by добавим (конкатенируем): переменную $table, переменную $order (укажет по какому полю 
					// сортировать), далее добавим: (конкатенируем) пробел, переменную $order_direction (направление сортировки), запятую
					$order_by .= $table . $order . ' ' . $order_direction . ',';
				}
			}

			// здесь обрежем запятую
			$order_by = rtrim($order_by, ',');
		}
		return $order_by;
	}

	// Метод createWhere() будет формировать строку запроса для инструкций WHERE в MySQL
	// на вход передаём: массив который пришёл, таблцу, инструкцию с значением по умолчанию: WHERE
	protected function createWhere($set, $table = false, $instruction = 'WHERE')
	{

		$table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
			? $this->createTableAlias($table)['alias'] . '.' : '';

		$where = ''; // в переменную записали пустую строку

		if (is_string($set['where'])) {
			return $instruction . ' ' . trim($set['where']);
		}

		// если в ячейку where массива $set что то пришло, проверим массив ли это и не пуст ли он
		if (is_array($set['where']) && !empty($set['where'])) {

			// перед сохранением результата сделаем проверки: пришло ли что-нибудь и является ли это массивом (не пустым) 
			// тогда сохраняем соответствующее значение иначе значение по умолчанию (в 1-ом случае: ячейку =, во 2-ом: ячейку AND)
			$set['operand'] = (is_array($set['operand']) && !empty($set['operand'])) ? $set['operand'] : ['='];
			$set['condition'] = (is_array($set['condition']) && !empty($set['condition'])) ? $set['condition'] : ['AND'];

			$where = $instruction;

			$o_count = 0;
			$c_count = 0;

			// запускаем цикл foreach по ячейке: where (массиву в ней), массива: $set (Здесь нам нужны и ключи и значения)
			foreach ($set['where'] as $key => $item) {
				// на каждой итерации цикла добавим пробел к переменной $where (содежит инструкции (здесь: строка WHERE), 
				// поступившие на вход ф-ии: createWhere())
				$where .= ' ';

				// проверим есть ли в ячейке operand массива $set, в ячейку $o_count что то пришло
				if ($set['operand'][$o_count]) {
					// то в переменную $operand сохраним то, что пришло
					$operand = $set['operand'][$o_count];
					// и делаем приращение переменной $o_count
					$o_count++;
				} else {
					// иначе сохраним предыдущее значение
					$operand = $set['operand'][$o_count - 1];
				}

				// такую же проверку делаем в массиве $set для ячейки condition (её ячейки $c_count)
				if ($set['condition'][$c_count]) {
					$condition = $set['condition'][$c_count];
					$c_count++;
				} else {
					$condition = $set['condition'][$c_count - 1];
				}

				// Проверим какой операнд пришёл: если IN или NOT IN
				if ($operand === 'IN' || $operand === 'NOT IN') {

					// если переменная $item- строка и в начале этой строки стоит SELECT
					if (is_string($item) && strpos($item, 'SELECT') === 0) {
						// то эту строку положим (сохраним) в переменную $in_str (без дополнительной обработки))
						$in_str = $item;
					} else {

						// если переменная $item- массив
						if (is_array($item)) {
							//то этот массив положим (сохраним) в переменную $temp_item (без дополнительной обработки))
							$temp_item = $item;
						} else {
							// иначе разберём строку в переменной $temp_item на массив по заданному разделителю- , (запятой)
							$temp_item = explode(',', $item);
						}

						// в этом случае (т.е. переменная $item- массив) в переменную $in_str сохраним пустую строку
						$in_str = '';

						// далее в цикле (foreach) переберём массив в переменной $temp_item и будем добавлять необходимые нам значения
						foreach ($temp_item as $v) {
							// на каждой итерации к переменной $in_str добавим злемент $v и запятую  в '' (одинарных кавычках) 
							// при этом ф-ии php: addslashes() —  Экранирует строку с помощью слешей (Возвращает строку с обратным 
							// слешем перед символами, которые нужно экранировать), 
							// trim() —  Удаляет пробелы из начала и конца строки (что бы пробелы тоже не попадали в ячейки массива)
							$in_str .= "'" . addslashes(trim($v)) . "',";
						}
					}

					// к переменной $where добавим: таблицу (название): $table, ключ: $key, пробел, операнд на текущий момент 
					// времени: $operand: IN или NOT IN, 
					// далее то что идёт после них |т.е. в $in_str, предварительно обрезав запятую в конце| находится в скобках с пробелами
					// и добавим то что есть в нашем условии: $condition
					$where .= $table . $key . ' ' . $operand . ' (' . trim($in_str, ',') . ') ' . $condition;

					// strpos()- Ищет позицию (порядковый номер) первого вхождения подстроки LIKE в строку $operand
					// если искомая подстрока LIKE будет стоять не первой, то она не будет найдена и ф-ия php^ strpos() вернёт false
					// убедимся что пришёл оператор LIKE и стоит первым в строке
				} elseif (strpos($operand, 'LIKE') !== false) {

					// разобъём строку $operand по заданному разделителю % (замещает любые символы при поиске в зависимости от того 
					// стоит в начале или конце искомого значения)
					// если ф-ия explode() не найдёт знака: %, то вся строка будет занесена в нулевой элемент массива
					$like_template = explode('%', $operand);

					foreach ($like_template as $lt_key => $lt) {

						// если в переменную $lt ничего не пришло (значит подстрока LIKE никуда не попала, т.е. стояла не первой в строке)
						if (!$lt) {
							// если в переменную $lt_key (в нулевой элемент) ничего не пришло (значит знак % стоял впереди подстроки LIKE)
							if (!$lt_key) {
								$item = '%' . $item;
							} else {
								$item .= '%';
							}
						}
					}

					$where .= $table . $key . ' LIKE ' . "'" . addslashes($item) . "' $condition";
				} else {

					// strpos()- Ищет позицию (порядковый номер) первого вхождения подстроки SELECT в строку $item
					// если SELECT стоит на первой позиции т.е. в нулевом элементе
					if (strpos($item, 'SELECT') === 0) {
						$where .= $table . $key . $operand . '(' . $item . ") $condition";
					} elseif ($item === null || $item === 'NULL') {
						if ($operand === '=') {
							$where .= $table . $key . ' IS NULL ' . $condition;
						} else {
							$where .= $table . $key . ' IS NOT NULL ' . $condition;
						}
					} else {
						$where .= $table . $key . $operand . "'" . addslashes($item) . "' $condition";
					}
				}
			}

			// ф-ия php: substr() — возвращает часть строки $where начиная с нулевого элемента (0) и заканчивая последним вхождением переменной $condition в строке $where (т.е. обрезаем то, что хранится в переменной $condition (условие запроса))
			$where = substr($where, 0, strrpos($where, $condition));
		}

		return $where;
	}

	protected function createJoin($set, $table, $new_where = false)
	{

		$fields = '';
		$join = '';
		$where = '';

		// если в массиве $set его ячейка join не пустая (что то пришло)
		if ($set['join']) {
			$join_table = $table;

			foreach ($set['join'] as $key => $item) {

				// проверим является ли числом ключ $key (чсловым массивом)
				if (is_int($key)) {
					// если не существует (или пустая) в массиве $item его ячейка table
					if (!$item['table']) {
						// переводим цикл на следующую итерацию
						continue;
						// иначе
					} else {
						// в $key сохраним ячейку table массива $item
						$key = $item['table'];
					}
				}

				$concatTable = $this->createTableAlias($key)['alias'];

				// если в переменной $join что то есть
				if ($join) {
					// то конкатенируем к ней пробел
					$join .= ' ';
				}

				// isset() — Определяет, была ли установлена переменная значением, отличным от null
				// ячейка on в массиве $item- показывает по какому признаку объединять таблицы
				if (isset($item['on']) && $item['on']) {

					// обявим пустой массив в переменной $join_fields
					$join_fields = [];

					// isset() — Определяет, была ли установлена переменная значением, отличным от null
					// проверим есть ли поле (ячейка) fields (в массиве $item, его ячейке on) и является ли это массивом и посчитаем
					// элементы этого массива (равно ли их количество 2-ум)
					if (isset($item['on']['fields']) && is_array($item['on']['fields']) && count($item['on']['fields']) === 2) {
						// в переменную $join_fields положим то что пришло в массиве $item['on']['fields']
						$join_fields = $item['on']['fields'];
						// посчитаем поля массива в ячейке on (равно ли их количество двум)
					} elseif (count($item['on']) === 2) {
						// в переменную $join_fields положим то что пришло в массиве $item['on']
						$join_fields = $item['on'];
					} else {
						// иначе переводим цикл на следующую итерацию
						continue;
					}

					// определим тип присоединения
					// если тип JOIN не пришёл
					if (!$item['type']) {
						// то по умолчанию: к преременной $join конкатенируем (присоединяем) LEFT JOIN
						$join .= 'LEFT JOIN ';
					} else {
						// иначе к преременной $join добавим строку приведённую к верхнему регистру (ф-ия: strtoupper()), далее через // пробел конкатенируем слово JOIN и снова поставим пробел 
						// trim()— Удаляет пробелы (или другие символы) из начала и конца строки
						$join .= trim(strtoupper($item['type'])) . ' JOIN ';
					}

					// Мы должны указать какую таблицу присоединяем
					// сначала к переменной $join добавляем ключ $key, далее добавляем строку ON (метод присоединения) с пробелами в начале и в конце
					$join .= $key . ' ON ';

					// если существует переменная $item с массивом, в нём- ячека on с массивом, а в нём ячейка: table
					if ($item['on']['table']) {
						// сохраняем эту переменную
						$join_temp_table = $item['on']['table'];
					} else {
						// иначе присоединяем предыдущую таблицу
						$join_temp_table = $join_table;
					}

					$join .= $this->createTableAlias($join_temp_table)['alias'];

					// добавим поле таблицы, которую мы пристыковываем
					$join .= '.' . $join_fields[0] . '=' . $concatTable . '.' . $join_fields[1];

					// ханесём в переменную $join_table текущую таблицу (в переменной $key), что бы следующая итерация цикла могла работать с предыдущей таблицей (в итерации- текущая)
					$join_table = $key;

					// если пришла новая инструкция where
					if ($new_where) {
						// проверка: существует ли что-нибудь (дополнительное условие) в ячейке where, массива в переменной $item
						if ($item['where']) {
							$new_where = false;
						}

						// в переменную $group_condition запишем строку (инструкция) WHERE
						$group_condition = 'WHERE';
					} else {
						// сохраним результат проврки в переменную $group_condition: если пришёл $item['group_condition'], то 
						// сохраним его (предварительно преобразовав в заглавные буквы), иначе сохраним слово: AND
						$group_condition = $item['group_condition'] ? strtoupper($item['group_condition']) : 'AND';
					}

					$fields .= $this->createFields($item, $key, $set['join_structure']);
					$where .= $this->createWhere($item, $key, $group_condition);
				}
			}
		}

		// ф-ия зhp: compact() — создание массива, содержащего переменные и их значения
		// Для каждого из них compact() ищет переменную с таким именем в текущей таблице символов и добавляет ее в выходной 
		//массив таким образом, что имя переменной становится ключом, а содержимое переменной становится значением для этого ключа
		return compact('fields', 'join', 'where');
	}

	// метод createInsert() будет создавать массив вставки
	// переменная $except позволяет сбрасывать поле (для указания какие поля исключить и запрос не будет их отрабатывать)
	protected function createInsert($fields, $files, $except)
	{

		$insert_arr = [];

		$insert_arr['fields'] = '(';

		$array_type = array_keys($fields)[0];

		if (is_int($array_type)) {
			$check_fields = false;
			$count_fields = 0;

			foreach ($fields as $i => $item) {
				$insert_arr['values'] .= '(';

				if (!$count_fields) {
					$count_fields = count($fields[$i]);
				}

				$j = 0;

				foreach ($item as $row => $value) {
					if ($except && in_array($row, $except)) {
						continue;
					}

					if (!$check_fields) {
						$insert_arr['fields'] .= $row . ',';
					}

					if (in_array($value, $this->sqlFunc)) {
						$insert_arr['values'] .= $value . ',';
					} elseif ($value == 'NULL' || $value === NULL) {
						$insert_arr['values'] .= "NULL" . ',';
					} else {
						$insert_arr['values'] .= "'" . addslashes($value) . "',";
					}

					$j++;

					if ($j === $count_fields) {
						break;
					}
				}
				if ($j < $count_fields) {
					for (; $j < $count_fields; $j++) {
						$insert_arr['values'] .= "NULL" . ',';
					}
				}

				$insert_arr['values'] = rtrim($insert_arr['values'], ',') . '),';

				if (!$check_fields) {
					$check_fields = true;
				}
			}
		} else {
			$insert_arr['values'] = '(';

			if ($fields) {
				foreach ($fields as $row => $value) {

					// если в $except что то пришло (указания на исключение полей) и
					// ф-ия php: in_array() — проверяет, существует ли значение $row в массиве $except (т.е. указание: это ряд не добавлять)
					if ($except && in_array($row, $except)) {
						// переходим на следующую итерацию цикла
						continue;
					}

					// добавим поле $row в ячейку fields (в массиве $insert_arr) и в конце запятую
					$insert_arr['fields'] .= $row . ',';

					// ф-ия php: in_array() — проверяет, существует ли значение $value в массиве, который хранится в свойстве: $sqlFunc 
					if (in_array($value, $this->sqlFunc)) {
						$insert_arr['values'] .= $value . ',';
					} elseif ($value == 'NULL' || $value === NULL) {
						$insert_arr['values'] .= "NULL" . ',';
					} else {

						// обработаем $values одинарными кавычками, а также зкранируем слешами (addslashes($value)) и запятая в конце
						$insert_arr['values'] .= "'" . addslashes($value) . "',";
					}
				}
			}

			if ($files) {
				foreach ($files as $row => $file) {
					$insert_arr['fields'] .= $row . ',';

					// проверим является ли массивом то что пришло в переменную $file
					if (is_array($file)) {

						// ф-я php: json_encode()- Возвращает строку, содержащую JSON-представление предоставленной платформы: $files
						// (формирует JSON-строку т.е. здесь- представляет массив (поданный на вход) в строковом виде, чтобы 
						// сохранить его в базе данных)
						// затем обработаем результат работы ф-ии json_encode() одинарными кавычками, а также зкранируем слешами 
						// (addslashes(json_encode($files)) и запятая в конце
						$insert_arr['values'] .= "'" . addslashes(json_encode($files)) . "',";
					} else {
						// иначе обработаем $file одинарными кавычками, а также зкранируем слешами (addslashes($file)) и запятая в конце
						$insert_arr['values'] .= "'" . addslashes($file) . "',";
					}
				}
			}
			$insert_arr['values'] = rtrim($insert_arr['values'], ',') . ')';
		}
		$insert_arr['fields'] = rtrim($insert_arr['fields'], ',') . ')';
		$insert_arr['values'] = rtrim($insert_arr['values'], ',');

		// возвращаем массив (и затем он попадёт в final public function add() в BaseModel.php)
		return $insert_arr;
	}

	protected  function createUpdate($fields, $files, $except)
	{
		// изначально переменную $update определим как пустую строку
		$update = '';

		if ($fields) {
			foreach ($fields as $row => $value) {

				// если в $except что то пришло (указания на исключение полей) и
				// ф-ия php: in_array() — проверяет, существует ли значение $row в массиве $except (т.е. указание: это ряд не добавлять)
				if ($except && in_array($row, $except)) {

					// переходим на следующую итерацию цикла
					continue;
				}

				// к переменной $update добавляем поле $row и конкатенируем к нему знак равенства
				$update .= $row . '=';

				// ф-ия php: in_array() — проверяет, существует ли значение $value в массиве, который хранится в свойстве: $sqlFunc 
				if (in_array($value, $this->sqlFunc)) {

					// то к переменной $update добавляем значение переменной $value и конкатенируем к нему запятую
					$update .= $value . ',';

					// если значение в переменной $value строго равно NULL или 'NULL'
				} elseif ($value === NULL || $value === 'NULL') {

					// то к переменной $update добавим слово NULL в двойных кавычках (что и будет означать нулевое значение) и потом конкатенируем запятую
					$update .= "NULL" . ',';

					// иначе
				} else {
					// обработаем $values одинарными кавычками, а также зкранируем слешами (addslashes($value)) и запятая в конце
					$update .= "'" . addslashes($value) . "',";
				}
			}
		}

		if ($files) {

			foreach ($files as $row => $file) {
				$update .= $row . '=';

				// проверим является ли массивом то что пришло в переменную $file
				if (is_array($file)) {

					// ф-я php: json_encode()- Возвращает строку, содержащую JSON-представление предоставленной платформы: $files
					// (формирует JSON-строку т.е. здесь- представляет массив (поданный на вход) в строковом виде, чтобы 
					// сохранить его в базе данных)
					// затем обработаем результат работы ф-ии json_encode() одинарными кавычками, а также зкранируем слешами 
					// (addslashes(json_encode($files)) и запятая в конце
					$update .= "'" . addslashes(json_encode($file)) . "',";
				} else {
					// иначе обработаем $file одинарными кавычками, а также зкранируем слешами (addslashes($file)) и запятая в конце
					$update .= "'" . addslashes($file) . "',";
				}
			}
		}

		// ф-ия php: rtrim — удаление пробелов (или других символов) из конца строки (здесь- обрежем запятую в конце строки  в переменной $update)
		return rtrim($update, ',');
	}

	protected function joinStructure($res, $table)
	{
		$join_arr = [];
		$id_row = $this->tableRows[$this->createTableAlias($table)['alias']]['id_row'];

		foreach ($res as $value) {
			if ($value) {
				if (!isset($join_arr[$value[$id_row]])) {
					$join_arr[$value[$id_row]] = [];
				}

				foreach ($value as $key => $item) {
					if (preg_match('/TABLE(.+)?TABLE/u', $key, $matches)) {
						$table_name_normal = $matches[1];

						if (!isset($this->tableRows[$table_name_normal]['multi_id_row'])) {
							$join_id_row = $value[$matches[0] . '_' . $this->tableRows[$table_name_normal]['id_row']];
						} else {
							$join_id_row = '';

							foreach ($this->tableRows[$table_name_normal]['multi_id_row'] as $multi) {
								$join_id_row .= $value[$matches[0] . '_' . $multi];
							}
						}

						$row = preg_replace('/TABLE(.+)TABLE_/u', '', $key);

						if ($join_id_row && !isset($join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row])) {
							$join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row] = $item;
						}

						continue;
					}

					$join_arr[$value[$id_row]][$key] = $item;
				}
			}
		}

		return $join_arr;
	}

	protected function createTableAlias($table)
	{
		$arr = [];

		if (preg_match('/\s+/i', $table)) {
			$table = preg_replace('/\s{2,}/i', ' ', $table);
			$table_name = explode(' ', $table);

			$arr['table'] = trim($table_name[0]);
			$arr['alias'] = trim($table_name[1]);
		} else {
			$arr['alias'] = $arr['table'] = $table;
		}

		return $arr;
	}
}
