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
}