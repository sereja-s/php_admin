<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;
use core\base\model\UserModel;
use core\user\helpers\ValidationHelper;

// Выпуск №154 | Пользовательская часть | регистрация

class LoginController extends BaseUser
{

	use ValidationHelper;

	protected $userModel;
	protected $userData;

	// Выпуск №154 | Пользовательская часть | регистрация пользователя
	protected function inputData()
	{
		parent::inputData();

		// Интернет магазин с нуля на php Выпуск №151 | Пользовательская часть | подготовка почтовых шаблонов
		//$this->checkAuth();

		// в переменную: $model получили объект класса: UserModel
		$this->model = UserModel::instance();

		$this->checkAuth();

		if (!empty($this->parameters['alias'])) {

			switch ($this->parameters['alias']) {

				case 'registration':

					// вызываем метод:
					$this->registration();
					break;

				case 'login':

					// вызываем метод:
					$this->login();
					break;

				case 'logout':

					// вызываем метод который будет выкидывать куку пользователя (из класса: UserModel)
					$this->model->logout();

					// направляем на эту же страницу
					$this->redirect($this->alias('catalog'));

					break;
			}
		} else {

			throw new RouteException('Такой страницы не существует');
		}
	}

	protected function registration(): void
	{

		if (!$this->isPost()) {

			throw new RouteException('не существует');
		}

		$_POST['password'] = trim($_POST['password'] ?? '');
		$_POST['confirm_password'] = trim($_POST['confirm_password'] ?? '');

		if ($this->userData && !$_POST['password']) {

			unset($_POST['password']);
		}
		if (isset($_POST['password']) && $_POST['password'] !== $_POST['confirm_password']) {

			$this->sendError('Пароли не совпадают');
		}

		unset($_POST['confirm_password']);

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


		];

		foreach ($_POST as $key => $item) {

			if (!empty($validation[$key]['methods'])) {

				foreach ($validation[$key]['methods'] as $method) {

					$_POST[$key] = $item = $this->$method($item, $validation[$key]['translate'] ?? $key);
				}
			}
		}

		$where = [
			'phone' => $_POST['phone'],
			'email' => $_POST['email'],
		];

		// в переменную сохраним условие: ИЛИ (т.е здесь- или phone = phone, или email = email)
		$condition[] = 'OR';

		$res = $this->model->get('visitors', [
			'where' => $where,
			'condition' => $condition,
			'limit' => 1
		]);

		if ($res) {

			$res = $res[0];

			$field = $res['phone'] === $_POST['phone'] ? 'телефон' : 'email';

			$this->sendError('Такой ' . $field . '  уже зарегистрирован');
		}

		// добавляем посетителя
		$id = $this->model->add('visitors', [

			// нам нужен идентификатор
			'return_id' => true
		]);

		if (!empty($id)) {

			if (UserModel::instance()->checkUser($id)) {

				$this->sendSuccess('Спасибо за регистрацию, ' . $_POST['name']);

				$this->redirect();
			}
		} else {

			$this->sendError('Произошла внутренняя ошибка Свяжитесь с администрацией сайта');
		}

		//$this->sendError('Произошла внутренняя ошибка Свяжитесь с администрацией сайта');
	}

	protected function login()
	{
		if ($this->isPost()) {
			// +Выпуск №117
			if (empty($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {

				exit('Ошибка входа');
			}

			$success = 0;

			if (!empty($_POST['email']) && !empty($_POST['password'])) {

				$email = $this->clearStr($_POST['email']);
				$password = $this->clearStr($_POST['password']);
				//$password = md5($this->clearStr($_POST['password']));

				// если пользователь с такими данными есть, то получим его из БД
				$this->userData = $this->model->get('visitors', [
					'fields' => ['id', 'name', 'email', 'phone'],
					'where' => ['email' => $email, 'password' => $password],
					//'return_query' => true
				])[0];

				if (!empty($this->userData['id'])) {

					$ordersUser = $this->model->get('orders', [
						'fields' => ['id', 'total_sum', 'total_qty', 'address'],
						'where' => ['visitors_id' => $this->userData['id']],
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

					foreach ($ordersUser as $key => $value) {

						$ordersUser[$key]['goods'] = $this->model->get('orders_goods', [
							'fields' => ['name as good_name', 'price as good_price', 'qty as good_qty'],
							'where' => ['orders_id' => $value['id']],
							'operand' => ['='],
						]);
					}

					if (UserModel::instance()->checkUser($this->userData['id'])) {

						$this->sendSuccess('Добро пожаловать, ' . $this->userData['name']);

						$this->redirect($this->alias(['login' => 'login']));
					}
				} else {

					$this->sendError('Не правильно введены email и(или) пароль');
				}
			} else {

				//$error = 'Заполните обязательные поля';
				$this->sendError('Заполните обязательные поля');
			}
		}

		return compact('userData');
	}
}
