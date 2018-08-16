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
		$secret_key = "$2a$12$M3Je3l0qTy4Gy12E6PqviuOI25cfRRpEHFI.ARteS/v8TVhBeX3na";
		$data = $_REQUEST;
        if(empty($data)){
            $data = file_get_contents("php://input");
        }

        write_to_log("【美洽美洽】".$data,'mkapi/log/');
        $meiqia_sign = $_SERVER['HTTP_AUTHORIZATION'];
        $data = json_decode($data);
        //$series = $data['customizedId'];
	}
}
