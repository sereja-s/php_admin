<?php

namespace core\admin\controller;

use core\base\controller\BaseMethods;
use core\admin\controller\BaseAdmin;

class CreatesitemapController extends BaseAdmin
{
	use BaseMethods;

	protected $all_links = [];
	// свойство для хранения временных элементов парсинга запросов
	protected $temp_links = [];
	protected $bad_links = [];

	// максимально допустимое количество ссылок которые могут подаваться
	protected $maxLinks = 5000;

	// свойство, которое будет содержать имя файла для логирования элементов (например коды ответа: 404, 307 и т.д.) Что бы 
	// такая ссылка дальше не обрабатывалась
	protected $parsingLogFile = 'parsing_log.txt';
	// объявим массив в котором будем держать расширения тех файлов (если они попались в атрибуте (например href)) с которыми 
	// дальше ничего делать не будем
	protected $fileArr = ['jpg', 'png', 'jpeg', 'gif', 'xls', 'xlsx', 'pdf', 'mp4', 'mpeg', 'mp3', 'avi'];

	protected $filterArr = [
		'url' => [],
		'get' => []
	];

	// входной метод (на вход принимает: 1- счётчик ссылок)
	public function inputData($links_counter = 1, $redirect = true)
	{
		$links_counter = $this->clearNum($links_counter);

		// проверим существует ли функция: 
		if (!function_exists('curl_init')) {
			// завершаем работу скрипта и выводим сообщение
			$this->cancel(0, 'Library CURL is absent. Creation of sitemap is imposible.', '', true);
		}

		// сделаем стандартную проверку и выполним метод инициализации модели (выполним код базового метода родителя 
		// (абстрактного класса: BaseAdmin) чтобы получить объект модели)
		if (!$this->userId) {
			$this->execBase();
		}

		// если не выполнился метод: checkParsingTable()
		if (!$this->checkParsingTable()) {
			// завершаем работу скрипта и выводим сообщение
			$this->cancel(0, 'You have problem with database table parsing data', '', true);
		}
		// иначе таблица: parsing_data с добавленными в неё данными будет создана

		// снимаем ограничение на время выполнения скрипта
		set_time_limit(0);

		// в переменой сохраним результат работы объекта модели его метда: get()-т.е. все то что хранится в БД (в таблице: 
		// parsing_data) Вернём нулевой элемент
		$reserve = $this->model->get('parsing_data')[0];
		$table_rows = [];

		foreach ($reserve as $name => $item) {
			$table_rows[$name] = '';

			if ($item) {
				$this->$name = json_decode($item);
			} elseif ($name === 'all_links' || $name === 'temp_links') {
				$this->$name = [SITE_URL];
			}
		}

		// в свойство: $this->maxLinks сохраним результат работы ф-ии: ceil() (если приведённая к цеочисленному типу $links_counter больше единицы) иначе $this->maxLinks не изменится
		// ceil()- Возвращает следующее по величине целочисленное значение путем округления в большую сторону, если это необходимо
		$this->maxLinks = (int)$links_counter > 1
			? ceil($this->maxLinks / $links_counter)
			: $this->maxLinks;

		// на каждой итерации новые ссылки будут попадать в свойство (массив): $temp_links (будет заполняться динамически из метода: parsing())
		// запускаем цикл (будем постоянно проверять: если в $temp_links что то есть, то будем осуществлять парсинг (запускать метод: parsing()))
		while ($this->temp_links) {
			// в переменную созраним кол-во элементов, которые будут храниться в св-ве: $temp_links
			$temp_links_count = count($this->temp_links);
			// в переменную: $links сохраняем то что пришло в св-во: $temp_links
			$links = $this->temp_links;
			// затем св-во: $temp_links обнуляем (запишем как пустой массив)
			$this->temp_links = [];

			// если кол-во ссылок пришло больше чем установленное максимально допустимое
			if ($temp_links_count > $this->maxLinks) {

				// Разделим массив в $links на заданное кол-во частей и сохраним результат в переменной: $links
				// array_chunk()- Разбивает массив на массивы с элементами. Последний блок может содержать меньше элементов.
				// в параметры на вход подаём: 1- что делим, 1- на какое кол-во частей (ceil()- Возвращает следующее по величине 
				// целочисленное значение выражения, путем округления в большую сторону, если это необходимо)
				$links = array_chunk($links, ceil($temp_links_count / $this->maxLinks));

				// сохраним исходное кол-во элементов массива в $links, т.е. на сколько элементов он поделился (т.к. мы его будем 
				// чистить, когда будем подавать массив на вход методу: parsing())
				$count_chunks = count($links);

				for ($i = 0; $i < $count_chunks; $i++) {
					// методу parsing() на каждой итерации будем подавать i-ый элемент (массив) массива в $links 
					$this->parsing($links[$i]);
					// затем разрегистрируем (удаляем) этот элемент (массив), т.к. его элементы мы уже разпарсили ( и что бы не 
					// забивать память)
					unset($links[$i]);

					// если в $links что то есть (осталось)
					if ($links) {
						foreach ($table_rows as $name => $item) {
							if ($name === 'temp_links') {

								// json_encode() — возвращает строку, содержащую JSON-представление значения, поданного на вход
								// array_merge() — объединение одного или нескольких массивов поданных на вход
								// (здесь на вход подаём: ...$links, что означает подали столько элементов массива 1-го уровня, сколько 
								// было в переменной: $links) Это необходимо потому что ф-ия php: array_merge() не принимает 
								// многомерный массив
								$table_rows[$name] = json_encode(array_merge(...$links));
							} else {
								$table_rows[$name] = json_encode($this->$name);
							}
						}

						// вызываем метод: edit() На вход: 1- что редактируем (название таблицы) 2- массив (поля для редактирования)
						$this->model->edit('parsing_data', [
							'fields' => $table_rows
						]);
					}
				}

				// иначе (т.е. кол-во ссылок для парсинга не превышает максимально допустимого)
			} else {

				//вызовем метод: parsing() и на вход передадим то что пришло в переменную: $links ( одномерный массив ссылок для парсинга)
				$this->parsing($links);
			}

			foreach ($table_rows as $name => $item) {
				$table_rows[$name] = json_encode($this->$name);
			}

			$this->model->edit('parsing_data', [
				'fields' => $table_rows
			]);
		}

		foreach ($table_rows as $name => $item) {
			// по окончании цикла: while, содержмое ячейки удалим и оставим пустую строку
			$table_rows[$name] = '';
		}

		$this->model->edit('parsing_data', [
			'fields' => $table_rows
		]);

		if ($this->all_links) {
			// пройдёмся по массиву и для его элемента вызовем метод: filter
			foreach ($this->all_links as $key => $link) {
				if (!$this->filter($link) || in_array($link, $this->bad_links)) {
					unset($this->all_links[$key]);
				}
			}
		}

		$this->createSitemap();

		if ($redirect) {
			!$_SESSION['res']['answer'] && $_SESSION['res']['answer'] = '<div class="success">Sitemap is created!</div>';

			$this->redirect();
		} else {
			$this->cancel(1, 'Sitemap is created! ' . count($this->all_links)  . ' links', '', true);
		}
	}

