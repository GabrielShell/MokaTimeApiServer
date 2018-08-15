<?php

/**
*唤醒App
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
class Wakeup extends Controller{
	public function index(){
		$series = $_POST['series'];
		$access_token = $_POST['access_token'];
		$refresh_token = $_POST['refresh_token'];
		if($series == null || $access_token == null || $refresh_token == null){
			my_json_encode(8,'参数错误');
		}

		$result = Db::query("select * from mk_token_messages where series = '$series' and re_start_time > unix_timestamp()-2592000");
		if(empty($result)){
			my_json_encode(7, 'token过期，请重新登录');
		}else{
			$data['access_token'] = getkey(32);
			$data['refresh_token'] = getkey(32);
			$data['ac_start_time'] = time();
			$data['re_start_time'] = time();
			$result = Db::name('token_messages')->where('series',$series)->update($data);
			if($result){
				my_json_encode(10000,'success',array(
					'access_token' => $data['access_token'],
					'refresh_token' => $data['refresh_token']
				));
			}
		}
	}
}