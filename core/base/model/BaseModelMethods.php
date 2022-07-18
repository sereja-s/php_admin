<?php

namespace core\base\model;



abstract class BaseModelMethods
{
	protected $sqlFunc = ['NOW()'];
	protected $tableRows;
	// свойство используемое в методах модели для формирования UNION запросов к базе данных
	protected $union = [];

	protected function createFields($set, $table = false, $join = false)
	{
		// array_key_exists() — проверяет, существует ли в массиве заданный ключ или индекс
		if (array_key_exists('fields', $set) && $set['fields'] === null) {
			return '';
		}

		$concat_table = '';
		$alias_table = $table;

		if (empty($set['no_concat'])) {

			$arr = $this->createTableAlias($table);
			$concat_table = $arr['alias'] . '.';
			$alias_table = $arr['alias'];
		}


		// в переменную $fields сохраним пустую строку
		$fields = '';
		// объявим флаг и поставим ему значение по умолчаанию
		$join_structure = false;

		// если у нас пришёл: $join или значение в ячейке: $set['join_structure'] установлено отличным от null
		// и $set['join_structure'] возвращает true и что то пришло в $table
		if (($join || isset($set['join_structure']) && $set['join_structure']) && $table) {
			// поставим флаг структуризации джоинов в значение: true
			$join_structure = true;
			$this->showColumns($table);

			if (isset($this->tableRows[$table]['multi_id_row'])) {
				// обнуляем поле
				$set['fields'] = [];
			}
		}

		// сделаем проверку:
		if (!isset($set['fields']) || !is_array($set['fields']) || !$set['fields']) {
			// если ничего не пришло в $join (т.е. или отсутствует ячейка: join_structure и 3-им параметром в метод: 
			// createFields() придёт: $join = false, или когда метод: createFields() вызывается из основного метода 
			// модели: get() В этом случае нам не нужно объявлять псевдонимы, т.к. это выборка для основных полей (идёт // с основными именами))
			if (!$join) {
				$fields = $concat_table . '*,';
				// иначе надо собирать только поля с псевдонимами
			} else {
				foreach ($this->tableRows[$alias_table] as $key => $item) {
					// если ключ не равен служебным полям: id_row и не равен полям: multi_id_row
					if ($key !== 'id_row' && $key !== 'multi_id_row') {
						// в пустую строку: $fields добавим:
						$fields .= $concat_table . $key . ' as TABLE' . $alias_table . 'TABLE_' . $key . ',';
					}
				}
			}
		} else {
			// сделаем флаг и установим значение по умолчанию: false
			$id_field = false;

			// проходим по массиву $set (его ячейке fields) как $field 
			// На каждой итерации значение текущего элемента массива $set['fields'] присваивается переменной $field
			foreach ($set['fields'] as $field) {
				// если флаг: $join_structure стоит в true (т.е. нам необходимо структурировать данные) и никакое из полей не является первичным ключём (т.е. !$id_field), и ячейка: tableRows[$alias_table] равна $field
				if ($join_structure && !$id_field && $this->tableRows[$alias_table] === $field) {
					// поставим флаг в true (теперь знаем, что первичный ключ добавлен в наш запрос)
					$id_field = true;
				}


				// делаем проверку что в переменную что то пришло или то что пришло равно: null
				if ($field || $field === null) {

					if ($field === null) {

						$fields .= "NULL,";

						continue;
					}

					// если нам необходимо присоединять (join) таблицы и структурировать
					if ($join && $join_structure) {
						// проверим на соответствие регулярному выраению (найдём определён ли псевдоним уже в $field)
						// если переменная: $field пришла уже с псевдонимом
						if (preg_match('/^(.+)?\s+as\s+(.+)/i', $field, $matches)) {
							// значит нам не надо одно и тоже поле два раза выбирать
							// если $matches подаётся на вход (3-ий параметр), то он заполняется результатами поиска. 
							// (будет содержать текст, который соответствовал полному шаблону Или будет иметь текст, 
							// который соответствовал первому захваченному подшаблону в скобках, и так далее) 
							// здесь- т.к. в регулярке две переменных в круглых скобках, в matches[1] будет храниться 
							// нормальное название поля (1-ая переменная), а в $matches[2]- его псевдоним (2-ая переменная)
							$fields .= $concat_table . $matches[1] . ' as TABLE' . $alias_table . 'TABLE_' . $matches[2] . ',';

							// иначе (т.е. если не соответствует регулярному выражению)
						} else {

							$fields .= $concat_table . $field . ' as TABLE' . $alias_table . 'TABLE_' . $field . ',';
						}
					} else {

						// применяется также для системы поиска по административной панели сайта 
						$fields .= (!preg_match('/(\([^()]*\))|(case\s+.+?\s+end)/i', $field) ? $concat_table : '') . $field . ',';
					}
				}
			}

			// если в ячейке: $set['fields'] не было поля из $id_field и нам необходимо осуществлять структуризацию джоина (т.е. $join_structure = true)
			if (!$id_field && $join_structure) {
				// в $join что то пришло (т.е. метод: createFields() вызвали из метода: createJoin())
				if ($join) {
					$fields .= $concat_table . $this->tableRows[$alias_table]['id_row']
						. ' as TABLE' . $alias_table . 'TABLE_' . $this->tableRows[$alias_table]['id_row'] . ',';
					// иначе (т.е. метод: createFields() вызвали из основного метода модели: get())
				} else {
					$fields .= $concat_table . $this->tableRows[$alias_table]['id_row'] . ',';
				}
			}
		}

		// вернётся строка с полями, в которой будут пристыкованы названия таблиц с псевдонимами 
		return $fields;
	}

