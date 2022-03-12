<?php

namespace core\user\controller;

use core\admin\model\Model;
use core\base\controller\BaseController;

class IndexController extends BaseUser {

    protected $name;

    protected function inputData()    {
        parent::inputData();

        $alias = '';

        $res = $this->alias(['catalog' => 'auto', 'vasya' => 'petya'], ['page' => 1, 'order' => 'desc']);

        $a = 1;
    }
}