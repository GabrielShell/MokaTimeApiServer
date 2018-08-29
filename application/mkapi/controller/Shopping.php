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
		$data['series']        = $_POST['series'];
		$data['shipping_id']   = isset($_POST['shipping_id']) ? $_POST['shipping_id'] : null;
		$data['payment_id']    = isset($_POST['payment_id'])  ? $_POST['payment_id'] : null;
		$data['pay_money']     = isset($_POST['pay_money'])   ? $_POST['pay_money'] : null;
		$data['order_type']    = isset($_POST['order_type'])  ? $_POST['order_type'] : null;
		$data['order_money']   = isset($_POST['order_money']) ? $_POST['order_money'] :null;
		$data['message']       = isset($_POST['message'])     ? $_POST['message'] : null;
		$data['goods_id']      = isset($_POST['goods_id'])    ? $_POST['goods_id'] : null;
		$data['goods_num']     = isset($_POST['goods_num'])   ? $_POST['goods_num'] : null;
		$data['attribute_ids'] = isset($_POST['attribute_ids']) ? $_POST['attribute_ids'] : null;
		$data['order_no']      = date("ymdHis").getNumNo(6);
		$data['create_time']   = time(); 
		$data['order_status']  = 1;
		$data['express_money'] = 0;
		//判断参数是否正确
		if($data['payment_id'] == null || $data['pay_money'] == null || $data['goods_id'] == null || $data['goods_num'] == null || $data['order_type'] == null || $data['order_money'] == null || $data['attribute_ids'] == null){
			my_json_encode(8,'参数错误');
			exit();
		}

		// 如果订单类型为申请pos机
		// if($data['order_type'] ==  1){
		// 	$userInfo = Db::name('users')->field('is_pos')->where('series',$data['series'])->find();	
		// 	if($userInfo['is_pos'] == 1){
		// 		my_json_encode(10002,'您已申请过收款宝，不能重复申请');
		// 		exit();
		// 	}
		// 	$data['order_status'] = 2;
		// }

		//判断用户是否已经领过限领商品
		if($data['order_type'] ==  1){
			$orderInfo = Db::name('order')->field('id')->where([
				'series'        => ['=', $data['series']],
				'goods_id'      => ['=', $data['goods_id']],
				'order_status'  => ['<', 6]
			])->find();

			if(!empty($orderInfo)){
				my_json_encode(10002,'您已申请过此商品，不能重复申请');
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
			my_json_encode(10000,'success');
			// $result = Db::name('users')->where('series',$data['series'])->update(['is_pos'=>'1']);
			// if($result){
			// 	my_json_encode(10000,'success');
			// }else{
			// 	$errorId = uniqid('ERR');
			// 	Log::error('【'.$errorId.'】 订单储存错误');
			// 	my_json_encode(10002,'数据更新失败：errorId='.$errorId);
			// }
		}
	}

	//我的订单
	public function userOrder(){
		$series = $_POST['series'];
		$order_status = isset($_POST['order_status']) ? $_POST['order_status'] : null;
		//获取全部订单
		if($order_status == null){
			$orderList = Db::name("order")->field('goods_name,goods_thumb,attribute_ids as goods_attribute,order_status,goods_money,order_money,express_money,goods_num,limit_money as promotion_price')->alias('a')->join('goods b','a.goods_id = b.id')->where([
				'series'       => ['=',$series],
				'order_status' => ['<',7]
			])->select();
		}else{
			//获取指定订单
			$orderList = Db::name("order")->field('goods_name,goods_thumb,attribute_ids as goods_attribute,order_status,goods_money,order_money,express_money,goods_num,limit_money as promotion_price')->alias('a')->join('goods b','a.goods_id = b.id')->where([
				'series'       => ['=',$series],
				'order_status' => ['=',$order_status]
			])->select();
		}

		foreach($orderList as $key1 => $value1){
			//获取商品属性
			$attributeList = Db::name('goods_attribute')->field('attribute_name,attribute_value')->where('id','in',$value1['goods_attribute'])->select();

			//=============================属性信息重组=========================//
			$attributeArray = array();
			foreach ($attributeList as $key2 => $value2) {
				$attributeArray[] = $value2['attribute_name'].':'. $value2['attribute_value'];
			}

			$orderList[$key1]['goods_attribute'] = $attributeArray;
			//=============================属性信息重组=========================//

			$handle = fopen($value1['goods_thumb'],'r');
			$imgData = fread($handle,filesize($value1['goods_thumb']));
			$orderList[$key1]['goods_thumb'] = base64_encode($imgData);
			fclose($handle);
		}

		my_json_encode(10000,'success',$orderList);
	}
}