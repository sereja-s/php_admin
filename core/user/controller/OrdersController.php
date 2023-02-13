<?php

namespace core\user\controller;

use core\user\helpers\ValidationHelper;

class OrdersController extends BaseUser
{

	use ValidationHelper;

	protected $delivery = [];
	protected $payments = [];

	protected function inputData()
	{
		parent::inputData();

		if ($this->isPost()) {

			$this->delivery = $this->model->get('delivery');
			$this->payments = $this->model->get('payments');

			$this->order();
		}
	}

	protected function order()
	{
		if (empty($this->cart['goods']) || empty($_POST)) {

			$this->sendError('Отсутствуют данные для оформления заказа');
		}

		$validation = [

			'name' => [

				'translate' => 'Ваше имя',
				'methods' => ['emptyField']
			],
			'phone' => [

				'translate' => 'Телефон',
				'methods' => ['emptyField', 'phoneField', 'numericField']
			],
			'email' => [

				'translate' => 'E-mail',
				'methods' => ['emptyField', 'emailField']
			],
			'delivery_id' => [

				'translate' => 'Способ доставки',
				'methods' => ['emptyField', 'numericField']
			],
			'payments_id' => [

				'translate' => 'Способ оплаты',
				'methods' => ['emptyField', 'numericField']
			]

		];

		// опишем массив для заказа:
		$order = [];

		// массив для посетителей:
		$visitor = [];

		$columnsOrders = $this->model->showColumns('orders');

		$columnsVisitors = $this->model->showColumns('visitors');

		foreach ($_POST as $key => $item) {

			if (!empty($validation[$key]['methods'])) {

				foreach ($validation[$key]['methods'] as $method) {

					$_POST[$key] = $item = $this->$method($item, $validation[$key]['translate'] ?? $key);
				}
			}

			if (!empty($columnsOrders[$key])) {

				$order[$key] = $item;
			}
			if (!empty($columnsVisitors[$key])) {

				$visitor[$key] = $item;
			}
		}
	}
}
