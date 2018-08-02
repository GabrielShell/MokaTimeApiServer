<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Log;
/**
* 收款页面
*/
class Makecollections extends Common{
	//收款主页面
	public function index(){
		$request = Request::instance();
		//收集表单数据
		$channel_id = $request->post("channel_id"); //渠道号 现在默认都是1
		if($channel_id == null){
			my_json_encode(8,'参数不能为空');
		}

		$result = Db::name("channel")->field("id channel_id , channel_code , pay_rate")->where("id = $channel_id")->find();
		
		if($result){
			my_json_encode(10000,'success',$result);
		}else{
			$errorId = uniqid("sqlErr");
			Log::sql("【".$errorId."】渠道获取失败");
			my_json_encode(10002,'渠道获取失败:errorId = '.$errorId);
		}
	}


	// 收款记录
	public function recordOfReceipts(){
		$series = $_POST['series'];
		$timeType = is_null($_POST['timeType']) ? null : $_POST['timeType'];
		if($timeType == null){
			my_json_encode(8,'timeType参数不能为空');
		}
		if($timeType == 'd'){
			$beginTime = mktime(0,0,0,date('m'),date('d')-1,date('y'));
		}else if($timeType == 'm'){
			$beginTime = mktime(0,0,0,date('m')-1,date('d'),date('y'));
		}else if($timeType == 'y'){
			$beginTime = mktime(0,0,0,date('m'),date('d'),date('y')-1);
		}

		//查询收款记录 join Left
		$result = DB::name('lakala_order')->field('channel_id,order_no,order_money,arrive_money,pay_time,a.merchant_no,pay_rate,other_fee,merchant_name, b.terminalno')->alias('a')->join('merchants b','a.merchant_no = b.merchant_no','left')->where([
			'a.series' =>['=',$series],
			'trade_status' => ['=',2],
			'pay_time' => ['>',$beginTime],
		])->order('pay_time desc')->select();
		
		my_json_encode(10000,'success',$result);
	}
}