	// метод, который будет парсить сайты (на вход приходят ссылки на сайт)
	protected function parsing($urls)
	{
		if (!$urls) {
			return;
		}

		// Инициализируем библиотеку: curl 
		// создадим дискриптор многопоточного подключения (соединения) с помощью ф-ии: curl_multi_init(), которая инициализирует 
		// многопоточный curl
		$curlMulty = curl_multi_init();
		// создадим массив дискрипторов
		$curl = [];

		foreach ($urls as $i => $url) {
			// (инициализировали дескриптор одного потока: $curl[$i] при помощи которого будут отправляться запросы на сервер)
			$curl[$i] = curl_init();

			// настроим curl
			// опция: CURLOPT_URL- константа библиотеки curl (показывает куда отправлять запросы)
			curl_setopt($curl[$i], CURLOPT_URL, $url);
			// CURLOPT_RETURNTRANSFER (получать ответы от сервера)
			curl_setopt($curl[$i], CURLOPT_RETURNTRANSFER, true);
			// CURLOPT_HEADER (возвращать заголовки)
			curl_setopt($curl[$i], CURLOPT_HEADER, true);
			// CURLOPT_FOLLOWLOCATION (идти ли CURL за запросом при редиректе)
			curl_setopt($curl[$i], CURLOPT_FOLLOWLOCATION, 1);
			// CURLOPT_TIMEOUT (ограницение ожидания ответа от сервера)
			curl_setopt($curl[$i], CURLOPT_TIMEOUT, 120);
			// CURLOPT_ENCODING (декодирует страницу в браузере (если она призодит сжатая в gzip))
			curl_setopt($curl[$i], CURLOPT_ENCODING, 'gzip,deflate');

			// добавим обработчик событий (конкретный дискриптор события): $curl[$i] к основному дискриптору: $curlMulty
			curl_multi_add_handle($curlMulty, $curl[$i]);
		}

		// Запускаем цикл по которому будем отлавливать собщения от curl
		do {
			// опишем первую функцию инициализации
			// переменная: $active- активность соединения Если ф-ия вернёт $active ноль, значит curl все дискрипторы обошёл
			$status = curl_multi_exec($curlMulty, $active);
			// переменная для хранения информационных сообщений (массив) от curl
			$info = curl_multi_info_read($curlMulty);

			// если в $info пришло не false
			if (false !== $info) {
				// если в работе curl что то не так
				if ($info['result'] !== 0) {
					// в ячейке: $info['handle'] хранится адрес дискриптора в стеке: curl_multi, у которого возникли вопросы в соединении
					// array_search()- ищет элемент в массиве ($curl) и возвращает ключ искомого элемента (ячейки: $info['handle'])
					$i = array_search($info['handle'], $curl);

					//  получим ошибку которая возникла
					// curl_errno()- Возвращает номер ошибки для последней операции cURL
					$error = curl_errno($curl[$i]);
					// curl_error()- Возвращает сообщение об ошибке для последней операции cURL
					$message = curl_error($curl[$i]);
					// curl_getinfo()- вернёт массив с информационными данными запроса cURL, за который отвечает 
					// дескриптор: $curl[$i]
					$header = curl_getinfo($curl[$i]);

					// если переменная: $error не равен нулю (есть ошибка)
					if ($error != 0) {
						$this->cancel(0, 'Error loading '
							. $header['url'] . 'http code: ' . $header['http_code']
							. ' error: ' . $error . ' message: ' . $message);
					}
				}
			}

			if ($status > 0) {
				// curl_multi_strerror()- Возвращает текстовое сообщение об ошибке, описывающее заданный код ошибки CURLM (в 
				// переменной: $status)
				$this->cancel(0, curl_multi_strerror($status));
			}
		} while ($status === CURLM_CALL_MULTI_PERFORM || $active); // условие выхода из цикла

		$result = [];

		// Пройдёмся вв цикле по $urls и по идентификатору (ключу) в $i и получим необходимую нам информацию от конкретного 
		// дескриптора: $curl[$i]
		foreach ($urls as $i => $url) {
			// curl_multi_getcontent()- Если CURLOPT_RETURNTRANSFER является параметром, заданным для определенного дескриптора, 
			// то эта функция вернет содержимое этого дескриптора cURL в виде строки
			$result[$i] = curl_multi_getcontent($curl[$i]);
			// curl_multi_remove_handle — удаление дескриптора: $curl[$i] из мультипотока: $curlMulty
			curl_multi_remove_handle($curlMulty, $curl[$i]);
			// закроем соединение с $curl[$i], что бы удалить всё что связано с этим дескриптором
			curl_close($curl[$i]);

			// preg_match()- Поиск соответствия регулярному выражению
			if (!preg_match('/Content-Type:\s+text\/html/ui', $result[$i])) {
				$this->bad_links[] = $url;
				$this->cancel(0, 'Incorrect content type ' . $url);
				continue;
			}

			if (!preg_match('/HTTP\/\d\.?\d?\s+20\d/ui', $result[$i])) {
				$this->bad_links[] = $url;
				$this->cancel(0, 'Incorrect server code ' . $url);
				continue;
			}

			$this->createLinks($result[$i]);
		}

		// завершим мультипотоковое соединение
		curl_multi_close($curlMulty);
	}

