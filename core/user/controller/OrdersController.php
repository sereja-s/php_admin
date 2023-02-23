<?php

namespace core\user\controller;

use core\base\model\UserModel;
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

		// Выпуск №149 | Пользовательская часть | сохранение заказа
		if (empty($visitor['email']) && empty($visitor['phone'])) {

			$this->sendError('Отсутствуют данные пользователя для оформелния заказа');
		}

		$visitorsWhere = $visitorsCondition = [];

		if (!empty($visitor['email']) && !empty($visitor['phone'])) {

			$visitorsWhere = [
				'email' => $visitor['email'],
				'phone' => $visitor['phone']
			];

			$visitorsCondition = ['OR'];
		} else {

			$visitorsKey = !empty($visitor['email']) ? 'email' : 'phone';

			$visitorsWhere[$visitorsKey] = $visitor[$visitorsKey];
		}

		$resVisitor = $this->model->get('visitors', [
			'where' => $visitorsWhere,
			'condition' => $visitorsCondition,
			'limit' => 1
		]);

		if ($resVisitor) {

			$resVisitor = $resVisitor[0];

			$order['visitors_id'] = $resVisitor['id'];
		} else {

			$order['visitors_id'] = $this->model->add('visitors', [
				'fields' => $visitor,
				'return_id' // указали ключ, чтобы вернулся
			]);
		}


		// после того как зарегистрировали пользователя, формируем оставшиеся данные о заказе:

		$order['total_sum'] = $this->cart['total_sum'];

		$order['total_old_sum'] = $this->cart['total_old_sum'];

		$order['total_qty'] = $this->cart['total_qty'];

		$baseStatus = $this->model->get('orders_statuses', [
			'fields' => ['id'],
			'order' => ['menu_position'],
			'limit' => 1
		]);

		$baseStatus && $order['orders_statuses_id'] = $baseStatus[0]['id'];

		// добавляем заказ
		$order['id'] = $this->model->add('orders', [
			'fields' => $order,
			'return_id' => true
		]);


		if (!$order['id']) {

			$this->sendError('Ошибка сохранения заказа. Свяжитесь с администрацией сайта по телефону - ' . $this->set['phone']);
		}

		// если у нас не было такого пользователя и мы его добавляли
		if (!$resVisitor) {

			UserModel::instance()->checkUser($order['visitors_id']);
		}

		$this->sendSuccess('Спасибо за заказ! В ближайшее время наш специалист свяжется с Вами для уточнения деталей');

		$this->sendOrderEmail(['order' => $order, 'visitor' => $visitor]);

		$this->clearCart();

		$this->redirect();
	}

	protected function sendOrderEmail(array $orderData)
	{
	}
}
