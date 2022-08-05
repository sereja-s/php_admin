<?php

// условие: если константа безопасности VG_ACCESS установлена (т.е. вернёт true), то код ниже условия выполнится, иначе вернёт (покажет): Access denied (доступ запрещён)
defined('VG_ACCESS') or die('Access denied');

// константа в значении: false не разрешает работу с браузером Microsoft 
const MS_MODE = false;

// константа для хранения пути к пользовательской части сайта
const TEMPLATE = 'templates/default/';
// константа для хранения пути к административной панели сайта
const ADMIN_TEMPLATE = 'core/admin/view/';
// константа, указывающая директорию где у нас будут храниться загружаемые файлы
const UPLOAD_DIR = 'userfiles/';
// константа, указывающая директорию где у нас будут храниться изображения используемые по умолчанию
const DEFAULT_IMAGE_DIRECTORY = 'default_images';

// константа для хранения версии куки файлов (версия выбирается произвольно)
const COOKIE_VERSION = '1.0.0';

// константа для хранения ключа шифрования для куки файлов
// генерируем ключ в генераторе ключей для AES-128 (используемый здесь метод шифрования) Генератор находим в 
// поисковике браузера 
// Переходим по ссылке: https://www.allkeysgenerator.com/Random/Security-Encryption-Key-Generator.aspx
// выбираем: Security level- 128-bit Чтобы ключ был посложнее выбираем: How many ?- 5 (по желанию можно и больше)
// копируем их и вставляем в const CRYPT_KEY в качестве суммарного ключа (равняем в строку)
const CRYPT_KEY = 't7w!z%C&F)J@NcRfYq3t6w9z$B&E)H@MgVkYp3s6v9y$B?E(NdRgUkXp2s5v8y/B)J@NcRfUjXn2r5u8';

// константа для хранения времени в мин. (ограничение бездействия администратора), по истечении которого его выкинет из админки (разлогинит)
const COOKIE_TIME = 60;

// константа для хранения времени в мин. на которое заблокирует пользователя при попытке подобрать пароль к сайту
const BLOCK_TIME = 3;

// константа для хранения конечного слеша
const END_SLASH = '/';

// константы для постраничной навигации
const QTY = 5; // кол-во отображения товаров на странице
const QTY_LINKS = 3; // кол-во ссылок постраничной навигации (левее и правее активной)

// константа для хранения путей css- и js-файлов административной панели сайта
const ADMIN_CSS_JS = [
	// ячейка массива styles отвечает за css-файлы
	'styles' => ['css/main.css'],
	// ячейка массива scripts отвечает за js-файлы
	'scripts' => [
		'js/frameworkfunctions.js',
		'js/scripts.js',
		'js/tinymce/tinymce.min.js',
		'js/tinymce/tinymce_init.js'
	]

];


//  константа для хранения путей css- и js-файлов пользовательской части сайта
const USER_CSS_JS = [
	'styles' => [
		'https://fonts.googleapis.com/css?family=Ubuntu:300,400,500,700&display=swap&subset=cyrillic',
		'https://fonts.googleapis.com/css?family=Didact+Gothic&display=swap&subset=cyrillic',
		'https://cdnjs.cloudflare.com/ajax/libs/normalize/8.0.1/normalize.min.css',
		'https://unpkg.com/swiper/swiper-bundle.min.css',
		'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.css',
		'assets/css/animate.css',
		'assets/css/style.css'
	],
	'scripts' => [
		'https://code.jquery.com/jquery-3.4.1.min.js',
		'https://unpkg.com/swiper/swiper-bundle.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/fancybox/3.5.7/jquery.fancybox.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.2.5/gsap.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.0.2/gsap.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/gsap/2.1.3/TweenMax.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/ScrollMagic.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/plugins/animation.gsap.min.js',
		'https://cdnjs.cloudflare.com/ajax/libs/ScrollMagic/2.0.7/plugins/debug.addIndicators.min.js',
		'assets/js/jquery.maskedinput.min.js',
		'assets/js/TweenMax.min.js',
		'assets/js/ScrollMagic.min.js',
		'assets/js/animation.gsap.min.js',
		'assets/js/bodyscrolllock/bodyScrollLock.min.js',
		'assets/js/app.js',
		'assets/js/script.js'
	]
];

use core\base\exceptions\RouteException;


function autoloadMainClasses($class_name)
{
	// в переменной сохраняем результат работы функции str_replace(), которая поменяет все встречающиеся в переменной $class_name (указана 3-им параметром), знаки \ (в ф-ции он экранирован) на знак /
	$class_name = str_replace('\\', '/', $class_name);

	// условие: если не подключается данный файл
	// (знак @ блокирует вывод ошибок, которые генерирует функция, когда пытается подключить файл и его подключить не может)
	if (!@include_once ($class_name) . '.php') {
		// небходимо выбросить исключение с указанным текстом сообщения
		// (конструкция throw ищет ближайший catch класса RouteException (здесь это в файле catch))
		throw new RouteException('Не верное имя файла для подключения: ' . $class_name);
	}
}

// функция автоматической загрузки классов
spl_autoload_register('autoloadMainClasses');
