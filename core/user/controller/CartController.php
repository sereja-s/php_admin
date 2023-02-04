<?php

namespace core\user\controller;

class CartController extends BaseUser
{

	// Выпуск №144 | Пользовательская часть | удаление и очистка корзины

	// получим оплату и доставку (объявим свойства)
	protected $delivery;
	protected $payments;

	// Выпуск №143 | Пользовательская часть | Корзина товаров | ч 1
	protected function inputData()
	{
		parent::inputData();


		/* $_SESSION['res']['phone'] = '9999999999999';

		$this->userData = [
			'name' => 'Masha',
			'phone' => '78965411223',
			'email' => 'mail@mail.ru'
		]; */

		$this->delivery = $this->model->get('delivery');
		$this->payments = $this->model->get('payments');

		if (!empty($this->parameters['alias']) && $this->parameters['alias'] === 'remove') {

			if (!empty($this->parameters['id'])) {

				$this->deleteCartData($this->parameters['id']);
			} else {

				$this->clearCart();
			}

			$this->redirect($this->alias('cart'));
		}

		//$a = 1;
	}
}
