<?php

namespace core\base\controller;

// класс вспомогательных методов
trait BaseMethods
{

	// Метод очистки данных (для строковых данных, а также массивов)
	protected function clearStr($str)
	{
		if (is_array($str)) {
			// Конструкция foreach предоставляет простой способ перебора массивов (работает только с массивами и объектами)
			// присвоит ключ текущего элемента переменной $key. а значение текущего элемента присваивается переменной $item
			foreach ($str as $key => $item) {

				$str[$key] = $this->clearStr($item);
			}
			return $str;
		} else {
			// trim — Удаляет пробелы (или другие символы) из начала и конца строки
			// strip_tags — Удаляет теги HTML и PHP из строки
			return trim(strip_tags($str));
		}
	}

	// Метод очистки данных (для числовых данных)
	protected function clearNum($num)
	{
		// empty() — Проверяет, пуста ли переменная (переменная считается пустой, если она не существует или её значение равно false)
		// preg_match() — Выполняет проверку на соответствие регулярному выражению (Ищет в заданном тексте $num совпадения с шаблоном /\d/, т.е. цифры )
		// если условие выполняется и если совпадения есть:
		return (!empty($num) && preg_match('/\d/', $num)) ?
			// то выполняем поиск и замену:
			// preg_replace() — Выполняет поиск и замену по регулярному выражению
			// (здесь- выполняет поиск совпадений в строке $num с шаблоном /[^\d.]/ (означает всё что не цифры и точки) // и заменяет их на '' (пустую строку))
			// далее умножаем на единицу (чтобы привести к числу) 
			// иначе: вернём ноль
			preg_replace('/[^\d.]/', '', $num) * 1 : 0;
	}

	// Проверочный метод: пришли ли данные при помощи метода Post
	protected function isPost()
	{
		// работаем с суперглобальным массивом $_SERVER и его ячейкой REQUEST_METHOD
		// (если равенство выполняется, то вернёт true)
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}

	// Проверочный метод: пришли ли данные при помощи метода XMLHttpRequest (используется при передаче данных Ajax (асинхронной отправке запроса из браузера))
	protected function isAjax()
	{
		// проверим с помощью ф-ии php: isset() существует ли в суперглобальном массиве $_SERVER ячейка HTTP_X_REQUESTED_WITH и 
		// эта ячейка жёстко равна XMLHttpRequest
		// (если проверка выполнется, то вернётся true)
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
	}

	// метод перенаправления страницы
	protected function redirect($http = false, $code = false)
	{
		// проверка: если $code не false
		if ($code) {
			// объявим массив $codes, в ячейке которого сохраним элемент со значением 301, который указывает на строку:
			//  HTTP/1.1 301 Move Permanently
			$codes = ['301' => 'HTTP/1.1 301 Move Permanently'];

			// проверим существует ли такой элемент массива $codes его ячейка $code 
			if ($codes[$code]) {

				// отправим заголовок (HTTP/1.1 301 Move Permanently) браузеру при помощи ф-ии php: header()
				header($codes[$code]);
			}
		}
		// Сдеаем перенаправление:

		// проверка: если пришёл http
		if ($http) $redirect = $http;
		// иначе в переменную $redirect сохраним результат проверки: существует ли в суперглобальном массиве $_SERVER ячейка: 
		// HTTP_REFERER (она будет существовать если пользователь перешёл на нашу страницу с другой страницы нашего сайта), то 
		// всё то что находится после знака вопроса занесётся в $redirect 
		// иначе перенаправим пользователя на главную страницу нашего сайта (обратимся к константе PATH)
		else $redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : PATH;
		// отправим заголовок при помощи ф-ии php: header() и укажем на входе заголовок $redirect (в двойных кавычках), т.е. куда перенаправить
		header("location: $redirect");
		exit;
	}

	// вывод стилей
	protected function getStyles()
	{
		if ($this->styles) {
			foreach ($this->styles as $style) {
				echo '<link rel="stylesheet" href="' . $style . '">';
			}
		}
	}

	// вывод скриптов
	protected function getScripts()
	{
		if ($this->scripts) {
			foreach ($this->scripts as $script) {
				echo '<script src="' . $script . '"></script>';
			}
		}
	}

