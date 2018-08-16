<?php
/*
*商城管理
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
class Shopping extends Common{
	//生成商品订单
	public function createOrder(){
		$data['series'] = $_POST['series'];
		$data['shipping_id'] = isset($_POST['shipping_id']) ? $_POST['shipping_id'] : null;
		$data['payment_id'] = isset($_POST['payment_id']) ? $_POST['payment_id'] : null;

		$data['pay_money'] = isset($_POST['pay_money']) ? $_POST['pay_money'] : null;
		$data['goods_name'] = isset($_POST['goods_name']) ? $_POST['goods_name'] : null;
		$data['goods_num'] = isset($_POST['goods_num']) ? $_POST['goods_num'] : null;
		$data['order_type'] = isset($_POST['order_type']) ? $_POST['order_type'] : null;
		$data['goods_money'] = isset($_POST['goods_money']) ? $_POST['goods_money'] : null;
		$data['order_money'] = isset($_POST['order_money']) ? $_POST['order_money'] :null;

		$data['message'] = isset($_POST['message']) ? $_POST['message'] : null;
		$data['order_no'] = date("ymdHis").getNumNo(6);
		$data['create_time'] = time(); 
		$data['order_status'] = 1;
		//判断参数是否正确
		if($data['payment_id'] == null || $data['pay_money'] == null || $data['goods_name'] == null || $data['goods_num'] == null || $data['order_type'] == null || $data['goods_money'] == null || $data['order_money'] == null){
			my_json_encode(8,'参数错误');
			exit();
		}

		//如果订单类型为申请pos机
		if($data['order_type'] ==  1){
			$userInfo = Db::name('users')->field('is_pos')->where('series',$data['series'])->find();	
			if($userInfo['is_pos'] == 1){
				my_json_encode(10002,'您已申请过收款宝，不能重复申请');
				exit();
			}
			$data['order_status'] = 2;

		}

		//未传地址id ,新增收货地址
		if($data['shipping_id'] == null){
			$addrData['series'] = $_POST['series'];
			$addrData['province'] = isset($_POST['province']) ? $_POST['province'] : null;
			$addrData['city'] = isset($_POST['city']) ? $_POST['city'] : null;
			$addrData['district'] = isset($_POST['district']) ? $_POST['district'] : null;
			$addrData['street'] = isset($_POST['street']) ? $_POST['street'] : null;
			$addrData['consignee'] = isset($_POST['consignee']) ? $_POST['consignee'] : null;
			$addrData['phone'] = isset($_POST['phone']) ? $_POST['phone'] : null;
			$addrData['is_default'] = isset($_POST['is_default']) ? $_POST['is_default'] : null;
			$addrData['create_time'] = time();
			//判断参数是否传正确了
			if($addrData['province'] == null || $addrData['city'] == null || $addrData['district'] == null || $addrData['street'] == null || $addrData['consignee'] == null || $addrData['phone'] == null || $addrData['is_default'] == null){
				my_json_encode(8,'参数错误');
				exit();
			}

			//更改默认地址
			if($_POST['is_default'] == 1){
				Db::name('shipping')->where('series',$data['series'])->update(['is_default'=>0]);
			}
			
		    $addrResult = Db::name('shipping')->insert($addrData);
		    if(!$addrResult){
		    	echo '地址信息插入失败';
		    	exit();
		    }
			$data['shipping_id'] = Db::name('shipping')->getLastInsID();
		}

		//创建订单
		$result = Db::name('order')->insert($data);
		if(!$result){
			$errorId = uniqid('ERR');
			Log::error('【'.$errorId.'】 订单储存错误');
			my_json_encode(10002,'订单储存错误：errorId='.$errorId);
		}else{
			$result = Db::name('users')->where('series',$data['series'])->update(['is_pos'=>'1']);
			if($result){
				my_json_encode(10000,'success');
			}else{
				$errorId = uniqid('ERR');
				Log::error('【'.$errorId.'】 订单储存错误');
				my_json_encode(10002,'数据更新失败：errorId='.$errorId);
			}
		}
	}

	//我的订单
	public function userOrder(){
		$series = $_POST['series'];
		// $orderList = Db::name("order")->where()
	}
}