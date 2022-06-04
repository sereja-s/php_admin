<?php

namespace libraries;

class FileEdit
{
	// объявим массив, в котором соберутся все файлы (которые будут находиться в глобальном массиве ФАЙЛОВ: $_FILES)
	protected $imgArr = [];
	// свойство в котором будет храниться путь куда мы будем сохранять файлы
	protected $directory;
	// свойство (управляющий флаг) даёт возможность относительно таблицы не искать уникальное имя файла, а пересожранять его
	protected $uniqueFile = true;

	// метод для добавления файлов
	public function addFile($directory = '')
	{
		$directory = trim($directory, ' /');

		$directory .= '/';

		$this->setDirectory($directory);

		foreach ($_FILES as $key => $file) {
			// is_array() — определяет, является ли переменная: $file['name'] массивом Если да
			if (is_array($file['name'])) {
				// то обрабатываем как массив
				$file_arr = [];

				foreach ($file['name'] as $i => $value) {

					// проверим не пустой ли пришёл $file['name'] его [$i]- элемент
					if (!empty($file['name'][$i])) {

						// начинаем формировать нужный нам массив: $file_arr
						$file_arr['name'] = $file['name'][$i];
						$file_arr['type'] = $file['type'][$i];
						$file_arr['tmp_name'] = $file['tmp_name'][$i];
						$file_arr['error'] = $file['error'][$i];
						$file_arr['size'] = $file['size'][$i];

						// Далее формируем корректное имя файла и отправляем данный файл на сервер

						$res_name = $this->createFile($file_arr);

						// если в переменую: $res_name пришло имя перемещённого файла
						if ($res_name) {
							$this->imgArr[$key][$i] = $directory . $res_name;
						}
					}
				}
				// иначе обрабатываем как единичный файл
			} else {
				if ($file['name']) {
					$res_name = $this->createFile($file);

					if ($res_name) {

						$this->imgArr[$key] = $directory . $res_name;
					}
				}
			}
		}

		return $this->getFiles();
	}

	// метод, который будет создавать один конкретный файл, записывает в необходимый архив и т.д.
	protected function createFile($file)
	{
		// explode()— разбирает строку (имя файла из $file['name']) на массив по заданному разделителю (получим все элементы 
		// через разделитель точка (.))
		$fileNameArr = explode('.', $file['name']);
		// и получим его расширение
		// (в переменную: $ext положим последний элемент массива: $fileNameArr)
		$ext = $fileNameArr[count($fileNameArr) - 1];
		// далее разрегестрируем (удалим) полученное расширение файла
		unset($fileNameArr[count($fileNameArr) - 1]);

		// implode()- создаёт строку из массива ($fileNameArr, но уже без расширения) по заданному разделителю (.)
		$fileName = implode('.', $fileNameArr);

		// в переменной: $fileName сохраняем результат работы метода: translit(), который вызываем из ссылки на объект класса: TextModify 
		$fileName = (new TextModify())->translit($fileName);

		// в переменной сохраним результат работы метода, который проверит файл и вернёт корректное имя файла (с расширением)
		$fileName = $this->checkFile($fileName, $ext);

		// в переменную сохраним полный путь к файлу
		$fileFullName = $this->directory . $fileName;

		// вызовем метод: uploadFile, которая переместит файл (на вход передаём: 1-ый параметр: ячейку массива где файл 
		// находится сейчас: $file['tmp_name'] и 2-ой параметр: путь куда мы хотим переместить этот файл: $fileFullName)
		if ($this->uploadFile($file['tmp_name'], $fileFullName)) {
			return $fileName;
		}

		return false;
	}

	// метод, который перемещает файл (на вход получает временное имя файла и путь куда его надо переместить)
	protected function uploadFile($tmpName, $dest)
	{
		// move_uploaded_file() — перемещает загруженный файл в новое расположение
		if (move_uploaded_file($tmpName, $dest)) {
			return true;
		}

		return false;
	}

	// метод, который проверит файл и вернёт корректное имя файла
	// на вход передаём 2-е обязательные переменные (название файла и его расширение) и одну необязательную (ту часть имени 
	// файла, которая будет генерироваться динамически вслучае если такой файл существует)
	protected function checkFile($fileName, $ext, $fileLastName = '')
	{

		// проверим: существует ли такой файл в заданной директории Если не существует
		if (!file_exists($this->directory . $fileName . $fileLastName . '.' . $ext) || !$this->uniqueFile) {
			// вернём готовое имя файла (с расширением)
			return $fileName . $fileLastName . '.' . $ext;
		}

		// если такой файл существует, рекурсивно вызываем этот же метод на вход передаём: $fileName, $ext, а также 3-ий 
		// параметр (запишется в $fileLastName): символ (_) к которому конкатенируем функцию: hash() Она сформирует случайную 
		// строку (на вход получит: алгоритм хеширования, ф-ию которая вернёт текущую метку времени и ф-ию которая сгенерирует 
		// число в заданном диапазоне) У нас создастся: $fileLastName по текущей метке времени и т.д. и снова будет проходить 
		// проверку на существование такого файла
		return $this->checkFile($fileName, $ext, '_' . hash('crc32', time() . mt_rand(1, 1000)));
	}

	public function setUniqueFile($value)
	{

		$this->uniqueFile = $value ? true : false;
	}

	public function setDirectory($directory)
	{
		// в текущую переменную: directory сохраним путь
		$this->directory = $_SERVER['DOCUMENT_ROOT'] . PATH . UPLOAD_DIR . $directory;

		if (!file_exists($this->directory)) {

			mkdir($this->directory, 0777, true);
		}
	}

	public function getFiles()
	{
		return $this->imgArr;
	}
}
