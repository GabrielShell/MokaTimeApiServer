<?php
/*
*商户开通接口测试
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use crypt\AesCbc;
use think\Log;
class Callback extends Controller{
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

    /**
    *开通商户回调
    */
	public function register(){

        $data = $_REQUEST;
        if(empty($data)){
            $data = file_get_contents("php://input");
        }
        if(!empty($data)){
            write_to_log('【开通商户回调信息：】 '.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/callback/');
            // Log::init(['type' => 'file', 'path' => APP_PATH . 'mkapi/log/lakala/callback/']);
            // Log::error(date("y-m-d H:i:s").'【开通商户回调信息】'.json_encode($data,JSON_UNESCAPED_UNICODE));
            $coreData = base64_decode($data['param']);
            $coreData = json_decode($coreData,true);
            $AES = new AesCbc($this->_LklAesKey);
            $decryData = $AES->decryptString($coreData['params']);
            $decryData = json_decode($decryData,true);
            //Log::error('开通商户解密： '.json_encode($data,JSON_UNESCAPED_UNICODE));
            write_to_log('【开通商户回调信息解密：】'.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/callback/');
            // $checkSign = $AES->checkSign($decrypted, $coreData['sign'], $this->_LklDecryptKeyPath);
            // if ($checkSign) {
            //     $decrypted = json_decode($decrypted, true);


            //     //巧玲修改
            //     $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/register';
            //     $this->transfer($data,$decrypted,'partnerUserId',$url);
            //     //巧玲修改


            //     if (!empty($coreData['ver'])){
            //         //写入
            //         $posManage['status'] = 1;
            //         $posManage['bind_time'] = time();
            //         $posManage['merchantId'] = $decrypted['merId'];
            //         $result = M("Posmanagement")->where("tel='%s' AND pos_id=1", array($decrypted['partnerUserId']))->save($posManage);
            //         if (!$result) {
            //             write_to_log('拉卡拉注册/绑定通知-无此用户' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
            //             exit();
            //         }
            //         //只有注册/绑定时才需要返回以下数据给拉卡拉
            //         $param['isSuccess'] = 'Y';
            //         $param['partnerTime'] = date("YmdHis");
            //         $json = json_encode($param, JSON_UNESCAPED_UNICODE);
            //         $map['ver'] = $coreData['ver'];
            //         $map['reqId'] = $coreData['reqId'];
            //         $map['params'] = $AES->encryptString($json);
            //         $map['sign'] = $AES->sign($json, $this->_LklEncryptKeyPath);
            //         $map2Json = json_encode($map, JSON_UNESCAPED_UNICODE);
            //         write_to_log('拉卡拉注册/绑定通知输出给拉卡拉的内容' . $map2Json, '/WxApi/Log/Lakala/');
            //         write_to_log('拉卡拉注册/绑定通知输出给拉卡拉的内容' . json_encode($decrypted, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
            //         echo $map2Json;
            //         sleep(5);
            //         //开通D0
            //         $result = $this->openD0($decrypted['merId']);
            //         if ($result['status'] == 1){
            //             $manage['openStatus'] = 1;
            //             M("Posmanagement")->where("tel='%s' AND pos_id=1", array($decrypted['partnerUserId']))->save($manage);
            //             write_to_log('拉卡拉-开通D0成功' . $result['msg'], '/WxApi/Log/Lakala/');
            //         }else{
            //             write_to_log('拉卡拉-开通D0失败' . $result['msg'], '/WxApi/Log/Lakala/');
            //         }
            //     }
            // }
            // write_to_log('拉卡拉注册/绑定通知-验签失败' . json_encode($decrypted), '/WxApi/Log/Lakala/');
        }    
    }

    /**
    *开通D0回调
    */
    public function openD0CallBack(){
        $request = Request::instance();
        $data = $request->param();
        write_to_log('【开通D0回调信息：】 '.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/callback/');
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