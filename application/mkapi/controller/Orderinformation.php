<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Log;
class Orderinformation extends Common{
	
	//订单消息
	public function info(){
		$series = $_POST['series'];
		$orderList = Db::name('order_information')->field('a.id,a.content,a.order_id,b.delivery_no,c.goods_name,c.goods_img,a.update_time')->alias('a')->join('order b','a.order_id = b.id')->join('goods c','b.goods_id = c.id')->where('a.series',$series)->order('a.update_time desc')->select();
		foreach ($orderList as $key => $value) {
			$handle = fopen($value['goods_img'],'r');
			$imgData = fread($handle,filesize($value['goods_img']));
			$orderList[$key]['goods_img'] = base64_encode($imgData);
			fclose($handle);
		}
		my_json_encode(10000,'success',$orderList);
	}
}
	