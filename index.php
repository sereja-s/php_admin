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
require_once 'libraries/functions.php';

use core\base\controller\BaseRoute;
use core\base\exceptions\RouteException;
use core\base\exceptions\DbException;

try {
	BaseRoute::routeDirection();
} catch (RouteException $e) {
	exit($e->getMessage());
} catch (DbException $e) {
	exit($e->getMessage());
}
