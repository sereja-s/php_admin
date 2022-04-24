<?php

namespace core\admin\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;
use core\base\exceptions\RouteException;
use core\base\settings\Settings;
use libraries\FileEdit;

// класс будт отвечать за сборку шаблона (шапку и подвал сайта)
abstract class BaseAdmin extends BaseController
{
	// свойство через которое мы будем обращаться и вызывать методы модели
	protected $model;

	// свойство показывает какую таблицу данных подключить
	protected $table;
	protected $columns;
	// свойство для хранения основных данных в админке
	protected $foreignData;

	protected $adminPath;

	// определим переменную в которую придёт боковое меню
	protected $menu;
	// переменная для заголовка страницы
	protected $title;

	protected $alias;
	protected $fileArray;

	protected $messages;
	protected $settings;

	protected $translate;
	protected $blocks = [];

	protected $templateArr;
	protected $formTemplates;
	protected $noDelete;

	protected function inputData()
	{

		if (!MS_MODE) {
			if (preg_match('/msie|trident.+?rv\s*:/i', $_SERVER['HTTP_USER_AGENT'])) {
				exit('Вы используете устаревшую версию браузера. Пожалуйста обновитесь до актуальной версии');
			}
		}

		$this->checkAuth(true);

		// выполним инициализацию скриптов и стилей
		// вызовем сответствующий метод с ключём true, чтобы понять,что это административная панель
		$this->init(true);
		// укажем в переменной необходимое нам название (заголовок) страницы
		$this->title = 'VG engine';


		// Получим и созраним в переменной model объект модели, чтобы дальше могли ей пользоваться
		// сначала сделаем проверку: если никто раньше (до вызова этого метода) эту модель не установил
		if (!$this->model) {
			// обратимся к классу Model (использует шаблон Singleton) и вызовем его статический метод instance() 
			// Результат работы метода (а именно объект модели) сохраним в переменной: model
			$this->model = Model::instance();
		}

		// аналогично проверяем меню
		if (!$this->menu) {
			// то обратимся к классу Settings и вывзовем его статический метод get(), передав на вход название свойства: projectTables Результат сохраним в свойстве: menu
			$this->menu = Settings::get('projectTables');
		}
		if (!$this->adminPath) {
			$this->adminPath = PATH . Settings::get('routes')['admin']['alias'] . '/';
		}

		if (!$this->templateArr) {
			$this->templateArr = Settings::get('templateArr');
		}
		if (!$this->formTemplates) {
			$this->formTemplates = Settings::get('formTemplates');
		}

		if (!$this->messages) {
			$this->messages = include $_SERVER['DOCUMENT_ROOT'] . PATH . Settings::get('messages') . 'informationMessages.php';
		}

		// вызовем метод, отправляющий браузеру заголовки файлов, которые не надо кешировать
		$this->sendNoCacheHeaders();
	}

	protected function outputData()
	{
		if (!$this->content) {
			$args = func_get_arg(0);
			$vars = $args ? $args : [];

			//if(!$this->template) { $this->template = ADMIN_TEMPLATE . 'show'; }

			$this->content = $this->render($this->template, $vars);
		}

		$this->header = $this->render(ADMIN_TEMPLATE . 'include/header');
		$this->footer = $this->render(ADMIN_TEMPLATE . 'include/footer');

		return $this->render(ADMIN_TEMPLATE . 'layout/default');
	}

	// метод отправляющий ответы (заголоки) браузеру (заголовки файлов которые не надо кешировать)
	protected function sendNoCacheHeaders()
	{
		// испоьзуем ф-ию header() для отправки заголовка (поданного на вход) браузеру

		// формируем заголовок ответа последней модификации контента: название- Last-Modified:, пробел, далее конкатенируем 
		// результат работы ф-ии: gmdate(), которая вернёт отформатированные по гринвичу дату и время (поданные на вход), затем 
		// указываем пробел и строку: GMT
		header("Last-Modified: " . gmdate("D, d m Y H:i:s") . " GMT");
		// формируем заголовок, который принуждает модуль браузера, отвечающий за кеш, отправлять запрос на наш сервер каждый раз
		// для валидации тех данных, которые в этом кеше хранятся
		header("Cache-Control: no-cache, must-revalidate");
		// в заголовке укажем браузеру максимальный период свежести контента (у нас значение ноль, т.е. браузер будет понимать, 
		// что надо загрузить контент с нашего сервера, а не показать кешированный)
		header("Cache-Control: max-age=0");
		// заголовок для браузера Internet Explorer (что бы загружал контент с нашего сервера, а не показывал кешированный)
		header("Cache-Control: post-check=0, pre-check=0");
	}

