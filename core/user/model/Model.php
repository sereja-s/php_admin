<?php

namespace core\user\model;

use core\base\controller\Singleton;

class Model extends \core\base\model\BaseModel
{

	use Singleton;

	public function getGoods($set = [], &$catalogFilters = null, &$catalogPrices = null)
	{

		if (empty($set['join_structure'])) {

			$set['join_structure'] = true;
		}

		if (empty($set['where'])) {

			$set['where'] = [];
		}

		// соберём сортировку по умолчанию
		if (empty($set['order'])) {

			$set['order'] = [];

			if (!empty($this->showColumns('goods')['parent_id'])) {

				$set['order'][] = 'parent_id';
			}

			if (!empty($this->showColumns('goods')['price'])) {

				$set['order'][] = 'price';
			}
		}

		$goods = $this->get('goods', $set);

		// все действия выполняем если пришли товары
		if ($goods) {

			unset($set['join'], $set['join_structure'], $set['pagination']);

			if ($catalogPrices !== false && !empty($this->showColumns('goods')['price'])) {

				$set['fields'] = ['MIN(price) as min_price', 'MAX(price) as max_price'];

				$catalogPrices = $this->get('goods', $set);

				if (!empty($catalogPrices[0])) {

					$catalogPrices = $catalogPrices[0];
				}
			}

			if ($catalogFilters !== false && in_array('filters', $this->showTables())) {

				$parentFiltersFields = [];

				$filtersWhere = [];

				$filtersOrder = [];

				foreach ($this->showColumns('filters') as $name => $item) {

					if (!empty($item) && is_array($item)) {

						$parentFiltersFields[] = $name . ' as f_' . $name; // что бы отличать родителя от значения
					}
				}


				if (!empty($this->showColumns('filters')['visible'])) {

					$filtersWhere['visible'] = 1;
				}

				if (!empty($this->showColumns('filters')['menu_position'])) {

					$filtersOrder[] = 'menu_position';
				}

				// получаем фильтры
				$filters = $this->get('filters', [

					'where' => $filtersWhere,
					'join' => [
						'filters f_name' => [
							'type' => 'INNER',  // т.к. нам не нужно чтобы приходило значение если нет родителя
							'fields' => $parentFiltersFields,
							'where' => $filtersWhere,
							// укажем признак (из предыдущей таблицы- поле: parent_id смотрит на текущую- поле: id)
							'on' => ['parent_id', 'id']
						],

						// нам нужен джоин (связь) с таблицей связей
						'goods_filters' => [
							'on' => [
								'table' => 'filters',
								// поле из предыдущей таблицы (id) должно смотреть на поле текущей (filters_id)
								'fields' => ['id', 'filters_id']
							],
							'where' => [
								// строим подзапрос (вложенный запрос)
								'goods_id' => $this->get('goods', [
									'fields' => [$this->showColumns('goods')['id_row']],
									'where' => $set['where'] ?? null,
									'return_query' => true
								])
							],
							'operand' => ['IN'],
						]
					],

					// 'return_query' => true
				]);

				if (!empty($this->showColumns('goods')['discount'])) {

					foreach ($goods as $key => $item) {

						$this->applyDiscount(!empty($data[$key]), $item['discount']);
					}
				}

				if ($filters) {

					// implode() — объединение элементов массива со строкой
					// (Возвращает строку, содержащую строковое представление всех элементов массива в одном порядке со 
					// строкой-разделителем (здесь- запятая) между каждым элементом)
					// array_column() — возвращает значения из одного столбца во входном массиве
					$filtersIds = implode(',', array_unique(array_column($filters, 'id')));

					$goodsIds = implode(',', array_unique(array_column($filters, 'goods_id')));

					$query = "SELECT `filters_id` as id, COUNT(goods_id) as count FROM goods_filters WHERE filters_id IN ($filtersIds) AND goods_id IN ($goodsIds) GROUP BY filters_id";

					$goodsCountDb = $this->query($query);

					$goodsCount = [];

					if ($goodsCountDb) {

						foreach ($goodsCountDb as $item) {

							$goodsCount[$item['id']] = $item;
						}
					}

					// формируем фильтр каталога
					$catalogFilters = [];

					foreach ($filters as $item) {

						$parent = [];

						$child = [];

						foreach ($item as $row => $rowValue) {

							// определим родительскую категорию
							if (strpos($row, 'f_') === 0) {

								$name = preg_replace('/^f_/', '', $row);

								$parent[$name] = $rowValue;
							} else {

								$child[$row] = $rowValue;
							}
						}

						if (isset($goodsCount[$child['id']]['count'])) {

							$child['count'] = $goodsCount[$child['id']]['count'];
						}

						if (empty($catalogFilters[$parent['id']])) {

							$catalogFilters[$parent['id']] = $parent;

							// создадим элемент для сбора значений фильтров
							$catalogFilters[$parent['id']]['values'] = [];
						}

						$catalogFilters[$parent['id']]['values'][$child['id']] = $child;

						if (isset($goods[$item['goods_id']])) {

							if (empty($goods[$item['goods_id']]['filters'][$parent['id']])) {

								$goods[$item['goods_id']]['filters'][$parent['id']] = $parent;
								$goods[$item['goods_id']]['filters'][$parent['id']]['values'] = [];
							}

							$goods[$item['goods_id']]['filters'][$parent['id']]['values'][$item['id']] = $child;
						}
					}
				}
			}
		}

		return $goods ?? null;
	}

	public function applyDiscount(&$data, $discount)
	{

		if ($discount) {

			$data['old_price'] = $data['price'];

			$data['discount'] = $discount;

			$data['price'] = $data['old_price'] - ($data['old_price'] / 100 * $discount);
		}
	}
}
