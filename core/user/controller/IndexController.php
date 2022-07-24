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


		$advantages = $this->model->get('advantages', [
			'where' => ['visible' => 1],
			'order' => ['menu_position'],
			'limit' => 10
		]);


		$news = $this->model->get('news', [
			'where' => ['visible' => 1],
			'order' => ['date'],
			'order_direction' => ['DESC'],
			'limit' => 4
		]);


		$arrHits = [

			'hit' => [
				'name' => 'Хиты продаж',
				'icon' => '<svg><use xlink:href="' . PATH . TEMPLATE . 'assets/img/icons.svg#hit"</use></svg>'
			],
			'hot' => [
				'name' => 'Горячие предложения',
				'icon' => '<svg><use xlink:href="' . PATH . TEMPLATE . 'assets/img/icons.svg#hot"</use></svg>'
			],
			'sale' => [
				'name' => 'Распродажа',
				'icon' => '%'
			],
			'new' => [
				'name' => 'Новинки',
				'icon' => 'new'
			],

		];

		$goods = [];

		foreach ($arrHits as $type => $item) {

			$goods[$type] = $this->model->getGoods([
				'where' => [$type => 1, 'visible' => 1],
				'limit' => 6 // ограничение (к выводу 6-ть товаров (хит продаж, акция, новинка, горячее предложение))
			]);
		}

		//$goods = $this->model->getGoods();

		// собираем все переменные в массив и возвращаем в шаблон:
		return compact('sales', 'arrHits', 'goods', 'advantages', 'news');
	}
}
