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
	//生成pos机订单
	public function posOrder(){
		$data['series'] = $_POST['series'];
		$data['shipping_id'] = isset($_POST['shipping_id']) ? $_POST['shipping_id'] : null;
		$data['payment_id'] = isset($_POST['payment_id']) ? $_POST['payment_id'] : null;
		$data['pos_name'] = isset($_POST['pos_name']) ? $_POST['pos_name'] : null;
		$data['pos_num'] = isset($_POST['pos_num']) ? $_POST['pos_num'] : null;
		$data['message'] = isset($_POST['message']) ? $_POST['message'] : null;
		$data['order_no'] = date("ymdHis").getNumNo(6);
		$data['create_time'] = time(); 
		$data['order_status'] = 1;
		$userInfo = Db::name('users')->field('is_pos')->where('series',$data['series'])->find();
		if($userInfo['is_pos'] == 1){
			my_json_encode(10002,'您已申请过收款宝，不能重复申请');
			exit();
		}
		if($data['shipping_id'] == null || $data['payment_id'] == null || $data['pos_name'] == null || $data['pos_num'] == null){
			my_json_encode(8,'参数错误');
			exit();
		}

		$result = Db::name('pos_order')->insert($data);
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
}