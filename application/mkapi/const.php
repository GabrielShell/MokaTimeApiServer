<?php
use think\Request;
$request = Request::instance();
// defined("__MODULE__",$request->module());
defined("__CONTROLLER__",$request->controller());
defined("__CONTROLLER__",$request->action());