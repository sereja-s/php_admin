<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;

class ProductController extends BaseUser
{

	protected function inputData()
	{
		parent::inputData();

		if (empty($this->parameters['alias'])) {
			throw new RouteException('Отсутствует ссылка на товар', 3);
		}

		$data = $this->model->getGoods([
			'where' => ['alias' => $this->parameters['alias'], 'visible' => 1]
		]);

		if (!$data) {
			throw new RouteException('Отсутствует товар по ссылке ' . $this->parameters['alias']);
		}

		$data = array_shift($data);

		return compact('data');
	}
}