	protected function createOrder($set, $table = false)
	{
		$table = ($table && (!isset($set['no_concat']) || !$set['no_concat']))
			// вызовем метод: createTableAlias(), ему на вход передадим переменную: $table и вернём то что будет 
			// находиться в ячейке: ['alias'] результирующего массива и конкатенируем точку Результат сохраним в 
			// переменной: $table (если условие выполнится) Иначе переменная: $table будет пустой
			? $this->createTableAlias($table)['alias'] . '.' : '';

		// сформируем пустую строковую переменную $order_by
		$order_by = '';

		// если $set['order'] существует и в нём есть значение
		if (isset($set['order']) && $set['order']) {
			// если order придёт как строка, тогда сделаем явное приведение типов и строка попадёт в нулевой элемент 
			// массива и вернётся в $set['order']
			$set['order'] = (array)$set['order'];

			$set['order_direction'] = (isset($set['order_direction']) && $set['order_direction'])
				? (array)$set['order_direction'] : ['ASC'];

			// что бы каждый раз не делать проверку пришло ли что-нибудь в переменную $order_by (пусто или нет), сразу занесём в неё строку: ORDER BY 
			$order_by = 'ORDER BY ';

			// объявим переменную $direct_count и изначально поставим в значение: ноль
			$direct_count = 0;

			// запускаем цикл foreach (перебирает массив, задаваемый с помощью $set['order'] 
			// На каждой итерации значение текущего элемента (в ячейке order из массива в переменной $set) присваивается переменной $order)
			foreach ($set['order'] as $order) {

				// проверим существует ли элемент массива order_direction с таким же порядковым номером, как элемент массива order
				// ( здесь элемент с номером ноль будет всегда (даже по умолчанию в $set['order_direction'] что то будет изначально))
				// т.е. если в элементе order_direction массива $set, есть ячейка direct_count
				if (!empty($set['order_direction'][$direct_count])) {

					// то в переменную $order_direction сохраним этот элемент массива
					// ф-ия php: strtoupper()  — Преобразует строку в верхний регистр
					$order_direction = strtoupper($set['order_direction'][$direct_count]);
					// затем увеличиваем счётчик
					$direct_count++;
				} else {
					// иначе положим (сохраним) в переменную $order_direction предыдущий элемент массива $set['order_direction']
					$order_direction = strtoupper($set['order_direction'][$direct_count - 1]);
				}

				// если переменная: $order есть в св-ве: $sqlFunc (sql-функции)
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

		// если в ячейку: where массива: set пришла строка
		if (!empty($set['where']) && is_string(($set['where']))) {
			return $instruction . ' ' . trim($set['where']);
		}

		// если в ячейку where массива $set что то пришло, проверим массив ли это и не пуст ли он
		if (!empty($set['where']) && is_array(($set['where']))) {

			// перед сохранением результата сделаем проверки: пришло ли что-нибудь и является ли это массивом (не пустым) 
			// тогда сохраняем соответствующее значение иначе значение по умолчанию (в 1-ом случае: ячейку =, во 2-ом: ячейку AND)
			$set['operand'] = (!empty($set['operand']) && is_array($set['operand'])) ? $set['operand'] : ['='];
			$set['condition'] = (!empty($set['condition']) && is_array($set['condition'])) ? $set['condition'] : ['AND'];

			$where = $instruction;

			$o_count = 0;
			$c_count = 0;

			// запускаем цикл foreach по ячейке: where (массиву в ней), массива: $set (Здесь нам нужны и ключи и значения)
			foreach ($set['where'] as $key => $item) {
				// на каждой итерации цикла добавим пробел к переменной $where (содежит инструкции (здесь: строка WHERE), 
				// поступившие на вход ф-ии: createWhere())
				$where .= ' ';

				// проверим есть ли в ячейке operand массива $set, в ячейку $o_count что то пришло
				if (!empty($set['operand'][$o_count])) {
					// то в переменную $operand сохраним то, что пришло
					$operand = $set['operand'][$o_count];
					// и делаем приращение переменной $o_count
					$o_count++;
				} else {
					// иначе сохраним предыдущее значение
					$operand = $set['operand'][$o_count - 1];
				}

				// такую же проверку делаем в массиве $set для ячейки condition (её ячейки $c_count)
				if (!empty($set['condition'][$c_count])) {
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
		if (!empty($set['join'])) {
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
					if (empty($item['type'])) {

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
					if (!empty($item['on']['table'])) {
						// сохраняем эту переменную
						$join_temp_table = $item['on']['table'];
					} else {
						// иначе присоединяем предыдущую таблицу
						$join_temp_table = $join_table;
					}

					$join .= $this->createTableAlias($join_temp_table)['alias'];



					// добавим поле таблицы, которую мы пристыковываем
					$join .= '.' . $join_fields[0] . '=' . $concatTable . '.' . $join_fields[1];

					// занесём в переменную $join_table текущую таблицу (в переменной $key), что бы следующая итерация цикла могла работать с предыдущей таблицей (в итерации- текущая)
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
						$group_condition = (!empty($item['group_condition'])) ? strtoupper($item['group_condition']) : 'AND';
					}

					$fields .= $this->createFields($item, $key, (!empty($set['join_structure'])));

					$where .= $this->createWhere($item, $key, $group_condition);
				}
			}
		}

		// ф-ия зhp: compact() — создание массива, содержащего переменные и их значения
		// Для каждого из них compact() ищет переменную с таким именем в текущей таблице символов и добавляет ее в выходной 
		//массив таким образом, что имя переменной становится ключом, а содержимое переменной становится значением для этого ключа
		return compact('fields', 'join', 'where');
	}

	// метод createInsert() будет создавать массив вставки (единичной и множественной)
	// переменная $except позволяет сбрасывать поле (для указания какие поля исключить и запрос не будет их отрабатывать)
	protected function createInsert($fields, $files, $except)
	{

		// массив $insert_arr- это ассоциативный массив, который возвращает массив с ячейками: fields (в которой хранится 
		// строка и values (в которой хранятся значения))
		// изначально он пустой
		$insert_arr = [];

		// в массив $insert_arr (его ячейку: fields) запишем скобку (открывающую)
		$insert_arr['fields'] = '(';

		// Определим пришёл в fields массив с числовыми ключами или со строковыми
		// в переменную вернём первый ключ массива: fields
		$array_type = array_keys($fields)[0];

		// если array_type является числом (здесь это означает, что массив- многомерный)
		if (is_int($array_type)) {

			// Структура запроса должна быть корректной (за это отвечают флаги, обявленные ниже)
			// объявим флаг: check_fields и поставим его в false 
			$check_fields = false;
			// объявим флаг: count_fields и инициализируем нулём
			$count_fields = 0;

			foreach ($fields as $i => $item) {
				// на каждой итерации цикла в массив $insert_arr (его ячейку: values) конкатенируем (добавим) скобку (открывающую)
				$insert_arr['values'] .= '(';

				// если ещё не посчитали количество элементов в первом элементе, который пришёл в $fields[$i] на первой итерации
				if (!$count_fields) {
					// сохраним в переменной: $count_fields, количество элементов в первом элементе (т.е. текущем, который попал на 
					// первую итерацию массива $fields в его ячеёке: $i)
					$count_fields = count($fields[$i]);
				}

				// теперь в следующие элементы массива: $fields, не будем давать возможность добавлять элементов больше, чем есть в $count_fields

				// чтобы их считать, мы должны иметь счётчик, для этого объявим пременную и поставим: $j её в ноль (на каждой итерации // она будет обнуляться)
				$j = 0;

				// запускаем цикл по массива: $item, который попал в массив: $fields его ячейку: $i
				foreach ($item as $row => $value) {

					// если что то необходимо исключить (в массив: $except что то пришло) и в массиве: $except есть поле: $row, 
					// которое надо исключить
					if ($except && in_array($row, $except)) {
						// переходим на следующую итерацию цикла
						continue;
					}

					// проверим заполнены ли у нас поля: в ячейке fields в массиве: $insert_arr (заполняются только на первой 
					// итерации и берутся из первого элемента)

					// если поля не заполнены
					if (!$check_fields) {
						// то в массив: $insert_arr (в ячейку: fields) добавим то, что пришло в ключ: $row (название поля) и поставить запятую 
						$insert_arr['fields'] .= $row . ',';
					}

					// если в функциях: sqlFunc есть полученное значение: $value
					if (in_array($value, $this->sqlFunc)) {
						$insert_arr['values'] .= $value . ',';
						// если полученное значение: $value равно строке: 'NULL' или строго равно NULL (пусто)
					} elseif ($value == 'NULL' || $value === NULL) {

						// в двойных кавычках NULL будет распознано как пустота (в одинарных нельзя, т.к. sql-сервром будет 
						// распознано как строка NULL)
						$insert_arr['values'] .= "NULL" . ',';
					} else {
						// к тому что есть в $insert_arr['values'] конкатенируем (добавлем) то что есть в переменной: value 
						// предварительно добавив слеши и заключив в одинарные кавычки и запятая в конце)
						$insert_arr['values'] .= "'" . addslashes($value) . "',";
					}

					// увеличим счётчик: $j, что бы сравнить с количеством полей: $count_fields (если равен остановим выполнение цикла)
					$j++;

					if ($j === $count_fields) {
						// выход из цикла
						break;
					}
				}

				// предусмотрим проверку при которой количесто элементов: $j в последующих элементах меньше количества элементов: 
				// $count_fields в первом элементе, который попал на первую итерацию массива $fields
				if ($j < $count_fields) {
					// заполним эти поля "NULL", т.е пусто и запятая в конце
					for (; $j < $count_fields; $j++) {
						$insert_arr['values'] .= "NULL" . ',';
					}
				}

				// ф-ия php: rtrim () обрежет пробел в конце строки (в $insert_arr['values']), поданной на вход 1-ым параметром или // символ (здесь- ,), указанный 2-ым параметром
				// и в конце конкатенируем закрывающую скобку и запятую
				$insert_arr['values'] = rtrim($insert_arr['values'], ',') . '),';

				// если по окончании итерации цикла $check_fields = false (значит мы прошли первый элемент массива (в $insert_arr))
				if (!$check_fields) {
					// то поля (в $insert_arr['fields']) больше заполнять не надо
					$check_fields = true;
				}
			}

			// иначе (пришёл массив со строковым ключём)
		} else {

			// в массив $insert_arr (его ячейку: values) запишем скобку (открывающую)
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

					// добавим поле $row в ячейку fields (в массиве $insert_arr) и в конце запятую
					$insert_arr['fields'] .= $row . ',';

					// проверим является ли массивом то что пришло в переменную $file
					// если массив
					if (is_array($file)) {
						// то  этот массив преобразуем в JSON-строку

						// ф-я php: json_encode()- Возвращает строку, содержащую JSON-представление предоставленной платформы: $files
						// (формирует JSON-строку т.е. здесь- представляет массив (поданный на вход) в строковом виде, чтобы 
						// сохранить его в базе данных)
						// затем обработаем результат работы ф-ии json_encode() одинарными кавычками, а также зкранируем слешами 
						// (addslashes(json_encode($files)) и запятая в конце
						$insert_arr['values'] .= "'" . addslashes(json_encode($files)) . "',";
						// иначе 
					} else {
						// запишем как строку (обработаем $file одинарными кавычками, а также экранируем слешами (addslashes ($file)) и запятая в конце)
						$insert_arr['values'] .= "'" . addslashes($file) . "',";
					}
				}
			}

			// у содержимого массива: $insert_arr и его ячейки: values обрежем запятую в конце и конкатенируем закрывающую скобку
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

	// метод который будет структурировать данные в выборке из БД
	protected function joinStructure($res, $table)
	{
		// объявим путой по умолчанию массив
		$join_arr = [];
		$id_row = $this->tableRows[$this->createTableAlias($table)['alias']]['id_row'];

		foreach ($res as $value) {
			if ($value) {
				// если не существует ячейка: $join_arr[$value[$id_row]]
				if (!isset($join_arr[$value[$id_row]])) {
					// то создадим её и объявим, что это массив
					$join_arr[$value[$id_row]] = [];
				}

				// запускаем цикл по $value, в котором хранятся поля которые пришли из БД
				foreach ($value as $key => $item) {

					// если в $key находим шаблон (регулярное выражение) 
					// в ф-ию: preg_match() 3-им параметром передаём массив вхождений: $matches
					// (если такая ячейка есть, значит это сджойненная таблица)
					if (preg_match('/TABLE(.+)?TABLE/u', $key, $matches)) {
						// то получим нормализованное имя таблицы, которое будет равняться ячейке массива: $matches[1] в 
						// которую прийдёт: 1-ая переменная: .+ (т.е. то что в шаблоне заключено в круглые скобки) 
						// (а в ячейку: $matches[0] придёт вхождение всей подстроки, соответствующей указанному шаблону, а // именно TABLE(.+)?TABLE)
						$table_name_normal = $matches[1];

						// если в таблице которую мы джойним ($table_name_normal) нет мультиключей, т.е. составных (multi_id_row),а  пришёл нормальный первичный ключ (id_row)
						if (!isset($this->tableRows[$table_name_normal]['multi_id_row'])) {
							// получим значение первичного ключа сджойненной таблицы в переменную $join_id_row
							// запись обращения к ячейки с первичным ключём образована исходя из того как формировались 
							// поля для выборки из таблицы в методе: createFields() Там между 2-я словами: TABLE хранится 
							// имя таблицы, а дальше после нижнего подчёркивания хранится 
							// идентификатор (первичный ключ): $this->tableRows [$table_name_normal]['id_row']]
							// (поэтому мы можем обратиться к имени таблицы, а затем к значению, которое лежит в поле с 
							// первичным ключём)
							$join_id_row = $value[$matches[0] . '_' . $this->tableRows[$table_name_normal]['id_row']];
							// иначе (т.е. мультиключи есть)
						} else {
							// в переменную положим пустую строку
							$join_id_row = '';

							foreach ($this->tableRows[$table_name_normal]['multi_id_row'] as $multi) {
								// наберём первичный ключ сджойненной таблицы как составную строку
								$join_id_row .= $value[$matches[0] . '_' . $multi];
							}
						}

						// получим чистый ряд (точное название поля, которое хранится после нижнего подчёркивания)
						// ищем шаблон: TABLE(.+)TABLE_, заменяем на пустую строку, ищем в переменной: $key
						$row = preg_replace('/TABLE(.+)TABLE_/u', '', $key);

						// проверим: если у нас первичный ключ сджойненной таблицы (в $join_id_row) есть и не существует 
						// такое поле в результирующем массиве (что бы его не продублировать)
						if ($join_id_row && !isset($join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row])) {
							// создадим  ячейку результирующего массива и в неё сохраним значение из $item
							$join_arr[$value[$id_row]]['join'][$table_name_normal][$join_id_row][$row] = $item;
						}

						continue;
					}

					// если мы работаем с несджойненной (не присоединённой) таблицей
					$join_arr[$value[$id_row]][$key] = $item;
				}
			}
		}

		return $join_arr;
	}

	// метод, для создания алиасов таблиц
	protected function createTableAlias($table)
	{
		$arr = [];

		// в регулярном выражении ищем пробел один или более раз
		// если есть пробел в таблице ( в $table)
		if (preg_match('/\s+/i', $table)) {
			// Ищем символ пробела в переменной: $table, встречающегося 2-а или более раз и заменяем его на один пробел и сохраняем в переменной: $table
			$table = preg_replace('/\s{2,}/i', ' ', $table);
			// разбиваем строку (из $table) на массив строк по заданному разделителю (пробелу) и сохраняем в переменной: $table_name
			$table_name = explode(' ', $table);

			// trim()— удаление пробелов (или других символов, если они указаны 2-ым параметром) из начала и конца строки
			$arr['table'] = trim($table_name[0]);
			$arr['alias'] = trim($table_name[1]);
		} else {
			$arr['alias'] = $arr['table'] = $table;
		}

		return $arr;
	}
}
