<?php

namespace core\user\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;

class IndexController extends BaseUser
{

	protected $name;

	protected function inputData()
	{
		parent::inputData();

		//echo $this->getController();

		//exit;

		// $years = $this->wordsForCounter(1);

		// $a = 1;

		$sales = $this->model->get('sales', [
			'where' => ['visible' => 1],
			'order' => ['menu_position']
		]);


		$arrHits = ['hit', 'sale', 'new', 'hot'];

		$goods = [];

		foreach ($arrHits as $type) {

			$goods[$type] = $this->model->getGoods([
				'where' => [$type => 1],
				'limit' => 6 // ограничение (к выводу 6-ть товаров (хит продаж, акция, новинка, горячее предложение))
			]);
		}

		//$goods = $this->model->getGoods();

		// собираем переменные в массив и возвращаем переменную:
		return compact('sales');
	}
}
