<?php

// используя функцю define(), определим константу безопасности VG_ACCESS и установим значение: true
// (т.е. прямой доступ к подключаемым ниже файлам будет запрещён до выполнения файла: index.php)
define('VG_ACCESS', true);

// используя функцю header(), отправим браузеру пользователя заголовки с типом контента и кодировкой до того как сделан вывод на экран
header('Content-Type: text/html; charset=utf-8');

// стартуем сессию (сессия запускается после того как пользователь зайдёт на сайт и будет закрыта, когда пользователь закроет браузер)
session_start();

// подключим файлы
require_once 'config.php'; // базовые настройки, для быстрого развёртывания сайта на хостинге
require_once 'core/base/settings/internal_settings.php'; // фундаментальные настройки
require_once 'libraries/functions.php'; // подключили файл функций

use core\base\controller\BaseRoute;
use core\base\exceptions\RouteException;
use core\base\exceptions\DbException;

try {
	// вызовем статический метод routeDirection() у класса BaseRoute (что бы им пользоваться, не нужно создавать объект класса При этом мы работаем внутри класса)
	BaseRoute::routeDirection();
	// условие: в скобках, в $e должен прийти объект класса RouteException (т.е. $e -объект класса RouteException)
} catch (RouteException $e) {
	// тогда выполнится код внутри блока catch
	// метод getMessage() находится в родительском классе Exception и получает сообзение об ошибке,
	// которое было выброшено через throw (здесь- в файле internal_settings.php)
	exit($e->getMessage());
	// перехватываем исключение класса DbException
} catch (DbException $e) {
	exit($e->getMessage());
}
