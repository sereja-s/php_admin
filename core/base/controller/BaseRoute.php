<?php

namespace core\base\controller;

class BaseRoute
{
	use Singleton, BaseMethods;

	public static function routeDirection()
	{
		// в статическом контексте, конструкция $this не доступна 
		//(т.к. она является ссылкой на объект класса, а объкта класса здесь не существует Мы просто ипользуем метод класса, не создавая объект)
		// Здесь используется конструкция (ключевое слово) self которая,означает ,что мы ссылаемся на наш собственный класс)

		// сделаем проверку выполнения условия:		
		if (self::instance()->isAjax()) {
			exit((new BaseAjax())->route());
		}

		RouteController::instance()->route();
	}
}
