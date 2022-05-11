<?php

namespace core\admin\controller;

use core\base\model\UserModel;
use core\base\settings\Settings;

class LoginController extends \core\base\controller\BaseController
{
	protected $model;

	protected function inputData()
	{
		// в переменную: $model получили объект класса: UserModel
		$this->model = UserModel::instance();
		// получим возможность работать с административной частью
		$this->model->setAdmin();

		// сделаем проверку на случай если пользователь захочет разлогиниться
		// isset()— Определяет, была ли установлена переменная значением, отличным от null
		if (isset($this->parameters['logout'])) {
			// вызовем метод проверки авторизации с флагом на входе в значении: true 
			$this->checkAuth(true);

			// логируем вход и выход пользователя
			$userLog = 'Выход пользователя ' . $this->userId['name'];
			$this->writeLog($userLog, 'user_log.txt', 'Access user');

			// вызываем метод который будет выкидывать куку пользователя (из класса: UserModel)
			$this->model->logout();
			// направляем на эту же страницу
			$this->redirect(PATH);
		}

		if ($this->isPost()) {
			// сделаем проверку если в ячейке: $_POST['token'] пусто или её содержимое не равно: $_SESSION['token']
			if (empty($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
				// выходим из программы с сообщением
				exit('Куку ошибка :)');
			}


			// Разблокировка пользователя после времени указанного в константе: BLOCK_TIME

			// получим имя переменной по которой будем чистить данные
			// вызываем объект встроенного класса \DateTime, у него вызовем метод: modify() и модифицируем на минус указанное в константе значение: BLOCK_TIME часов изатем форматируем его (объект) под систему хранения в БД (ф-ия: format())
			$timeClean = (new \DateTime())->modify('-' . BLOCK_TIME . ' hour')->format('Y-m-d H:i:s');
			//$timeClean = (new \DateTime())->modify('-1' . ' seconds')->format('Y-m-d H:i:s');

			// одним запросом к базе данных удалим все записи, в которых поле: time меньше чем текущее
			// вызываем getBlockedTable() из класса: UserModel
			$this->model->delete($this->model->getBlockedTable(), [
				'where' => ['time' => $timeClean],
				'operand' => ['<']
			]);


			// Получим ip пользователя
			// filter_var() — фильтрует переменную с заданным фильтром
			// в переменную: $ipUser сохраним результат (если он будет) работы ф-ии: filter_var() 1-ый случай иначе 2-ой случай
			// если и там ничего не придёт, тогда сохраним значение в ячейке: $_SERVER['REMOTE_ADDR']
			$ipUser = filter_var(@$_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP) ?: (filter_var(@$_SERVER['HTTP_X_FORWARDED_FOR'], FILTER_VALIDATE_IP) ?: @$_SERVER['REMOTE_ADDR']);

			// получаем в переменную: $trying все данные по ip пользователя из поля: trying по условию: 'ip' => $ipUser
			$trying = $this->model->get($this->model->getBlockedTable(), [
				'fields' => ['trying'],
				'where' => ['ip' => $ipUser]
			]);

			// в переменную: $trying сохраним (при условии, что в $trying не пусто ) приведённое к числу содержимое 
			// ячейки: $trying[0]['trying'] иначе сохраним ноль
			$trying = !empty($trying) ? $this->clearNum($trying[0]['trying']) : 0;

			$success = 0;


			// проверка если логин и пароль заполнены и попыток их ввода меньше указанного значения (проверку пользователь прошёл)
			if (!empty($_POST['login']) && !empty($_POST['password']) && $trying < 9) {

				// сохраним строки введённых логина и пароля в соответствующих переменных (предварительно очистив функцией: 
				// clearStr())
				$login = $this->clearStr($_POST['login']);
				$password = md5($this->clearStr($_POST['password']));

				// в переменной: $userData сохраняем запрашиваемую информацию из таблицы из БД с данными администрации (users) 
				// Используя метод: getAdminTable() из класса: UserModel, который передали методу: get() на вход 1-ым параметором,
				// вернули название таблицы  Поля таблицы и условия передали 2-ым параметром
				$userData = $this->model->get($this->model->getAdminTable(), [
					'fields' => ['id', 'name'],
					'where' => ['login' => $login, 'password' => $password]
				]);

				// если данные пользователя не пришли
				if (!$userData) {
					$method = 'add';

					$where = [];

					// если в переменной: $trying уже что то есть (т.е. уже были не удачные попытки зайти в админку)
					if ($trying) {
						// переопределяем метод
						$method = 'edit';
						// переопределяем условие
						$where['ip'] = $ipUser;
					}

					// у текущего объекта модели вызываем метод, который хранится в переменной: $method (на вход подаём: 1- название 
					// блокирующей таблицы (blocked_access) и 2-ым параметром передаём массив с данными)
					$this->model->$method($this->model->getBlockedTable(), [
						'fields' => ['login' => $login, 'ip' => $ipUser, 'time' => 'NOW()', 'trying' => ++$trying],
						'where' => $where
					]);

					// выводим сообщение об ошибке
					$error = 'Неверное имя пользователя или пароль - ' . $ipUser . ', логин - ' . $login;

					// иначе (если данные пользователя пришли)
				} else {
					// если пользоватль не прошёл проверку в методе: checkUser() из класса: UserModel
					if (!$this->model->checkUser($userData[0]['id'])) {
						// в переменной: $error сохраним результат работы метода: getLastError() из класса: UserModel
						$error = $this->model->getLastError();
						// иначе 
					} else {
						$error = 'Вход пользователя - ' . $login;
						$success = 1;
					}
				}
				// если попыток ввода больше (или равно) указанному значению
			} elseif ($trying >= 9) {
				// вызовем метод который будет выкидывать куку пользователя
				$this->model->logout();
				// и покажем сообщение
				$error = 'Превышено максимальное количество попыток ввода пароля - ' . $ipUser;
				// иначе
			} else {
				//  если поля не заполнены покажем сообщение
				$error = 'Заполните обязательные поля';
			}

			// если в ячейке: $_SESSION['res']['answer'] есть переменная $success, то на экране выводим приветственное сообщение
			$_SESSION['res']['answer'] = $success
				? '<div class="success">Добро пожаловать ' . $userData[0]['name'] . '</div>'
				// иначе запишем ошибку в переменную: $error
				// preg_split() — разделение строки регулярным выражением (здесь- по дефису)
				: preg_split('/\s*\-/', $error, 2, PREG_SPLIT_NO_EMPTY)[0];

			// выведем сообщение об ошибке
			$this->writeLog($error, 'user_log.txt', 'Access user');

			$path = null;

			// если в переменая: $success = 1 (возвращает true), т.е. авторизация прошла успешно, то в переменную: $path сохраняем // путь к админке
			$success && $path = PATH . Settings::get('routes')['admin']['alias'];
			// перенаправляем пользователя по указанному адресу
			$this->redirect($path);
		}

		return $this->render('', ['adminPath' => Settings::get('routes')['admin']['alias']]);
	}
}