	// метод, который быдет вызывать метод inputData() своего класса (BaseAdmin)
	protected function execBase()
	{
		self::inputData();
	}

	// метод, который заполняет свойства (здесь- table(таблицы))
	// (определяет с какой таблицы брать данные и выбирает колонки(поля) из данной таблицы)
	protected function createTableData($settings = false)
	{
		// если свойство table нигде не было заполнено
		if (!$this->table) {

			// проверим есть ли что то в свойстве parameters
			if ($this->parameters) {

				// то в свойство table запишем массив ключей, которые вернёт ф-ия php: array_keys() из поданного ей на вход массива
				// (здесь- вернуть нужно только нулевой элемент массива ключей)
				$this->table = array_keys($this->parameters)[0];

				// иначе
			} else {
				if (!$settings) {
					$settings = Settings::instance();
				}

				// то в св-во table получим свойство из настроек: defaultTable, что бы понимать из какой таблицы загрузятся 
				//данные по умолчанию (если в контроллер ничего не пришло)
				$this->table = $settings::get('defaultTable');
			}
		}

		// нам нужны поля из базы данных
		// в св-ве columns сохраним результат работы метода showColumns(), на вход которого подаём таблицу, поля которой нам нужны
		$this->columns = $this->model->showColumns($this->table);

		// если поля не пришли из БД
		if (!$this->columns) {
			// выбросим исключение
			new RouteException('Не найдены поля в таблице: ' . $this->table, 2);
		}
	}

	// метод для рабоы с расширениями (на вход подаём массив аргументов (как пустой массив): $args = [] и свойство settings со значением по умолчанию: false)
	protected function expansion($args = [], $settings = false)
	{
		// в массив: $filename сохраним результат работы ф-ии php: explode(), которая разделит строку (имя, поданной на вход 
		// таблицы: $this->table), по заданному разделителю, также поданного на вход 1-ым параметром (здесь- _ )
		$filename = explode('_', $this->table);
		// объявим переменную $className и запишем в неё пустую строку
		$className = '';

		// пробежимся по полученному массиву: $filename
		foreach ($filename as $item) {
			// и в переменную $className добавим результат работы ф-ии php: ucfirst(), которая делает первый символ, полученной 
			// на вход строки заглавным (т.е. получили имя класса)
			$className .= ucfirst($item);
		}

		if (!$settings) {
			// в переменной: $path сохраним результат работы статического метода: get(), класса: Settings (т.е. получим свойство: expansion, название которого поданно на вход)
			$path = Settings::get('expansion');
		} else if (is_object($settings)) {
			$path = $settings::get('expansion');
		} else {
			$path = $settings;
		}

		// запишем в переменную: $class полное имя класса (вместе с namespace)
		$class = $path . $className . 'Expansion';

		// проверим ф-ей php: is_readable() читается ли файл, название которого подано (сформировано) в виде строки на входе 
		// (т.е. укажем полный путь) на входе
		if (is_readable($_SERVER['DOCUMENT_ROOT'] . PATH . $class . '.php')) {

			// в переменой $class сохраним результат работы ф-ии php: str_replace(), которая в переменной: $class, поданной на 
			// вход ищет символ: / и заменяет его на символом: \ (экранированным в параметре ф-ии символом: \)
			$class = str_replace('/', '\\', $class);

			// отработаем этот класс по шаблону Singleton (шаблон проектирования, гарантирующий, что в однопоточном приложении  
			// будет единственный экземпляр некоторого класса), используя метод: instance() Результат работы сохраним в переменной: $exp
			$exp = $class::instance();

			foreach ($this as $name => $value) {
				$exp->$name = &$this->$name;
			}

			// объект хранится в переменной: $exp, у него вызовем метод: expansion() (он описан в классе: TeachersExpansion)
			// на вход пердадим массив аргументов: $args
			return $exp->expansion($args);
		} else {
			$file = $_SERVER['DOCUMENT_ROOT'] . PATH . $path . $this->table . '.php';

			extract($args);

			if (is_readable($file)) {
				return include $file;
			}
		}
		return false;
	}

