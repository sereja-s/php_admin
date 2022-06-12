<?php

namespace core\user\controller;

use core\admin\models\Model;
use core\base\controllers\BaseController;
use core\base\models\Crypt;

class IndexTestController extends BaseUser
{

	protected $name;

	protected function inputData()
	{

		parent::inputData();

		$years = $this->wordsForCounter(111);

		$a = 1;
	}
}
