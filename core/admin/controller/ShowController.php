<?php

namespace core\admin\controller;

use core\base\settings\Settings;
use core\base\settings\ShopSettings;


class ShowController extends BaseAdmin
{
	protected function inputData()
	{
		if (!$this->userId) {
			// вызовем метод inputData() родительского класса BaseAdmin обратившись к методу execBase()
			$this->execBase();
		}

		// вызовем метод, который определяет в какой таблице работать и заполняет свойства (здесь- table(таблицы))
		$this->createTableData();

		// вызовем метод, который будет получать необходимые для нашего шаблона вывода данные из текущей таблицы
		$this->createData();

		return $this->expansion();
	}

	// метод, который будет получать необходимые для нашего шаблона вывода данные из текущей таблицы (её определи в методе createTableData())
	// на вход методу передадим необязательную переменную: массив полей- $arr
	protected function createData($arr = [])
	{
		// определим три массива: поля, сортировка и направление сортировки
		$fields = [];
		$order = [];
		$order_direction = [];

		// если поля columns с ячейкой id_row не пришли из БД
		if (!$this->columns['id_row']) {
			// вернём в свойство $this->data пустой массив (св-во $this->data изначально и так с пустым массивом (по умолчанию))
			return $this->data = [];
		}

		// если поля columns с ячейкой id_row пришли из БД, заполним массив $fields[]:
		// в массив $fields[] добавим поля columns с ячейкой id_row и конкатенируем фразу: побел as id (псевдоним поля
		// (теперь как бы не называлось поле с идентификатором (здесь- id_row), в шаблоне мы будем работать с выборкой, которая будет называться id))
		$fields[] = $this->columns['id_row'] . ' as id';

		// проверим есть ли в полях в св-ве columns ячейки name и img
		if ($this->columns['name']) {
			// если есть в св-ве columns ячейка name, то в массив $fields (его ячейку name) запишем слово: name
			// (изначально в $fields призодит нумерованнцй массив, а имена даём для удобства работы с ним)
			$fields['name'] = 'name';
		}

		// тоже самое проделаем для ячейки img
		if ($this->columns['img']) {
			$fields['img'] = 'img';
		}

		// если количество полей меньше трёх (т.е. поле name или(и) поле img не записались)
		if (count($fields) < 3) {

			// тогда пройдёмся по массиву columns, что б понять есть ли там иные поля схожие по типу
			foreach ($this->columns as $key => $item) {

				// если нет ячейки name и в переменой key (ключе) ищем на любой позиции слово name (и находим т.е. строго не равно false)
				if (!$fields['name'] && strpos($key, 'name') !== false) {
					// то в массиве fields (ячейке name) сохраняем ключ и конкатенируем к нему псевдоним поля (через пробел): as name
					$fields['name'] = $key . ' as name';
				}

				// тоже самое проделаем для ячейки img (только ищем строгое равенство нулю (т.е. стоит на первом месте массива))
				if (!$fields['img'] && strpos($key, 'img') === 0) {
					$fields['img'] = $key . ' as img';
				}
			}
		}

		// если пришёл массив $arr и его ячейка: fields, добавляем его к текущей выборке $fields (значит нашему вспомогательному 
		// шаблону нужны какие то дополнительные данные)
		if ($arr['fields']) {
			if (is_array($arr['fields'])) {
				// склеим массивы, переданные в параметры нашей ф-ии: arrayMergeRecursive() и сохраним в св-ве $fields
				// (сначала обратились к классу: Settings, у него вызвали статический метод: instance(), который вернёт объект 
				// класса Settings или создаст новый объект и исходя из этого обращаемся к методу: arrayMergeRecursive())
				$fields = Settings::instance()->arrayMergeRecursive($fields, $arr['fields']);
			} else {
				// иначе в ячейку массива fields запишем массив: arr (его ячейку: fields)
				$fields[] = $arr['fields'];
			}
		}

		// проверим наличие ячейки: parent_id в выборке: columns (если есть, то по ней первой будем сортировать)
		if ($this->columns['parent_id']) {
			// если нет в массиве: fields ячейки: parent_id, то в добавим в массив: fields строку: parent_id
			if (!in_array('parent_id', $fields)) $fields[] = 'parent_id';
			// также в массив: order добавм строку: parent_id
			$order[] = 'parent_id';
		}

		// проверим наличие ячейки: menu_position в выборке: columns Если она есть
		if ($this->columns['menu_position']) {

			// то в массив: order добавм строку: menu_position () (что бы теперь по ней сортировались)
			$order[] = 'menu_position';

			// дополнительная проверка: если есть ячейка: дата в выборке: columns
		} elseif ($this->columns['date']) {

			// если в order уже что то есть (т.е. пришло parent_id, а значит способ сортировки (order_direction): ASC)
			if ($order) {
				// то в order_direction запишем массив: 1-ое поле (parent_id) сортировать как ASC, а 2-ое (date)-как DESC
				$order_direction = ['ASC', 'DESC'];
				// иначе
			} else {
				// в 1-ый элемент массива: order_direction запишем строку: DESC (т.е. ячейку даты сортируем как DESC (от конца к началу))
				$order_direction[] = 'DESC';
			}

			// в ячейку массива: order добавим поле: date
			$order[] = 'date';
		}

		// если пришёл массив $arr и его ячейка: order, добавляем его к текущей выборке $order
		if ($arr['order']) {
			if (is_array($arr['order'])) {
				// склеим массивы, переданные в параметры нашей ф-ии: arrayMergeRecursive() и сохраним в св-ве $order
				// (сначала обратились к классу: Settings, у него вызвали статический метод: instance(), который вернёт объект 
				// класса Settings или создаст новый объект и исходя из этого обращаемся к методу: arrayMergeRecursive())
				$order = Settings::instance()->arrayMergeRecursive($order, $arr['order']);
			} else {
				// иначе в ячейку массива order запишем массив: arr (его ячейку: order)
				$order[] = $arr['order'];
			}
		}

		// аналогично действуем с выборкой: $order_direction
		if ($arr['order_direction']) {
			if (is_array($arr['order_direction'])) {
				$order_direction = Settings::instance()->arrayMergeRecursive($order_direction, $arr['order_direction']);
			} else {
				$order_direction[] = $order_direction['order_direction'];
			}
		}

		// в свойство: $this->data вернём результат работы метода: get() модели (на вход ему подаём: таблицу ($this->table) и 
		// массив, который нам надо собрать)
		$this->data = $this->model->get($this->table, [
			'fields' => $fields,
			'order' => $order,
			'order_direction' => $order_direction
		]);
	}
}
