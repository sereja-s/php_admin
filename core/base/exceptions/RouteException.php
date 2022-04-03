<?php

namespace core\base\exceptions;

use core\base\controller\BaseMethods;

// создаём класс обработки исключений, который наследует базовый класс Exception обработки исключений языка PHP
// (знак \ говорит, что искать базовый класс Exception необходимо в глобальном пространстве имён)
class RouteException extends \Exception
{

	protected $messages;

	// импортируем трейт BaseMethods (класс вспомогательных методов)
	use BaseMethods;

	// определяем конструктор класса, в котором определим констуктор родительского класса
	public function __construct($message = "", $code = 0)
	{

		// через конструкцию parent вызывается метод родительского класса (что бы видеть служебные сообщения об ошибках)
		parent::__construct($message, $code);

		// в свойство этого класса messages получим массив сообщений (подключим имя файла, который будет отвечать за сообщения:
		// messages.php (лежит в зтой же папке) он вернёт массив сообщений для пользователя по указанному коду ошибки
		$this->messages = include 'messages.php';


		// Bcпользуем методы базового родительского класса: getMessage()- вернёт сообщение об ошибке, 
		// и аналогичные: getCode()- вернёт код ошибки, getFile()- вернёт файл с ошибкой и getLine()- вернёт линию в коде с ошибкой
		// т.е. сделаем проверку: если пришло сообщение. то оно попадёт в переменную $error, иначе получим код ошибки
		$error = $this->getMessage() ? $this->getMessage() : $this->messages[$this->getCode()];

		// далее конкатенируем (добавляем) к переменной: перенос строки для файла (\r\n), слово (file с пробелом), результат работы метода getFile(), опять перенос строки, слово (In line с пробелом) и добавим 
		// результат работы ф-ии ($this->getLine()), далее опять перенос строки (для удобного отображения нового сообщения)
		$error .= "\r\n" . 'file ' . $this->getFile() . "\r\n" . 'In line ' . $this->getLine() . "\r\n";

		// Сгенерируем сообщение для пользователя (оно хранится в свойстве $this->messagе родительского класса Exception) 
		// здесь мы можем получить к нему доступ и его записать
		// сделаем проверку: если в массиве свойства $this->messages есть ячейка с кодом [$this->getCode()]

		//if ($this->messages[$this->getCode()]) 
		//{
		// то перезапишем переменную $this->message (служебное сообщение родительского класса) нашим сообщением (пользовательским согласно кода)
		//$this->message = $this->messages[$this->getCode()];
		//}

		// запишем ошибки (на вход передадим строку. которую мы сформировали в переменной $error)
		$this->writeLog($error);
	}
}
