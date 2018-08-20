<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Log;
class Sysmessage extends Common{
	/**
	*获取系统推送消息
	*/
	public function systemMessage(){
		$series = $_POST['series'];
		$systemMessageList = Db::name('system_message')->field('title,content,is_read,is_new,create_time')->where('series',$series)->select();
		$systemMessageList = array_unique($systemMessageList);
	}
}
	