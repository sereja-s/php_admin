<?php

namespace core\user\controller;

use core\user\helpers\ValidationHelper;

class OrdersController extends BaseUser
{

	use ValidationHelper;

	protected $delivery = [];
	protected $payments = [];

	protected function inputData()
	{
		parent::inputData();

		if ($this->isPost()) {

			$this->delivery = $this->model->get('delivery');
			$this->payments = $this->model->get('payments');

			$this->order();
		}
	}

	protected function order()
	{
	}
}
