<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;

class CatalogController extends BaseUser
{

	protected $name;

	protected function inputData()
	{

		parent::inputData();

		/* $order = [
			'price' => 'цене',
			'name' => 'названию'
		]; */

		// количество товаров для отображения на странице каталога
		$quantities = [3, 5, 10];


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

		$catalogFilters = $catalogPrices = $orderDb = null;

		$order = $this->createCatalogOrder($orderDb);

		$operand = $this->checkFilters($where);



		$goods = $this->model->getGoods([

			'where' => $where,

			'operand' => $operand,

			'order' => $orderDb['order'],

			'order_direction' => $orderDb['order_direction'],

			'pagination' => [

				'qty' =>   $_SESSION['quantities'] ?? QTY,

				'page' => $this->clearNum($_GET['page'] ?? 1) ?: 1
			]


			//'pagination' => $this->clearNum($_GET['page'] ?? 1) ?: 1

		], $catalogFilters, $catalogPrices);


		$pages = $this->model->getPagination();



		return compact('data', 'goods', 'catalogFilters', 'catalogPrices', 'order', 'quantities', 'pages');
	}



	protected function checkFilters(&$where)
	{

		$dbWhere = [];

		$dbOperand = [];

		if (isset($_GET['min_price'])) {

			$dbWhere['price'] = $this->clearNum($_GET['min_price']);

			$dbOperand[] = '>=';
		}

		if (isset($_GET['max_price'])) {

			$dbWhere[' price'] = $this->clearNum($_GET['max_price']);

			$dbOperand[] = '<=';
		}



		if (!empty($_GET['filters']) && is_array($_GET['filters'])) {


			$subFiltersQuery = $this->setFilters();

			if ($subFiltersQuery) {

				$dbWhere['id'] = $subFiltersQuery;

				$dbOperand[] = 'IN';
			}


			/* $dbWhere['id'] = $this->model->get('goods_filters', [

				'fields' => ['goods_id'],

				'where' => ['filters_id' => implode(',', $_GET['filters'])],

				'operand' => ['IN'],

				'return_query' => true
			]); */
		}

		$where = array_merge($dbWhere, $where);

		$dbOperand[] = '=';

		return $dbOperand;
	}



	protected function setFilters()
	{

		foreach ($_GET['filters'] as $key => $item) {

			$_GET['filters'][$key] = $this->clearNum($item);

			if (!$_GET['filters'][$key]) {

				unset($_GET['filters'][$key]);

				continue;
			}

			// поищем дубликаты (что бы снять нагрузку с БД)
			$other = array_search($_GET['filters'][$key], $_GET['filters']);

			if ($other !== false && $other !== $key)
				unset($_GET['filters'][$key]);
		}

		// получим фильтры с привязкой к родителям
		$res = $this->model->get('filters', [

			'where' => ['id' => 'SELECT DISTINCT parent_id FROM filters WHERE id IN(' . implode(',', $_GET['filters']) . ')'],
			'operand' => ['IN'],
			'join' => [
				'filters f_val' => [
					'where' => ['id' => implode(',', $_GET['filters'])],
					'operand' => ['IN'],
					'fields' => ['id'],
					'on' => ['id', 'parent_id']
				]
			],

			'join_structure' => true
			//'return_query' => true

		]);

		if ($res) {

			$arr = [];

			$c = 0;

			foreach ($res as $item) {

				if (isset($item['join']['f_val'])) {

					$arr[$c] = array_column($item['join']['f_val'], 'id');

					$c++;
				}
			}

			$resArr = $this->crossDiffArr($arr);

			if ($resArr) {

				$queryStr = '';

				$filtersCount = 0;

				foreach ($resArr as $key => $item) {

					!$filtersCount && $filtersCount = count($item);

					$queryStr .= ' filters_id IN(' . implode(',', $item) . ')' . (isset($resArr[$key + 1]) ? ' OR ' : '');
				}

				return 'SELECT goods_id FROM goods_filters WHERE ' . $queryStr . ' GROUP BY goods_id HAVING COUNT(goods_id) >= '  . $filtersCount;
			}
		}
	}


	protected function crossDiffArr($arr, $counter = 0)
	{


		if (count($arr) === 1) {

			return array_chunk(array_shift($arr), 1);
		}

		if ($counter === count($arr) - 1)
			return $arr[$counter];

		$buffer = $this->crossDiffArr($arr, $counter + 1);

		$res = [];

		foreach ($arr[$counter] as $a) {

			foreach ($buffer as $b) {

				$res[] = is_array($b) ? array_merge([$a], $b) : [$a, $b];
			}
		}

		return $res;
	}



	protected function createCatalogOrder(&$orderDb)
	{

		$order = [

			'цене' => 'price_asc',

			'названию' => 'name_asc'

		];

		$orderDb = ['order' => null, 'order_direction' => null];

		if (!empty($_GET['order'])) {

			$orderArr = preg_split('/_+/', $_GET['order'], 0, PREG_SPLIT_NO_EMPTY);

			if (!empty($this->model->showColumns('goods')[$orderArr[0]])) {

				$orderDb['order'] = $orderArr[0];

				$orderDb['order_direction'] = $orderArr[1] ?? null;

				foreach ($order as $key => $item) {

					if (strpos($item, $orderDb['order']) === 0) {

						$direction = $orderDb['order_direction'] === 'asc' ? 'desc' : 'asc';

						$order[$key] = $orderDb['order'] . '_' . $direction;

						break;
					}
				}
			}
		}

		return $order;
	}
}
