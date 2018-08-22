<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Log;
class Sysinformation extends Common{
	/**
	*获取系统推送消息
	*/
	public function system(){
		$series = $_POST['series'];
		$systemMessageList = Db::name('system_information')->field('id,title,content,is_read,is_new,create_time')->where('series',$series)->order('id desc')->select();

		//更新消息状态
		Db::name('system_information')->where([
			'series' => $series,
			'is_new' => 'y'
		])->update(['is_new'=>'n']);
		my_json_encode(10000,'success',$systemMessageList);
	}

	//获取新消息数量
	public function newNum(){
		$series = $_POST['series'];
		$newNum = Db::name('system_information')->where([
			'series' => $series,
			'is_new' => 'y'
		])->count();
		my_json_encode(10000,'success',['newNum' => $newNum]);
	}

	//设置消息为新消息（测试用）
	public function setNew(){
		$series = $_POST['series'];
		$result = Db::name('system_information')->where([
			'series' => $series,
			'is_new' => 'n'
		])->update(['is_new'=>'y']);
		my_json_encode(10000,'success',['affectRow'=>$result]);
	}

	public function setIsRead(){
		$id = $_POST['id'];
		$result = Db::name('system_information')->where([
			'id' => $id,
			'is_read' => 'n'
		])->update(['is_read'=>'y']);
		my_json_encode(10000,'success',['affectRow'=>$result]);
	}

	// //订单消息
	// public function order(){
	// 	$series = $_POST['series'];
	// 	$orderList = Db::name('order')->field('a.content,a.update_time,c.goods_name,c.goods_img')->alias('a')->join('order b','a.order_id = b.id')->join('goods c','b.goods_id = c.id')->where('series',$series)->order('update_time desc')->select();
	// 	my_json_encode(10000,'success',$orderList);
	// }
}
	