	protected function createLinks($content)
	{
		if ($content) {

			// preg_match_all()- Выполняет поиск всех совпадений с заданным в регулярном выражении и помещает их в порядке, 
			// заданном (в круглых скобках регулярного выражения описаны переменные)
			preg_match_all('/<a\s*?[^>]*?href\s*?=\s*?(["\'])(.+?)\1[^>]*?>/ui', $content, $links);

			// разбираем то, что пришло в массив: $links (его 2-ой элемент)
			if ($links[2]) {
				foreach ($links[2] as $link) {
					if ($link === '/' || $link === SITE_URL . '/') {
						continue;
					}

					// проверим не является ли ссылка файлом (для построения карты сайта файлы не нужны)
					// пройдёмся в цикле по массиву в fileArr и каждый его хлемент сравним с концом нашей строки
					foreach ($this->fileArr as $ext) {
						if ($ext) {

							// addslashes()- Возвращает строку с обратными косыми чертами, добавленными перед символами, которые необходимо экранировать
							$ext = addslashes($ext);
							// найдём и заменим все точки на экранрованные точки в переменной: $ext
							$ext = str_replace('.', '\.', $ext);

							// проверим есть ли расширение в конце строки Если есть
							if (preg_match('/' . $ext . '(\s*?$|\?[^\/]*$)/ui', $link)) {
								// то укажем, что нужно прервать итерацию второго цикла
								continue 2;
							}
						}
					}

					// проверим ссылка относительная или абсолютная
					if (strpos($link, '/') === 0) {
						// то ссылка относительная ( в карте сайта будем размещать только абсолютные ссылки)
						// сформируем корректную переменную
						$link = SITE_URL . $link;
					}

					// str_replace() — Заменяет все вхождения строки поиска на строку замены
					// ищем точки, заменяем на экранированную точку, ищем в SITE_URL (в которой находим все слеши и меняем на 
					// экранирванные) Результат сохраняем в переменной: $site_url
					$site_url = mb_str_replace('.', '\.', mb_str_replace('/', '\/', SITE_URL));

					// выполним проверки при которых мы будем заносить ссылки в переменную
					// 1- нет ли нашей ссылки: $link в $this->bad_links
					// 2- не является ли ссылка заглушкой: # (с другими символами впереди и после неё)
					// 3- есть ли уже в $link то что хранится в константе: SITE_URL
					if (
						!in_array($link, $this->bad_links) &&
						!preg_match('/^(' . $site_url . ')?\/?#[^\/]*?$/ui', $link) &&
						strpos($link, SITE_URL) === 0 && !in_array($link, $this->all_links)
					) {
						$this->temp_links[] = $link;
						$this->all_links[] = $link;
					}
				}
			}
		}
	}

