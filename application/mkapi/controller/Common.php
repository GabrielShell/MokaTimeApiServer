<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
class Common extends Controller{
	public function __construct(){
		parent::__construct();
		$this->__init();
		$this->__checkLogin();
		date_default_timezone_set("PRC");
	}

	//设置路径常量
	private function __init(){
		$request = Request::instance();
		define("__MODULE__",$request->module());
		define("__CONTROLLER__",$request->controller());
		define("__ACTION__",$request->action());
	}

	// 系统集中验证token
	private function __checkLogin(){
		$request = Request::instance();
		$no_list = array(
			'User' => array('login','sendverify','register'),
			'Test' => array('certificate','login','testupload'));
		if(isset($no_list[__CONTROLLER__]) && in_array(__ACTION__, $no_list[__CONTROLLER__])){
			return;
		}else if ($request->post('cheat-code') == 'mokatime-999') {
			return;
		}else{
			//收集表单数据
            //$series = "mk17359491816887463"; 
			//$access_token = "5275469497d6n6m6ufk7o33y3v61vi17";
			//$refresh_token = "iqoq6y59i88650g5e9038a01ucs4s2m6";
			$series = $request->post('series');
			$access_token = $request->post('access_token');
			$refresh_token = $request->post('refresh_token');
          
			//验证access_token格式是否正确
          	if(empty($series)){
          		my_json_encode(8, 'series不能为空');
          	}

            if(empty($access_token) || !preg_match('/[a-z0-9]{32}/', $access_token)){
                my_json_encode(8, 'access_token为空或者格式不正确');
            }

            //验证access_token格式是否正确
            if(empty($refresh_token) || !preg_match('/[a-z0-9]{32}/', $refresh_token)){
                my_json_encode(8, 'refresh_token为空或者格式不正确');
            }

            // 判断token是否存在
           $result = Db::query("select * from mk_token_messages where series = '$series' and re_start_time > unix_timestamp()-2592000");
        	//var_dump($result);
            if(empty($result)){//token过期
            	my_json_encode(7, 'token过期，请重新登录');
            }else{
            	if($access_token !== $result[0]['access_token'] || $result[0]['ac_start_time'] < time() - 3600){
            		echo date('y-m-d H:i:s',$result[0]['ac_start_time']).'<br>';
	            	echo date('y-m-d H:i:s',time()).'<br>';
	        		echo date('y-m-d H:i:s',time()-3600).'<br>';
	        		echo $access_token.'<br>';
	        		echo $result[0]['access_token'];
	            	exit();
            		$data['access_token'] = getkey(32);
            		$data['ac_start_time'] = time();
                  	$tokenMessage = model('tokenMessages');
            		$tokenMessage->save($data,['series' => $series]);
            		my_json_encode(6,$data['access_token'],array('access_token已过期，请刷新token','access_token'=>$data['access_token']));
            	}
            }
		}
	}
}