<?php

namespace core\user\controller;

use core\base\exceptions\RouteException;
use core\base\model\UserModel;
use core\user\helpers\ValidationHelper;

class LkController extends BaseUser
{
	use ValidationHelper;

	// Выпуск №154 | Пользовательская часть | регистрация пользователя
	protected function inputData()
	{
		parent::inputData();

		// сделаем проверку на случай если пользователь захочет разлогиниться
		// isset()— Определяет, была ли установлена переменная значением, отличным от null
		if (isset($this->parameters['logout'])) {

			// вызываем метод который будет выкидывать куку пользователя (из класса: UserModel)
			$this->model->logout();
			// направляем на эту же страницу
			//$this->redirect($this->alias('index'));
		}

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
				$userData = $this->model->get('visitors', [
					'fields' => ['id', 'name', 'email', 'phone'],
					'where' => ['email' => $email, 'password' => $password],
					//'return_query' => true
				])[0];

				if (!$userData) {

					$this->sendError('не правильно введены email и(или) пароль');
				} else {

					/* $error = 'Добро пожаловать в личный кабинет';
					$_SESSION['res']['answer'] = $success ? '<div class="success">Добро пожаловать, ' . $userData['name'] . '</div>' : preg_split('/\s*\-/', $error, 2, PREG_SPLIT_NO_EMPTY)[0]; */
					$this->sendSuccess('Добро пожаловать, ' . $userData['name']);
				}

				$ordersUser = $this->model->get('orders', [
					'where' => ['visitors_id' => $userData['id']],
				]);

				$a = 1;
			} else {

				//$error = 'Заполните обязательные поля';
				$this->sendError('Заполните обязательные поля');
			}

			/* $_SESSION['res']['answer'] = $success ? '<div class="success">Добро пожаловать, ' . $userData['name'] . '</div>' : preg_split('/\s*\-/', $error, 2, PREG_SPLIT_NO_EMPTY)[0]; */
			//$this->sendError('Добро пожаловать' . $userData['name']);
		}

		return compact('userData');
	}
}
