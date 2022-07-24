<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;

class CatalogController extends BaseUser
{

	protected $name;

	protected function inputData()
	{

		parent::inputData();

		$order = [
			'price' => 'цене',
			'name' => 'названию'
		];

		$data = [];

		if (!empty($this->parameters['alias'])) {


			$data = $this->model->get('catalog', [
				'where' => ['alias' => $this->parameters['alias'], 'visible' => 1],
				'limit' => 1
			]);

			if (!$data) {

				throw new RouteException('Не найдены записи в таблице catalog по ссылке ' . $this->parameters['alias']);
			}

			$data = $data[0];
		}

		$where = ['visible' => 1];

		if ($data) {

			$where = ['parent_id' => $data['id']];
		} else {

			$data['name'] = 'Каталог';
		}

		$catalogFilters = $catalogPrices = null;

		$goods = $this->model->getGoods([
			'where' => $where
		], $catalogFilters, $catalogPrices);


		return compact('data', 'goods', 'catalogFilters', 'catalogPrices');
	}
}
