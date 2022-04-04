<?php

use Phalcon\Mvc\Model;

class Permissions extends Model
{
    public $id;
    public $role;
    public $controller;
    public $action;
}