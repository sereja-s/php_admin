<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;
use core\base\model\UserModel;
use core\user\helpers\ValidationHelper;

// Выпуск №154 | Пользовательская часть | регистрация

class LoginController extends BaseUser
{

	use ValidationHelper;

	// Выпуск №154 | Пользовательская часть | регистрация пользователя
	protected function inputData()
	{
		parent::inputData();

		if (!empty($this->parameters['alias'])) {

			switch ($this->parameters['alias']) {

				case 'registration':

					// вызываем метод:
					$this->registration();
					break;
			}
		}

		throw new RouteException('Такой страницы не существует');
	}

	protected function registration(): void
	{

		if (!$this->isPost()) {

			throw new RouteException('Такой страницы не существует');
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
			}
		}

		$this->sendError('Произошла внутренняя ошибка Свяжитесь с администрацией сайта');
	}
}
