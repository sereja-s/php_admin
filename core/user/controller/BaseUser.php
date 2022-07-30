<?php

namespace core\user\controller;

use core\user\model\Model;

abstract class BaseUser extends \core\base\controller\BaseController
{
	protected $model;
	protected $table;

	protected $set;
	protected $menu;

	protected $breadcrumbs;

	/*Проектные свойства*/
	protected $socials;


	protected function inputData()
	{
		// инициализируем стили и скрипты На вход здесь ничего не передаём
		$this->init();

		!$this->model && $this->model = Model::instance();


		// в переменную получим данные для шапки сайта из таблицы: settings (логотип, название сайта, телефон, эл.почта, адрес)
		$this->set = $this->model->get('settings', [
			'order' => ['id'], // сортируем по полю: id
			'limit' => 1
		]);

		// в переменной доступно, то что лежит первым по очереди
		$this->set && $this->set = $this->set[0];


		// получим в ячейку данные меню каталога (с раскрывающимся списком) в шапке сайта
		$this->menu['catalog'] = $this->model->get('catalog', [
			'where' => ['visible' => 1, 'parent_id' => null], // условие по которым выводить данные
			'order' => ['menu_position'] // сортируем по указанному полю
		]);


		// получим в ячейку информационные данные (акции и скидки, оплата и доставка, политика конф.)
		$this->menu['information'] = $this->model->get('information', [
			'where' => ['visible' => 1, 'show_top_menu' => 1],
			'order' => ['menu_position']
		]);


		// получим социальные сети из таблицы БД
		$this->socials = $this->model->get('socials', [
			'where' => ['visible' => 1],
			'order' => ['menu_position']
		]);
	}

	protected function outputData()
	{

		// в переменной сохраним результат работы ф-ии php: func_get_arg()- Возвращает указанный аргумент из списка аргументов пользовательской функции (здесь- порядковый номер: 0)
		$args = func_get_arg(0);
		$vars = $args ? $args : [];

		$this->breadcrumbs = $this->render(TEMPLATE . 'include/breadcrumbs');



		if (!$this->content) {



			//if(!$this->template) { $this->template = ADMIN_TEMPLATE . 'show'; }

			$this->content = $this->render($this->template, $vars);
		}

		$this->header = $this->render(TEMPLATE . 'include/header', $vars);
		$this->footer = $this->render(TEMPLATE . 'include/footer', $vars);

		return $this->render(TEMPLATE . 'layout/default');
	}


	// метод для удобного заполнения пути к изображению в файлах
	protected function img($img = '', $tag = false)
	{

		// если картинка отсутствует и есть папка с изображениями по умолчанию
		if (!$img && is_dir($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . DEFAULT_IMAGE_DIRECTORY)) {

			// scandir() — возвращает список файлов и каталогов внутри указанного пути
			$dir = scandir($_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . DEFAULT_IMAGE_DIRECTORY);

			// preg_grep() — возвращает записи массива, соответствующие шаблону ( или регулярному выражению)
			// в переменную: $imgArr положим то что в названии будет указывать на IndexController далее точка и какое то 
			// расширение, если такого нет, то будем искать файл с названием: default далее точка и какое то расширение
			$imgArr = preg_grep('/' . $this->getController() . '\./i', $dir) ?: preg_grep('/default\./i', $dir);

			// если в переменную: $imgArr что то пришло, то в переменную $img сохраним выражение, где 
			// array_shift()— возвращает массив поданный на вход, исключив первый элемент (все ключи числового массива будут 
			// изменены, чтобы начать отсчет с нуля)
			$imgArr && $img = DEFAULT_IMAGE_DIRECTORY . '/' . array_shift($imgArr);
		}

		if ($img) {

			$path = PATH . UPLOAD_DIR . $img;

			if (!$tag) {

				return $path;
			}

			echo '<img src="' . $path . '" alt="image" title="image">';
		}

		return '';
	}

	// метод формирования ссылок
	protected function alias($alias = '', $queryString = '')
	{

		$str = '';

		if ($queryString) {

			if (is_array($queryString)) {

				foreach ($queryString as $key => $item) {

					// к переменной: $str конкатенируем символ: знак вопроса (если в строку ничего не пришло) иначе- символ амперсанд
					$str .= (!$str ? '?' : '&');

					if (is_array($item)) {

						// к ключу конкатенируем символ квадратных скобок
						$key .= '[]';

						foreach ($item as $k => $v) {

							$str .= $key . '=' . $v . (!empty($item[$k + 1]) ? '&' : '');
						}
					} else {

						$str .= $key . '=' . $item;
					}
				}

				// иначе если в переменную: $queryString пришёл не массив
			} else {

				// проверим не пришёл ли уже знак вопроса в переменную: $queryString
				if (strpos($queryString, '?') === false) {

					$str = '?' . $str;
				}

				$str .= $queryString;
			}
		}


		if (is_array($alias)) {

			$aliasStr = '';

			foreach ($alias as $key => $item) {

				// если пришёл не числовой ключ и что то пришло в переменную: $item
				if (!is_numeric($key) && $item) {

					$aliasStr .= $key . '/' . $item . '/';

					// иначе если что то пришло в переменную: $item, но ключ числовой
				} elseif ($item) {

					$aliasStr .= $item . '/';
				}
			}

			// trim() — удаление пробелов (или других символов (здесь- символ: / )) из начала и конца строки
			$alias = trim($aliasStr, '/');
		}

		if (!$alias || $alias === '/') {

			return PATH . $str;
		}

		if (preg_match('/^\s*https?:\/\//i', $alias)) {

			return $alias . $str;
		}

		// ищем слеш повторяющийся 2-а и более раз и меняем на единичный слеш, и выводить это будем в готовом пути
		return preg_replace('/\/{2,}/', '/', PATH . $alias . END_SLASH . $str);
	}

	// метод, для автоматической подстановки слов рядом с цифрой (кол-во лет на рынке)
	protected function wordsForCounter($counter, $arrElement = 'years')
	{

		$arr = [
			'years' => [
				'лет',
				'год',
				'года'
			]
		];

		if (is_array($arrElement)) {

			$arr = $arrElement;
		} else {

			// в переменную положим то что лежит в ячейке: $arr[$arrElement] (если что то в неё пришло) или возьмём 1-ый 
			// элемент массива (при этом он удаляется из массива и все ключи массива будут изменены, чтобы начать отсчет с нуля)
			$arr = $arr[$arrElement] ?? array_shift($arr);
		}

		if (!$arr)
			return null;

		// сохраним в переменную: приведённый к целому числу, обрезанный из содержимого переменной: $counter последний символ
		$char = (int)substr($counter, -1);

		// аналогично для переменной: $counter (но обрезаем с конца два символа)
		$counter = (int)substr($counter, -2);

		if (($counter >= 10 && $counter <= 20) || ($char >= 5 && $char <= 9) || !$char) {

			// вернём то что лежит в ячейке: $arr[0] (если там что то есть) или null
			return $arr[0] ?? null;
		} elseif ($char === 1) {

			return $arr[1] ?? null;
		} else {

			return $arr[2] ?? null;
		}
	}

	protected function showGoods($data, $parameters = [], $template = 'goodsItem')
	{
		if (!empty($data)) {

			echo $this->render(TEMPLATE . 'include/' . $template, compact('data', 'parameters'));
		}
	}
}
