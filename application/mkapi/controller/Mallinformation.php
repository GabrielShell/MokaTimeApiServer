<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Log;
class Mallinformation extends Common{
	/**
	*获取系统推送消息
	*/
	public function info(){
		$series = $_POST['series'];
		$systemMessageList = Db::name('user_mall')->field('a.id,b.title,b.content,is_read,is_new,a.create_time')->alias('a')->join('mall_information b','a.mall_id = b.id')->where('series',$series)->order('a.create_time desc')->select();

		my_json_encode(10000,'success',$systemMessageList);
	}

	public function setIsNew(){
		$series = $_POST['series'];
		//更新消息状态
		Db::name('user_mall')->where([
			'series' => $series,
			'is_new' => 'y'
		])->update(['is_new'=>'n']);
		my_json_encode(10000,'success');
	}

	//获取新消息数量
	public function newNum(){
		$series = $_POST['series'];
		$newNum = Db::name('user_mall')->where([
			'series' => $series,
			'is_new' => 'y'
		])->count();
		my_json_encode(10000,'success',['newNum' => $newNum]);
	}

	//设置消息为新消息（测试用）
	public function setNew(){
		$series = $_POST['series'];
		$result = Db::name('user_mall')->where([
			'series' => $series,
			'is_new' => 'n'
		])->update(['is_new'=>'y']);
		my_json_encode(10000,'success',['affectRow'=>$result]);
	}

	public function setIsRead(){
		$id = $_POST['id'];
		$result = Db::name('user_mall')->where([
			'id' => $id,
			'is_read' => 'n'
		])->update(['is_read'=>'y']);
		my_json_encode(10000,'success');
	}

}
	