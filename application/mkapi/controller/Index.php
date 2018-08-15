<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
class Index extends Controller{
	public function index(){
		echo PHP_EOL;
		return $this->fetch();
	}

	public function login(){
		return $this->fetch();
	}
}