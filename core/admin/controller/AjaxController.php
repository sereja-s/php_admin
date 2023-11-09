<?php

namespace core\admin\controller;

use libraries\FileEdit;

class AjaxController extends BaseAdmin
{
	public function ajax()
	{
		if (isset($this->ajaxData['ajax'])) {

			$this->execBase();

			foreach ($this->ajaxData as $key => $item) {

				$this->ajaxData[$key] = $this->clearStr($item);
			}

			switch ($this->ajaxData['ajax']) {
				case 'sitemap':
					return (new CreatesitemapController())->inputData($this->ajaxData['links_counter'], false);
					break;

				case 'editData':
					// сформируем $_POST['return_id']
					$_POST['return_id'] = true;
					$this->checkPost();
					return json_encode(['success' => 1]);
					break;

				case 'change_parent':
					return $this->changeParent();
					break;

				case 'search':
					return $this->search();
					break;

				case 'wyswyg_file':
					$fileEdit = new FileEdit();
					$fileEdit->setUniqueFile(false);
					$file = $fileEdit->addFile($this->clearStr($this->ajaxData['table']) . '/content_file/');
					return ['location' => PATH . UPLOAD_DIR . $file[key($file)]];
					break;
			}
		}

		return json_encode(['success' => '0', 'message' => 'No ajax variable']);
	}

	// метод работы поиска в админке
	protected function search()
	{
		$data = $this->clearStr($this->ajaxData['data']);
		$table = $this->clearStr($this->ajaxData['table']);

		// вызовем метод модели
		// здесь 3-ий параметр это кол-во подсказок (ссылок) показываемых при работе с поисковой строкой
		return $this->model->search($data, $table, 20);
	}

	// метод работающий при смене родительской категории
	protected function changeParent()
	{
		// вернём результат запроса к административной панели
		// на вход метода: get (1- из какой таблицы вернуть данные, 2- необходимые параметры)
		return $this->model->get($this->ajaxData['table'], [
			'fields' => ['COUNT(*) as count'],
			'where' => ['parent_id' => $this->ajaxData['parent_id']],
			'no_concat' => true
		])[0]['count'] + $this->ajaxData['iteration']; // вернуть то, что придёт в нулевом элементе (в ячейке: count) 
		// вернуть исходя из того, что пришло (т.е. прибавим приведённое к числу текущее значение (из ячейки: ajaxData['iteration']))
	}
}
