<?php
/*
*商户开通接口测试
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use crypt\AesCbc;
class Callback extends Common{
	//拉卡拉交易D0提款参数
    // 机构号： 425510
    // private $_LklCompOrgCode = 'QFTMPOS';
    // private $_LklHashKey = 'mUb46HfgUDfygDq8KrbZTNRObQwhBeFv';
   	private $_LklCompOrgCode = 'QFDT';
   	private $_LklHashKey = 'wxd9c866ad31c3c6wxd9c866ad31c3c6';
    //拉卡拉服务器端参数
    private $_LklAesKey = '340D2C2F15204082B14092DDE811AA22';
    private $_LklEncryptKeyPath = './WxApi/Public/key/ct_rsa_private_key.pem';
    private $_LklDecryptKeyPath = './WxApi/Public/key/lkl_public_key.pem';
//    private $_LklAesKey = '12345678901234561234567890123456';
//    private $_LklEncryptKeyPath = './WxApi/Public/key/lkl_private_key.pem';
//    private $_LklDecryptKeyPath = './WxApi/Public/key/test_lkl_public_key.pem';
	public function register(){

        $data = $_REQUEST;
        if(empty($data)){
            $data = file_get_contents("php://input");
            write_to_log('开通商户回调信息： '.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/test/');
            return 'true';
        }
        
        
    }
    public function openD0CallBack(){
        $request = Request::instance();
        $data = $request->param();
        write_to_log('新增商户进件回调信息'.json_encode($data),"mkapi/log/test/");
    }
    private function echo_json($status, $msg, $data=array()){
        header('Content-type: text/json;charset=utf-8');
        echo json_encode(array(
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ), JSON_UNESCAPED_UNICODE);exit;
    }
}