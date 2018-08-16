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
  //       if(empty($data)){
  //           $data = file_get_contents("php://input");
  //           write_to_log("【返回数据流】".$data,"mkapi/log/");
  //       }
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
}