	protected function createOutputData($settings = false)
	{
		if (!$settings) {
			$settings = Settings::instance();
		}

		$blocks = $settings::get('blockNeedle');
		$this->translate = $settings::get('translate');

		if (!$blocks || !is_array($blocks)) {
			foreach ($this->columns as $name => $item) {
				if ($name === 'id_row') {
					continue;
				}
				if (!$this->translate[$name]) {
					$this->translate[$name][] = $name;
				}
				$this->blocks[0][] = $name;
			}
			return;
		}

		$default = array_keys($blocks)[0];

		foreach ($this->columns as $name => $item) {
			if ($name === 'id_row') {
				continue;
			}

			$insert = false;

			foreach ($blocks as $block => $value) {
				if (!array_key_exists($block, $this->blocks)) {
					$this->blocks[$block] = [];
				}
				if (in_array($name, $value)) {
					$this->blocks[$block][] = $name;
					$insert = true;
					break;
				}
			}

			if (!$insert) {
				$this->blocks[$default][] = $name;
			}
			if (!$this->translate[$name]) {
				$this->translate[$name][] = $name;
			}
		}

		return;
	}

	protected function createRadio($settings = false)
	{
		if (!$settings) {
			$settings = Settings::instance();
		}

		$radio = $settings::get('radio');

		if ($radio) {
			foreach ($this->columns as $name => $item) {
				if ($radio[$name]) {
					$this->foreignData[$name] = $radio[$name];
				}
			}
		}
	}

	protected function checkPost($settings = false)
	{
		if ($this->isPost()) {
			$this->clearPostFields($settings);
			$this->table = $this->clearStr($_POST['table']);
			unset($_POST['table']);

			if ($this->table) {
				$this->createTableData($settings);
				$this->editData();
			}
		}
	}

	protected function addSessionData($arr = [])
	{
		if (!$arr) {
			$arr = $_POST;
		}

		foreach ($arr as $key => $item) {
			$_SESSION['res'][$key] = $item;
		}

		$this->redirect();
	}

	protected function countChar($str, $counter, $answer, $arr)
	{
		if (mb_strlen($str) > $counter) {
			$str_res = mb_str_replace('$1', $answer, $this->messages['count']);
			$str_res = mb_str_replace('$2', $counter, $str_res);

			$_SESSION['res']['answer'] = '<div class="error">' . $str_res . '</div>';
			$this->addSessionData($arr);
		}
	}

	protected function emptyFields($str, $answer, $arr = [])
	{
		if (empty($str)) {
			$_SESSION['res']['answer'] = '<div class="error">' . $this->messages['empty'] . ' ' . $answer . '</div>';
			$this->addSessionData($arr);
		}
	}

	protected function clearPostFields($settings, &$arr = [])
	{
		if (!$arr) {
			$arr = &$_POST;
		}
		if (!$settings) {
			$settings = Settings::instance();
		}

		$id = $_POST[$this->columns['id_row']] ?: false;

		$validate = $settings::get('validation');
		if (!$this->translate) {
			$this->translate = $settings::get('translate');
		}

		foreach ($arr as $key => $item) {
			if (is_array($item)) {
				$this->clearPostFields($settings, $item);
			} else {
				if (is_numeric($item)) {
					$arr[$key]  = $this->clearNum($item);
				}

				if ($validate) {
					if ($validate[$key]) {
						if ($this->translate[$key]) {
							$answer = $this->translate[$key][0];
						} else {
							$answer = $key;
						}

						if ($validate[$key]['crypt']) {
							if ($id) {
								if (empty($item)) {
									unset($arr[$key]);
									continue;
								}

								$arr[$key] = md5($item);
							}
						}

						if ($validate[$key]['empty']) {
							$this->emptyFields($item, $answer, $arr);
						}

						if ($validate[$key]['trim']) {
							$arr[$key] = trim($item);
						}

						if ($validate[$key]['int']) {
							$arr[$key] = $this->clearNum($item);
						}

						if ($validate[$key]['count']) {
							$this->countChar($item, $validate[$key]['count'], $answer, $arr);
						}
					}
				}
			}
		}

		return true;
	}

