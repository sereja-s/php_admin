<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;
use core\base\model\UserModel;
use core\user\helpers\ValidationHelper;

class LkController extends BaseUser
{
	use ValidationHelper;

	protected $model;


	// Выпуск №154 | Пользовательская часть | регистрация пользователя
	protected function inputData()
	{
		parent::inputData();

		// в переменную: $model получили объект класса: UserModel
		//$this->model = UserModel::instance();

		//$this->checkAuth();

		if ($this->isPost()) {
			// +Выпуск №117
			if (empty($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {

				exit('Ошибка входа');
			}

			if (empty($_POST['email']) && empty($_POST['password'])) {

				$this->sendError('Заполните обязательные поля');
			}

			$email = $this->clearStr($_POST['email']);
			$password = $this->clearStr($_POST['password']);

			// если пользователь с такими данными есть, то получим его из БД
			$userData = $this->model->get('visitors', [
				'fields' => ['id', 'name', 'email', 'phone'],
				'where' => ['email' => $email, 'password' => $password],
				//'return_query' => true
			])[0];
		} else {

			$userId = $this->userData['id'];
			$userData = $this->model->get('visitors', [
				'fields' => ['id', 'name', 'email', 'phone'],
				'where' => ['id' => $userId],
				//'return_query' => true
			])[0];
		}

		if (empty($userData['id'])) {

			$this->sendError('Не правильно введены email и(или) пароль');
		}

		$userData['orders'] = $this->model->get('orders', [
			'fields' => ['id', 'order_data', 'total_sum', 'total_qty', 'address'],
			'where' => ['visitors_id' => $userData['id']],
			'order' => ['id'],
			'join' => [
				'delivery' => [
					'fields' => ['name as delivery_name'],
					'on' => ['delivery_id', 'id'],
				],
				'payments' => [
					'fields' => ['name as payments_name'],
					'on' => [
						'table' => 'orders',
						'fields' => ['payments_id', 'id'],
					],
				],
			],
		]);

		if (!empty($userData['orders'])) {

			foreach ($userData['orders'] as $key => $value) {

				$userData['orders'][$key]['goods'] = $this->model->get('orders_goods', [
					'fields' => ['name as good_name', 'price as good_price', 'qty as good_qty'],
					'where' => ['orders_id' => $value['id']],
					'operand' => ['='],
				]);
			}
		}

		if (UserModel::instance()->checkUser($userData['id'])) {

			$this->sendSuccess('Добро пожаловать, ' . $userData['name']);

			//$this->redirect($this->alias(['login' => 'login']));
		}

		return compact('userData');
	}
}
