<?php

namespace core\user\controller;

use core\user\model\Model;

abstract class BaseUser extends \core\base\controller\BaseController
{
	protected $model;
	protected $table;

	protected $set;
	protected $menu;

	/*Проектные свойства*/
	protected $socials;


	protected function inputData()
	{
		// инициализируем стили и скрипты На вход здесь ничего не передаём
		$this->init();

		!$this->model && $this->model = Model::instance();


		$this->set = $this->model->get('settings', [
			'order' => ['id'],
			'limit' => 1
		]);

		$this->set && $this->set = $this->set[0];

		$this->menu['catalog'] = $this->model->get('catalog', [
			'where' => ['visible' => 1, 'parent_id' => null],
			'order' => ['menu_position']
		]);

		$this->menu['information'] = $this->model->get('information', [
			'where' => ['visible' => 1, 'show_top_menu' => 1],
			'order' => ['menu_position']
		]);

		$this->socials = $this->model->get('socials', [
			'where' => ['visible' => 1],
			'order' => ['menu_position']
		]);
	}

	protected function outputData()
	{
		if (!$this->content) {

			// в переменной сохраним результат работы ф-ии php: func_get_arg()- Возвращает указанный аргумент из списка аргументов пользовательской функции (здесь- порядковый номер: 0)
			$args = func_get_arg(0);
			$vars = $args ? $args : [];

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

	protected function alias($alias = '', $queryString = '')
	{
		$str = '';

		if ($queryString) {
			if (is_array($queryString)) {
				foreach ($queryString as $key => $item) {
					$str .= (!$str ? '?' : '&');

					if (is_array($item)) {
						$key .= '[]';

						foreach ($item as $v) {
							$str .= $key . '=' . $v;
						}
					} else {
						$str .= $key . '=' . $item;
					}
				}
			} else {
				if (strpos($queryString, '?') === false) {
					$str = '?' . $str;
				}

				$str .= $queryString;
			}
		}

		if (is_array($alias)) {
			$aliasStr = '';

			foreach ($alias as $key => $item) {
				if (!is_numeric($key) && $item) {
					$aliasStr .= $key . '/' . $item . '/';
				} elseif ($item) {
					$aliasStr .= $item . '/';
				}
			}

			$alias = trim($aliasStr, '/');
		}

		if (!$alias || $alias === '/') {
			return PATH . $str;
		}

		if (preg_match('/^\s*https?:\/\//i', $alias)) {
			return $alias . $str;
		}

		return preg_replace('/\/{2,}/', '/', PATH . $alias . END_SLASH . $str);
	}
}
