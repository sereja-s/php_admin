<?php

namespace core\base\model;

use core\base\controller\Singleton;

class Crypt
{
	use Singleton;

	// в свойстве сохраним метод шифрования
	private $cryptMethod = 'AES-128-CBC';
	// алгоритм хеширования
	private $hashAlgoritm = 'sha256';
	// длина хеша для алгоритма хеширования
	private $hashLength = 32;

	// метод для шифрования данных
	public function encrypt($str)
	{
		// Получим псевдослучайную последовательность байт определённой длины (Вектор шифрования) Это также необходимо // для шифрования и дешифрования данных Генерировать надо на основании нашего метода шифрования (из 
		// $cryptMethod) и определёной длины

		// Получим длину вектора шифрования
		$ivlen = openssl_cipher_iv_length($this->cryptMethod);
		// получим вектор шифрования
		$iv = openssl_random_pseudo_bytes($ivlen);
		// необходимо зашифровать данные (4-ым параметром передадим константу библиотеки OPENSSL: OPENSSL_RAW_DATA- 
		// вернётся строка кодированная кодировкой: строка с двоичными данными ) Эта константа равна 1
		$cipherText = openssl_encrypt($str, $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $iv);
		// для дешифровки необходимо создать хеш для шифрованной строки (4-ый параметр: true означает, что получим 
		// результат в качестве необработанных двоичных данных)
		$hmac = hash_hmac($this->hashAlgoritm, $cipherText, CRYPT_KEY, true);

		// вернём результат (данные) в кодировке: base64 (результат работы функции: cryptCombine())
		return $this->cryptCombine($cipherText, $iv, $hmac);
	}

	// метод для расшифровки данных
	public function decrypt($str)
	{
		// Получим длину вектора шифрования
		$ivlen = openssl_cipher_iv_length($this->cryptMethod);

		$crypt_data = $this->cryptUnCombine($str, $ivlen);

		// расшифровка данных (получаем оригинальный текст)
		$original_plaintext = openssl_decrypt($crypt_data['str'], $this->cryptMethod, CRYPT_KEY, OPENSSL_RAW_DATA, $crypt_data['iv']);
		// получим хеш (так же как для $hmac)
		$calcmac = hash_hmac($this->hashAlgoritm, $crypt_data['str'], CRYPT_KEY, true);

		// осуществим сравнение, которое не подвержено атаке по времени при помощи ф-ии: hash_equals() 
		// Эта функция сравнивает 2-е строки (их хеши) на идентичность (поданные на вход), используя одно и тоже время
		if (hash_equals($crypt_data['hmac'], $calcmac)) {
			return $original_plaintext;
		}

		return false;
	}

	// метод собирающий шифрованную строку
	protected function cryptCombine($str, $iv, $hmac)
	{
		// переменная с изначально пустой строкой (в неё будем добавлять)
		$new_str = '';

		$str_len = strlen($str);
		// получим счётчик с которого начнём добавление (расчётное значение, приведённое к типу int)
		// матеметическая ф-я php: ceil(), приводит дробное значение поданное на вход к ближайшемк целому числу
		$counter = (int)ceil(strlen(CRYPT_KEY) / ($str_len + $this->hashLength));
		// объявим переменную, которая будет осуществлять изменение прогресса (через сколько символов будет происзодить чередование строк)
		$progress = 1;

		// если переменная: $counter больше или равно переменной: $str_len
		if ($counter >= $str_len) {
			// то присвоим переменной: $counter значние 1
			$counter = 1;
		}

		// пройдёмся в цикле по всей длине строки
		for ($i = 0; $i < $str_len; $i++) {
			if ($counter < $str_len) {
				// если соблюдается условие, 
				if ($counter === $i) {
					// то в переменную добавим результат работы ф-ии: substr(), в которой из строки: $iv, начиная с символа под номером: ($progress - 1) вернём подстроку длиной в 1-н символ
					$new_str .= substr($iv, $progress - 1, 1);
					$progress++;
					$counter += $progress;
				}
			} else {
				break;
			}

			// в переменную добавим результат работы ф-ии: substr(), т.е. вернём подстроку длиной: 1-н символ, начиная 
			// с $i-го символа из строки: в переменной: $str
			$new_str .= substr($str, $i, 1);
		}

		// допишем строку в переменной: $new_str (после выхода из цикла) оставшимися символами строки: $str, начиная с её крайнего $i-го символа
		$new_str .= substr($str, $i);
		// затем добавим остатки строки от вектора шифрования: $iv
		$new_str .= substr($iv, $progress - 1);

		// найдём середину полученной строки
		$new_str_half = (int)ceil(strlen($new_str) / 2);

		// соберём готовую строку, конкатенировав в её середину строку которая хранится в $hmac
		$new_str = substr($new_str, 0, $new_str_half) . $hmac . substr($new_str, $new_str_half);

		// вернё строку в кодировке: base64
		return base64_encode($new_str);
	}

	// метод разбирающий шифрованную строку
	protected function cryptUnCombine($str, $ivlen)
	{
		$crypt_data = [];

		// декодируем (получим строку без base64)
		$str = base64_decode($str);

		// получим позицию с которой в строке вставлен хеш
		$hash_position = (int)ceil((strlen($str) / 2) - ($this->hashLength / 2));

		// сохраним подстроку (хеш) из строки: $str, начиная с: $hash_position, длиной указаной в текущем свойстве: $hashLength
		$crypt_data['hmac'] = substr($str, $hash_position, $this->hashLength);

		// заменим все вхождения строки поиска (хеша): $crypt_data['hmac'] на строку замены (пустую строку) в строке: $str
		$str = str_replace($crypt_data['hmac'], '', $str);

		// вычислим с какого места в полученной строке: $str начинать поиск вектора шифрования
		$counter = (int)ceil(strlen(CRYPT_KEY) / (strlen($str) - $ivlen + $this->hashLength));

		$progress = 2;
		// ячейка массива для хранения символов результирующей строки
		$crypt_data['str'] = '';
		// ячейка массива для хранения символов вектора шифрования
		$crypt_data['iv'] = '';

		for ($i = 0; $i < strlen($str); $i++) {
			// если длина вектора шифрования с длиной результирующей строки меньше длины всей строки
			if ($ivlen + strlen($crypt_data['str']) < strlen($str)) {
				if ($i === $counter) {
					// значит это символ вектора шифрования (добавим его в соответствующую ячейку массива)
					$crypt_data['iv'] .= substr($str, $counter, 1);
					$progress++;
					$counter += $progress;
					// иначе
				} else {
					// это символ результирующей строки (добавим его в соответствующую ячейку массива)
					$crypt_data['str'] .= substr($str, $i, 1);
				}
				// здесь мы вышли за пределы исхоной строки
			} else {
				// получим длину последовательности символов в ячейке: $crypt_data['str']
				$crypt_data_len = strlen($crypt_data['str']);
				// получим символы результирующей строки
				$crypt_data['str'] .= substr($str, $i, strlen($str) - $ivlen - $crypt_data_len);
				// получим символы вектора шифрования
				$crypt_data['iv'] .= substr($str, $i + (strlen($str) - $ivlen - $crypt_data_len));

				break;
			}
		}

		return $crypt_data;
	}
}