	// метод который пишет в Log-файл (в параметры передаём сообщение, которое будет выводиться, имя файла куда писать и событие,которое происходит (по умолчанию: ошибка))
	protected function writeLog($message, $file = 'log.txt', $event = 'Fault')
	{
		// создадим переменную $dateTime, в которую сохраним объект встроенного класса php: DateTime(), созданный для текущей метки времени, т.к. ничего не передали на вход (в параметры)
		$dateTime = new \DateTime();

		// в переменную $str запишем строку вида: событие, двуеточие, пробел, далее обращаемся к объкту $dateTime и вызываем у 
		// него метод format, который на взод принимает строку в виде шаблона, далее идёт дефис, сообщение и перенос строки для файла
		$str = $event . ': ' . $dateTime->format('d-m-Y G:i:s') . ' - ' . $message . "\r\n";

		// file_put_contents — Пишет данные в файл
		// в параметры (на вход) передаём: 1- Путь к записываемому файлу 'log/' . $file); 2- Записываемые данные ($str); 
		// 3- Значение параметра flags (здесь- FILE_APPEND, т.е. Если файл уже существует, данные будут дописаны в конец файла вместо того, чтобы его перезаписать.)
		// (Функция идентична последовательным успешным вызовам функций fopen(), fwrite() и fclose().)
		file_put_contents('log/' . $file, $str, FILE_APPEND);
	}

	// метод для получения контроллера
	protected function getController()
	{
		// вернём $this->controller (если он есть)
		return $this->controller ?:

			// или вернём то что положим в $this->controller

			// preg_split() — разделение строки регулярным выражением

			// здесь- разделим строку (2-ой параметр) на элементы индексируемого массива по регулярному выражению (1-ый 
			// параметр): /_?controller/, что означает- символ: _	 может быть, а может не быть и слово: controller

			// разделяемую строку (результат работы ф-ии: preg_replace() приведём к нижнему регистру Функция из названия 
			// контроллера, написанного кэмэлкейсом сделает сочетание, разделённое символом: _ ) 

			// в ф-ию: preg_replace() в качестве шаблона (что ищем) подаём регулярное выражение (две объединённые переменные: // 1-ой  должна идти не заглавная буква: [^A-Z], 2-ой заглавная буква: [A-Z]), заменить мы их должны на 2-ой параметр- строку: $1_$2 (т.е поставить между найденными перемеными символ _ ) 

			// 3-им параметром подаём строку в которой делается замена: (new \ReflectionClass($this))->getShortName())
			// здесь- getShortName() получаем короткое имя контроллера (без namespace)  

			// 4-ым параметром подаём кол-во элементов,которое необходимо вернуть (limit = 0 т.е. все) 

			// 5-ым- флаг: PREG_SPLIT_NO_EMPTY, т.е. не показывать пустые элементы

			// в переменную: $this->controller вернём нулевой элемент полученного в результате массива
			$this->controller = preg_split('/_?controller/', strtolower(preg_replace('/([^A-Z])([A-Z])/', '$1_$2', (new \ReflectionClass($this))->getShortName())), 0, PREG_SPLIT_NO_EMPTY)[0];
	}

	/**
	 * метод для отображения даты 
	 * (по умолчанию на вход принимает строку: $date)
	 */
	protected function dateFormat($date)
	{
		if (!$date) {
			return $date;
		}

		$daysArr = [
			'Sunday' => 'Воскресенье',
			'Monday' => 'Понедельник',
			'Tuesday' => 'Вторник',
			'Wednesday' => 'Среда',
			'Thursday' => 'Четверг',
			'Friday' => 'Пятница',
			'Saturday' => 'Суббота'
		];

		$monthArr = [
			1 => 'Январь',
			2 => 'Февраль',
			3 => 'Март',
			4 => 'Апрель',
			5 => 'Май',
			6 => 'Июнь',
			7 => 'Июль',
			8 => 'Август',
			9 => 'Сентябрь',
			10 => 'Октябрь',
			11 => 'Ноябрь',
			12 => 'Декабрь',
		];

		$dateArr = [];

		$dateData = new \DateTime($date);

		$dateArr['year'] = $dateData->format('Y');

		$dateArr['month'] = $monthArr[$this->clearNum($dateData->format('m'))];

		$dateArr['monthFormat'] = preg_match('/т$/u', $dateArr['month']) ? $dateArr['month'] . 'а' : preg_replace('/[ьй]/u', 'я', $dateArr['month']);

		$dateArr['weekDay'] = $daysArr[$dateData->format('L')];

		$dateArr['day'] = $dateData->format('d');

		$dateArr['time'] = $dateData->format('H:i:s');

		$dateArr['format'] = mb_strtolower($dateArr['day']) . ' ' . $dateArr['monthFormat'] . ' ' . $dateArr['year'];


		return $dateArr;
	}
}
