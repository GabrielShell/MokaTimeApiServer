<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Log;
use app\mkapi\common\Meiqia\DTSigner;
use app\mkapi\common\UMeng\Umeng;
class Push extends Controller{
	
	public function pushMeiqia(){
		$secret_key = '$2a$12$M3Je3l0qTy4Gy12E6PqviuOI25cfRRpEHFI.ARteS/v8TVhBeX3na';
		// $data = $_REQUEST;
		// if(empty($data)){
		//      $data = file_get_contents("php://input");
		  //      write_to_log("【返回数据流】".$data,"mkapi/log/");
		  // }
      	$data = file_get_contents("php://input");
        //美洽验签
        $signer = new DTSigner($secret_key);
        if($signer->sign($data) !== $_SERVER['HTTP_AUTHORIZATION']){
        	exit();
        }else{
	        $data = json_decode($data,true);
	        $series = $data['customizedId'];
	        // 判断token是否存在
	        $result = Db::query("select * from mk_token_messages where series = '$series' and re_start_time > unix_timestamp()-2592000");
	        if(!$result){
	        	exit();
	        }else{
	        	$umeng = new Umeng();
		        $param = array(
		            'device_tokens' => $data['deviceToken']
		            ,'ticker' => $data['fromName']
		            ,'title' => "摩卡时代"
		            ,'text' => $data['content']
		        );
		        $umeng->sendAndroidUnicast($param);
	        }
        }
	}

	/**
    *系统消息推送
    *@param array $param 推送消息数组
    *
    */
	public function pushSystem($UMengdata){
		//推送消息
        $UMengdata['create_time'] = time();
        //获取用户设备号
        $userInfo =Db::name('users')->field('device_token')->where('series',$UMengdata['series'])->find();
        $UMengdata['device_token'] = $userInfo['device_token'];

        //储存系统推送消息
        if(isset($UMengdata['unique'])){
        	$result = Db::name('system_information')->field("unique")->where('unique',$UMengdata['unique'])->find();
	       	if($result){
	       		return;
	       	}
        }
        
       	Db::name('system_information')->insert($UMengdata);

   		$umeng = new Umeng();
        $Umengparam = array(
            'device_tokens' => $UMengdata['device_token']
            ,'ticker' => '摩卡时代-系统消息'
            ,'title' => $UMengdata['title']
            ,'text' => $UMengdata['content']
        );
        $umeng->sendAndroidUnicast($Umengparam);	
	}
}
