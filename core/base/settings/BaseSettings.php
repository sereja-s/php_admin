<?php

namespace core\base\settings;

use core\base\controller\Singleton;
use core\base\settings\Settings;

trait BaseSettings
{
	use Singleton {
		// зададим псевдоним методу: instance трейта: Singleton и далее обращаться к этому методу по его псевлониму
		instance as SingletonInstance;
	}

	// определим (объявим) свойство
	private $baseSettings;

	static public function get($property)
	{
		return self::instance()->$property;
	}

	static public function instance()
	{

		// instanceof используется для определения того, является ли переменная PHP экземпляром объекта определенного класса
		if (self::$_instance instanceof self) {
			return self::$_instance;
		}

		// Обратимся к трейту Singleton, чтобы он вернул объект нашего класса,для этого обратимся к методу этого класса по 
		// псевдониму: SingletonInstance(), затем к свойству baseSettings объекта класса 
		// и сохраним в нём ссылку на объект класса Settings вызвав его метод instance()
		self::SingletonInstance()->baseSettings = Settings::instance();

		// определим (создадим) переменную $baseProperties в которую сохраним результат работы функции,
		// которая будет клеять свойства: clueProperties(get_class()-в параметры передаём имя текущего класса); к которой мы 
		// обратились используя статическое свойство $_instance и затем свойство baseSettings (в котором хранится объект нашего класса)
		// (функция (метод) clueProperties() описана в файле Settings.php)
		$baseProperties = self::$_instance->baseSettings->clueProperties(get_class());
		// у нашего свойства self::$_instance мы должны вызвать метод setProperties() и передать туда $baseProperties
		self::$_instance->setProperties($baseProperties);

		// после того как склеятся все свойства у нас вернётся объект нашего класса в котором будут доступны все свойства (наших основных настроек и настроек плагина)
		return self::$_instance;
	}

	// создадим метод чтобы получить доступ к необходимым свойствам (запишет нам то что пришло в массив $baseProperties ) 
	// и создать их внутри объекта нашего класса
	// на вход приходит массив свойств
	protected function setProperties($properties)
	{
		// если свойства пришли (из $baseProperties)
		if ($properties) {
			// запускаем цикл и пробегаем по массиву свойств ($properties) как ключ ($name) и значение свойств ($property)
			foreach ($properties as $name => $property) {
				// в переменную $name запишем (сохраним) соответствующие свойства
				$this->$name = $property;
			}
		}
	}
}