	// метод который будет фильтровать те ссылки, которые не надо парсить (которые не надо заносить в карту сайта)
	protected function filter($link)
	{
		if ($this->filterArr) {
			foreach ($this->filterArr as $type => $values) {
				if ($values) {
					foreach ($values as $item) {
						$item = str_replace('/', '\/', addslashes($item));

						if ($type === 'url') {
							if (preg_match('/^[^\?]*' . $item . '/ui', $link)) {
								return false;
							}
						}

						if ($type === 'get') {
							if (preg_match('/(\?|&amp;|=|&)' . $item . '(=|&amp;|&|$)/ui', $link, $matches)) {
								return false;
							}
						}
					}
				}
			}
		}

		return true;
	}

	// метод который проверит существует ли таблица для парсинга
	protected function checkParsingTable()
	{
		// Здесь доступно свойство: $this->model, вызовем метод: showTables() и результат созраним в переменной: $tables
		$tables = $this->model->showTables();

		// если в массиве (в $tables) нет элемента: parsing_data
		if (!in_array('parsing_data', $tables)) {
			// создадим запрос
			$query = 'CREATE TABLE parsing_data (all_links longtext, temp_links longtext, bad_links longtext)';

			if (
				!$this->model->query($query, 'c') ||
				!$this->model->add('parsing_data', [
					'fields' => ['all_links' => '', 'temp_links' => '', 'bad_links' => '']
				])
			) {
				return false;
			}
		}

		return true;
	}