	protected function editData($returnId = false)
	{
		$id = false;
		$method = 'add';

		if (!empty($_POST['return_id'])) {
			$returnId = true;
		}

		if ($_POST[$this->columns['id_row']]) {
			$id = is_numeric($_POST[$this->columns['id_row']]) ?
				$this->clearNum($_POST[$this->columns['id_row']]) :
				$this->clearStr($_POST[$this->columns['id_row']]);

			if ($id) {
				$where = [$this->columns['id_row'] => $id];
				$method = 'edit';
			}
		}

		foreach ($this->columns as $key => $item) {
			if ($key === 'id_row') continue;
			if ($item['Type'] === 'date' || $item['Type'] === 'datetime') {
				!$_POST[$key] && $_POST[$key] = 'NOW()';
			}
		}

		$this->createFiles($id);

		$this->createAlias($id);

		$this->updateMenuPosition($id);

		$except = $this->checkExceptFields();

		$res_id = $this->model->$method($this->table, [
			'files' => $this->fileArray,
			'where' => $where,
			'return_id' => true,
			'except' => $except
		]);

		if (!$id && $method === 'add') {
			$_POST[$this->columns['id_row']] = $res_id;
			$answerSuccess = $this->messages['addSuccess'];
			$answerFail = $this->messages['addFail'];
		} else {
			$answerSuccess = $this->messages['editSuccess'];
			$answerFail = $this->messages['editFail'];
		}

		$this->checkManyToMany();

		$this->expansion(get_defined_vars());

		$result = $this->checkAlias($_POST[$this->columns['id_row']]);

		if ($res_id) {
			$_SESSION['res']['answer'] = '<div class="success">' . $answerSuccess . '</div>>';

			if (!$returnId) {
				$this->redirect();
			}

			return $_POST[$this->columns['id_row']];
		} else {
			$_SESSION['res']['answer'] = '<div class="error">' . $answerFail . '</div>>';

			if (!$returnId) {
				$this->redirect();
			}
		}
	}

	protected function checkExceptFields($arr = [])
	{
		if (!$arr) {
			$arr = $_POST;
		}

		$except = [];

		if ($arr) {
			foreach ($arr as $key => $item) {
				if (!$this->columns[$key]) {
					$except[] = $key;
				}
			}
		}

		return $except;
	}

	protected function createFiles($id)
	{
		$fileEdit = new FileEdit();
		$this->fileArray = $fileEdit->addFile($this->table);

		if ($id) {
			$this->checkFiles($id);
		}

		if (!empty($_POST['js-sorting']) && $this->fileArray) {
			foreach ($_POST['js-sorting'] as $key => $item) {
				if (!empty($item) && !empty($this->fileArray[$key])) {
					$fileArr = json_decode($item);

					if ($fileArr) {
						$this->fileArray[$key] = $this->sortingFiles($fileArr, $this->fileArray[$key]);
					}
				}
			}
		}
	}

	protected function sortingFiles($fileArr, $arr)
	{
		$res = [];

		foreach ($fileArr as $file) {
			if (!is_numeric($file)) {
				$file = substr($file, strlen(PATH . UPLOAD_DIR));
			} else {
				$file = $arr[$file];
			}

			if ($file && in_array($file, $arr)) {
				$res[] = $file;
			}
		}

		return $res;
	}

