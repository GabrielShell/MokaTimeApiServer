<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
use app\mkapi\common\Merqia\DTSigner;
class Push extends Controller{
	public function pushMeiqia(){
		$secret_key = '$2a$12$M3Je3l0qTy4Gy12E6PqviuOI25cfRRpEHFI.ARteS/v8TVhBeX3na';
		$data = $_REQUEST;
        if(empty($data)){
            $data = file_get_contents("php://input");
        }
        //美洽验签
        $signer = new DTSigner($secret_key);
        if($signer->sign($data) !== $_SERVER['HTTP_AUTHORIZATION']){
        	write_to_log("【美洽验签成功】");
        	exit();
        }else{
        	write_to_log("【美洽验签失败】sign1=".$signer->sign($data)." sign2=".$_SERVER['HTTP_AUTHORIZATION']);
        }
        $data = json_decode($data);
        $series = $data['customizedId'];
	}
}
