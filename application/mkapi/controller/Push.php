<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
class Push extends Controller{
	public function pushMeiqia(){
		$data = $_REQUEST;
        if(empty($data)){
            $data = file_get_contents("php://input");
        }

        write_to_log('【美洽消息推送】'.$data,'mkapi/log/');
        write_to_log('【美洽消息推送-请求头】'.json_encode($_SERVER),'mkapi/log/');
	}
}