	protected function createAlias($id = false)
	{
		if ($this->columns['alias']) {
			if (!$_POST['alias']) {
				if ($_POST['name']) {
					$alias_str = $this->clearStr($_POST['name']);
				} else {
					foreach ($_POST as $key => $item) {
						if (strpos($key, 'name') !== false && $item) {
							$alias_str = $this->clearStr($item);
							break;
						}
					}
				}
			} else {
				$alias_str = $_POST['alias'] = $this->clearStr($_POST['alias']);
			}

			$textModify = new \libraries\TextModify();
			$alias = $textModify->translit($alias_str);

			$where['alias'] = $alias;
			$operand[] = '=';

			if ($id) {
				$where[$this->columns['id_row']] = $id;
				$operand[] = '<>';
			}

			$res_alias = $this->model->get($this->table, [
				'fields' => ['alias'],
				'where' => $where,
				'operand' => $operand,
				'limit' => '1'
			])[0];

			if (!$res_alias) {
				$_POST['alias'] = $alias;
			} else {
				$this->alias = $alias;
				$_POST['alias'] = '';
			}

			if ($_POST['alias'] && $id) {
				method_exists($this, 'checkOldAlias') && $this->checkOldAlias($id);
			}
		}
	}

	protected function updateMenuPosition($id = false)
	{
		if (isset($_POST['menu_position'])) {
			$where = false;

			if ($id && $this->columns['id_row']) {
				$where = [$this->columns['id_row'] => $id];
			}

			if (array_key_exists('parent_id', $_POST)) {
				$this->model->updateMenuPosition($this->table, 'menu_position', $where, $_POST['menu_position'], ['where' => 'parent_id']);
			} else {
				$this->model->updateMenuPosition($this->table, 'menu_position', $where, $_POST['menu_position']);
			}
		}
	}

	protected function checkAlias($id)
	{
		if ($id) {
			if ($this->alias) {
				$this->alias .= '-' . $id;

				$this->model->edit($this->table, [
					'fields' => ['alias' => $this->alias],
					'where' => [$this->columns['id_row'] => $id]
				]);

				return true;
			}
		}

		return false;
	}

	protected function createOrderData($table)
	{
		$columns = $this->model->showColumns($table);

		if (!$columns) {
			throw new RouteException('Отсутствуют поля в таблице ' . $table);
		}

		$name = '';
		$order_name = '';

		if ($columns['name']) {
			$order_name = $name = 'name';
		} else {
			foreach ($columns as $key => $value) {
				if (strpos($key, 'name') !== false) {
					$order_name = $key;
					$name = $key . ' as name';
				}
			}
			if (!$name) {
				$name = $columns['id_row'] . ' as name';
			}
		}

		$parent_id = '';
		$order = [];

		if ($columns['parent_id']) {
			$order[] = $parent_id = 'parent_id';
		}

		if ($columns['menu_position']) {
			$order[] = 'menu_position';
		} else {
			$order[] = $order_name;
		}

		return compact('name', 'parent_id', 'order', 'columns');
	}

