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

			$this->delivery = $this->model->get('delivery', ['join_structure' => true]);
			$this->payments = $this->model->get('payments', ['join_structure' => true]);

			$this->order();
		}
	}

	// Выпуск №147
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

		// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
		if (!($goods = $this->setOrdersGoods($order))) {

			$this->sendError('Ошибка сохранения товаров заказа. Обратитесь к администрации сайта');
		}

		$this->sendSuccess('Спасибо за заказ! В ближайшее время наш специалист свяжется с Вами для уточнения деталей');

		// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
		$order['delivery'] = $this->delivery[$order['delivery_id']]['name'] ?? '';
		$order['payments'] = $this->payments[$order['payments_id']]['name'] ?? '';

		// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
		$this->sendOrderEmail(['order' => $order, 'visitor' => $visitor, 'goods' => $goods]);

		$this->clearCart();

		$this->redirect();
	}


	// Выпуск №150 | Пользовательская часть | сохранение товаров заказа
	// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов

	/** 
	 * метод сохранения товаров заказа (просто установщик данных)
	 */
	protected function setOrdersGoods(array $order): ?array
	{
		// проверим есть ли таблица с товарами заказов
		if (in_array('orders_goods', $this->model->showTables())) {

			$ordersGoods = [];

			// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
			$preparedGoods = [];

			foreach ($this->cart['goods'] as $key => $item) {

				// идентификатор заказа
				$ordersGoods[$key]['orders_id'] = $order['id'];

				// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
				$preparedGoods[$key] = $item;
				$preparedGoods[$key]['total_sum'] = $item['qty'] * $item['price'];

				foreach ($item as $field => $value) {

					// проверим есть ли такое поле в соответствующей таблице (здесь- orders_goods)
					if (!empty($this->model->showColumns('orders_goods')[$field])) {

						// проверим является ли поле, которое пришло идентификатором: id
						if ($this->model->showColumns('orders_goods')['id_row'] === $field) {

							if ($this->model->showColumns('orders_goods')['goods_id']) {

								$ordersGoods[$key]['goods_id'] = $value;
							}
						} else {

							$ordersGoods[$key][$field] = $value;
						}
					}
				}
			}

			// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
			if ($this->model->add('orders_goods', [

				'fields' => $ordersGoods
			])) {

				return $preparedGoods;
			}
		}

		// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
		return null;
	}

	protected function sendOrderEmail(array $orderData)
	{
		// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
		$dir = TEMPLATE . 'include/orderTemplates/';

		$templatesArr = [];

		if (is_dir($dir)) {

			$list = scandir($dir);

			foreach ($orderData as $name => $item) {

				// используем поиск в массиве по регулярному выражению
				if ($file = preg_grep('/^' . $name . '\./', $list)) {

					$file = array_shift($file);

					$template = file_get_contents($dir . $file);

					if (!is_numeric(key($item))) {

						$templatesArr[] = $this->renderOrderMailTemplate($template, $item);
					} else {

						if (($common = preg_grep('/' . $name . 'Header\./', $list))) {

							$common = array_shift($common);

							$templatesArr[] = $this->renderOrderMailTemplate(file_get_contents($dir . $common), []);
						}

						foreach ($item as $value) {

							$templatesArr[] = $this->renderOrderMailTemplate($template, $value);
						}

						if (($common = preg_grep('/' . $name . 'Footer\./', $list))) {

							$common = array_shift($common);

							$templatesArr[] = $this->renderOrderMailTemplate(file_get_contents($dir . $common), []);
						}
					}
				}
			}

			$sender = new SendMailController();

			$sender->setMailBody($templatesArr)->send($orderData['visitor']['email']);
		}
	}

	// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
	protected function renderOrderMailTemplate(string $template, array $data): string
	{

		foreach ($data as $key => $item) {

			$template = preg_replace('/#' . $key . '#/i', $item, $template);
		}

		return $template;
	}
}