	// метод который будет писать сообщение в лог, клиенту и при необходимости завершать работу скрипта 
	// на вход принимает параметры: 1- успешность, 2- сообщение для клиента (ответом js), 3- сообщение в лог, 4- завершение 
	// скрипта (по умолчанию: false)
	protected function cancel($success = 0, $message = '', $log_message = '', $exit = false)
	{
		// массив, который будем отдавать клиенту
		$exitArr = [];

		// определим что будет зраниться в ячейках массива
		$exitArr['success'] = $success;
		// ячейка: $exitArr['message'] по умолчанию будет равна переменной: $message (если такая существует) иначе служебному сообщению
		$exitArr['message'] = $message ? $message : 'ERROR PARSING';

		// проверим пришло ли что то в параметр: $log_messag Если пришло сохраняем это  в переменной: $log_message иначе запишем в неё: содержимое: $exitArr['message']
		$log_message = $log_message ? $log_message : $exitArr['message'];

		$class = 'success';

		if (!$exitArr['success']) {
			$class = 'error';
			// запишем сообщение из $log_message в файл: parsing_log.txt
			$this->writeLog($log_message, 'parsing_log.txt');
		}

		if ($exit) {
			$exitArr['message'] = '<div class="' . $class . '">' . $exitArr['message'] . '</div>';
			exit(json_encode($exitArr));
		}
	}

	// метод который будет строить карту сайта 
	protected function createSitemap()
	{
		$dom = new \domDocument('1.0', 'utf-8');
		$dom->formatOutput = true;

		$root = $dom->createElement('urlset');
		$root->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
		$root->setAttribute('xmlns:xls', 'http://w3.org/2001/XMLSchema-instance');
		$root->setAttribute('xsi:schemaLocation', 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap/xsd');

		$dom->appendChild($root);

		$sxe = simplexml_import_dom($dom);

		if ($this->all_links) {
			$date = new \DateTime();
			$lastMod = $date->format('Y-m-d') . 'T' . $date->format('H:i:s+01:00');

			foreach ($this->all_links as $item) {
				$elem = trim(mb_substr($item, mb_strlen(SITE_URL)), '/');
				$elem = explode('/', $elem);

				$count = '0.' . (count($elem) - 1);
				$priority = 1 - (float)$count;

				if ($priority == 1) {
					$priority = '1.0';
				}

				$urlMain = $sxe->addChild('url');
				$urlMain->addChild('loc', htmlspecialchars($item));
				$urlMain->addChild('lastmod', $lastMod);
				$urlMain->addChild('changefreq', 'weekly');
				$urlMain->addChild('priority', $priority);
			}
		}

		$dom->save($_SERVER['DOCUMENT_ROOT'] . PATH . 'sitemap.xml');
	}
}