	protected function createManyToMany($settings = false)
	{
		if (!$settings) {
			$settings = $this->settings ?: Settings::instance();
		}

		$manyToMany = $settings::get('manyToMany');
		$blocks = $settings::get('blockNeedle');

		if ($manyToMany) {
			foreach ($manyToMany as $mTable => $tables) {
				$targetKey = array_search($this->table, $tables);

				if ($targetKey !== false) {
					$otherKey = $targetKey ? 0 : 1;

					$checkBoxList = $settings::get('templateArr')['checkboxlist'];

					if (!$checkBoxList || !in_array($tables[$otherKey], $checkBoxList)) {
						continue;
					}

					if (!$this->translate[$tables[$otherKey]]) {
						if ($settings::get('projectTables')[$tables[$otherKey]]) {
							$this->translate[$tables[$otherKey]] = [$settings::get('projectTables')[$tables[$otherKey]]['name']];
						}
					}

					$orderData = $this->createOrderData($tables[$otherKey]);

					$insert = false;

					if ($blocks) {
						foreach ($blocks as $key => $item) {
							if (in_array($tables[$otherKey], $item)) {
								$this->blocks[$key][] = $tables[$otherKey];
								$insert = true;
								break;
							}
						}
					}

					if (!$insert) {
						$this->blocks[array_keys($this->blocks)[0]][] = $tables[$otherKey];
					}

					$foreign = [];

					if ($this->data) {
						$res = $this->model->get($mTable, [
							'fields' => [$tables[$otherKey] . '_' . $orderData['columns']['id_row']],
							'where' => [$this->table . '_' . $this->columns['id_row'] => $this->data[$this->columns['id_row']]]
						]);

						if ($res) {
							foreach ($res as $item) {
								$foreign[] = $item[$tables[$otherKey] . '_' . $orderData['columns']['id_row']];
							}
						}
					}

					if (isset($tables['type'])) {
						$data = $this->model->get($tables[$otherKey], [
							'fields' => [
								$orderData['columns']['id_row'] . ' as id',
								$orderData['name'],
								$orderData['parent_id']
							],
							'order' => $orderData['order']
						]);

						if ($data) {
							$this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['name'] = 'Выбрать';

							foreach ($data as $item) {
								if ($tables['type'] === 'root' && $orderData['parent_id']) {
									if ($item[$orderData['parent_id']] === null) {
										$this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;
									}
								} elseif ($tables['type'] === 'child' && $orderData['parent_id']) {
									if ($item[$orderData['parent_id']] !== null) {
										$this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;
									}
								} else {
									$this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;
								}

								if (in_array($item['id'], $foreign)) {
									$this->data[$tables[$otherKey]][$tables[$otherKey]][] = $item['id'];
								}
							}
						}
					} elseif ($orderData['parent_id']) {
						$parent = $tables[$otherKey];

						$keys = $this->model->showForeignKeys($tables[$otherKey]);

						if ($keys) {
							foreach ($keys as $item) {
								if ($item['COLUMN_NAME'] === 'parent_id') {
									$parent = $item['REFERENCED_TABLE_NAME'];
									break;
								}
							}
						}

						if ($parent === $tables[$otherKey]) {
							$data = $this->model->get($tables[$otherKey], [
								'fields' => [
									$orderData['columns']['id_row'] . ' as id',
									$orderData['name'],
									$orderData['parent_id']
								],
								'order' => $orderData['order']
							]);

							if ($data) {
								while (($key = key($data)) !== null) {
									if (!$data[$key]['parent_id']) {
										$this->foreignData[$tables[$otherKey]][$data[$key]['id']]['name'] = $data[$key]['name'];
										unset($data[$key]);
										reset($data);
										continue;
									} else {
										if ($this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]]) {
											$this->foreignData[$tables[$otherKey]][$data[$key][$orderData['parent_id']]]['sub'][$data[$key]['id']] = $data[$key];

											if (in_array($data[$key]['id'], $foreign)) {
												$this->data[$tables[$otherKey]][$data[$key][$orderData['parent_id']]][] = $data[$key]['id'];
											}

											unset($data[$key]);
											reset($data);
											continue;
										} else {
											foreach ($this->foreignData[$tables[$otherKey]] as $id => $item) {
												$parent_id = $data[$key][$orderData['parent_id']];

												if (isset($item['sub']) && $item['sub'] && isset($item['sub'][$parent_id])) {
													$this->foreignData[$tables[$otherKey]][$id]['sub'][$data[$key]['id']] = $data[$key];

													if (in_array($data[$key]['id'], $foreign)) {
														$this->data[$tables[$otherKey]][$id][] = $data[$key]['id'];
													}

													unset($data[$key]);
													reset($data);
													continue 2;
												}
											}
										}

										next($data);
									}
								}
							}
						} else {
							$parentOrderData = $this->createOrderData($parent);

							$data = $this->model->get($parent, [
								'fields' => [$parentOrderData['name']],
								'join' => [
									$tables[$otherKey] => [
										'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name']],
										'on' => [$parentOrderData['columns']['id_row'], $orderData['parent_id']]
									]
								],
								'join_structure' => true
							]);

							foreach ($data as $key => $item) {
								if (isset($item['join'][$tables[$otherKey]]) && $item['join'][$tables[$otherKey]]) {
									$this->foreignData[$tables[$otherKey]][$key]['name'] = $item['name'];
									$this->foreignData[$tables[$otherKey]][$key]['sub'] = $item['join'][$tables[$otherKey]];

									foreach ($item['join'][$tables[$otherKey]] as $value) {
										if (in_array($value['id'], $foreign)) {
											$this->data[$tables[$otherKey]][$key][] = $value['id'];
										}
									}
								}
							}
						}
					} else {
						$data = $this->model->get($tables[$otherKey], [
							'fields' => [$orderData['columns']['id_row'] . ' as id', $orderData['name'], $orderData['parent_id']],
							'order' => $orderData['order']
						]);

						if ($data) {
							$this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['name'] = 'Выбрать';

							foreach ($data as $item) {
								$this->foreignData[$tables[$otherKey]][$tables[$otherKey]]['sub'][] = $item;

								if (in_array($item['id'], $foreign)) {
									$this->data[$tables[$otherKey]][$tables[$otherKey]][] = $item['id'];
								}
							}
						}
					}
				}
			}
		}
	}

	protected function checkManyToMany($settings = false)
	{
		if (!$settings) {
			$settings = $this->settings ?: Settings::instance();
		}
		$manyToMany = $settings::get('manyToMany');

		if ($manyToMany) {
			foreach ($manyToMany as $mTable => $tables) {
				$targetKey = array_search($this->table, $tables);

				if ($targetKey !== false) {
					$otherKey = $targetKey ? 0 : 1;

					$checkboxlist = $settings::get('templateArr')['checkboxlist'];

					if (!$checkboxlist || !in_array($tables[$otherKey], $checkboxlist)) {
						continue;
					}

					$columns = $this->model->showColumns($tables[$otherKey]);

					$targetRow = $this->table . '_' . $this->columns['id_row'];

					$otherRow = $tables[$otherKey] . '_' . $columns['id_row'];

					$this->model->delete($mTable, [
						'where' => [$targetRow => $_POST[$this->columns['id_row']]]
					]);

					if ($_POST[$tables[$otherKey]]) {
						$insertArr = [];
						$i = 0;

						foreach ($_POST[$tables[$otherKey]] as $value) {
							foreach ($value as $item) {
								if ($item) {
									$insertArr[$i][$targetRow] = $_POST[$this->columns['id_row']];
									$insertArr[$i][$otherRow] = $item;

									$i++;
								}
							}
						}

						if ($insertArr) {
							$this->model->add($mTable, [
								'fields' => $insertArr
							]);
						}
					}
				}
			}
		}
	}

	protected function createForeignProperty($arr, $rootItems)
	{
		if (in_array($this->table, $rootItems['tables'])) {
			$this->foreignData[$arr['COLUMN_NAME']][0]['id'] = 'NULL';
			$this->foreignData[$arr['COLUMN_NAME']][0]['name'] = $rootItems['name'];
		}

		$orderData = $this->createOrderData($arr['REFERENCED_TABLE_NAME']);

		if ($this->data) {
			if ($arr['REFERENCED_TABLE_NAME'] === $this->table) {
				$where[$this->columns['id_row']] = $this->data[$this->columns['id_row']];
				$operand[] = '<>';
			}
		}

		$foreign = $this->model->get($arr['REFERENCED_TABLE_NAME'], [
			'fields' => [
				$arr['REFERENCED_COLUMN_NAME'] . ' as id',
				$orderData['name'],
				$orderData['parent_id']
			],
			'where' => $where,
			'operand' => $operand,
			'order' => $orderData['order']
		]);

		if ($foreign) {
			if ($this->foreignData[$arr['COLUMN_NAME']]) {
				foreach ($foreign as $value) {
					$this->foreignData[$arr['COLUMN_NAME']][] = $value;
				}
			} else {
				$this->foreignData[$arr['COLUMN_NAME']] = $foreign;
			}
		}
	}

	protected function createForeignData($settings = false)
	{
		if (!$settings) {
			$settings = Settings::instance();
		}

		$rootItems = $settings::get('rootItems');

		$keys = $this->model->showForeignKeys($this->table);

		if ($keys) {
			foreach ($keys as $item) {
				$this->createForeignProperty($item, $rootItems);
			}
		} elseif ($this->columns['parent_id']) {
			$arr['COLUMN_NAME'] = 'parent_id';
			$arr['REFERENCED_COLUMN_NAME'] = $this->columns['id_row'];
			$arr['REFERENCED_TABLE_NAME'] = $this->table;

			$this->createForeignProperty($arr, $rootItems);
		}

		return;
	}

	protected function createMenuPosition($settings = false)
	{

		if ($this->columns['menu_position']) {
			if (!$settings) {
				$settings = Settings::instance();
			}

			$rootItems = $settings::get('rootItems');

			if ($this->columns['parent_id']) {
				if (in_array($this->table, $rootItems['tables'])) {
					$where = 'parent_id IS NULL OR parent_id = 0';
				} else {
					$parent = $this->model->showForeignKeys($this->table, 'parent_id')[0];

					if ($parent) {
						if ($this->table === $parent['REFERENCED_TABLE_NAME']) {
							$where = 'parent_id IS NULL OR parent_id = 0';
						} else {
							$columns = $this->model->showColumns($parent['REFERENCED_TABLE_NAME']);

							if ($columns['parent_id']) {
								$order[] = 'parent_id';
							} else {
								$order[] = $parent['REFERENCED_COLUMN_NAME'];
							}

							$id = $this->model->get($parent['REFERENCED_TABLE_NAME'], [
								'fields' => [$parent['REFERENCED_COLUMN_NAME']],
								'order' => $order,
								'limit' => '1'
							])[0][$parent['REFERENCED_COLUMN_NAME']];

							if ($id) {
								$where = ['parent_id' => $id];
							}
						}
					} else {
						$where = 'parent_id IS NULL OR parent_id = 0';
					}
				}
			}

			$menu_pos = $this->model->get($this->table, [
				'fields' => ['COUNT(*) as count'],
				'where' => $where,
				'no_concat' => true
			])[0]['count'] + (int)!$this->data;

			for ($i = 1; $i <= $menu_pos; $i++) {
				$this->foreignData['menu_position'][$i - 1]['id'] = $i;
				$this->foreignData['menu_position'][$i - 1]['name'] = $i;
			}
		}

		return;
	}

	protected function checkOldAlias($id)
	{
		$tables = $this->model->showTables();

		if (in_array('old_alias', $tables)) {
			$old_alias = $this->model->get($this->table, [
				'fields' => ['alias'],
				'where' => [$this->columns['id_row'] => $id]
			])[0]['alias'];

			if ($old_alias && $old_alias !== $_POST['alias']) {
				$this->model->delete('old_alias', [
					'where' => ['alias' => $old_alias, 'table_name' => $this->table]
				]);

				$this->model->delete('old_alias', [
					'where' => ['alias' => $_POST['alias'], 'table_name' => $this->table]
				]);

				$this->model->add('old_alias', [
					'fields' => ['alias' => $old_alias, 'table_name' => $this->table, 'table_id' => $id]
				]);
			}
		}
	}

	protected function checkFiles($id)
	{
		if ($id) {
			$arrKeys = [];

			if (!empty($this->fileArray)) {
				$arrKeys = array_keys($this->fileArray);
			}

			if (!empty($_POST['js-sorting'])) {
				$arrKeys = array_merge($arrKeys, array_keys($_POST['js-sorting']));
			}

			if ($arrKeys) {
				$arrKeys = array_unique($arrKeys);

				$data = $this->model->get($this->table, [
					'fields' => $arrKeys,
					'where' => [$this->columns['id_row'] => $id]
				]);

				if ($data) {
					$data = $data[0];

					foreach ($data as $key => $item) {
						if ((!empty($this->fileArray[$key]) && is_array($this->fileArray[$key])) || !empty($_POST['js-sorting'][$key])) {
							$fileArr = json_decode($item);

							if ($fileArr) {
								foreach ($fileArr as $file) {
									$this->fileArray[$key][] = $file;
								}
							}
						} elseif (!empty($this->fileArray[$key])) {
							@unlink($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $item);
						}
					}
				}
			}
		}
	}
}
