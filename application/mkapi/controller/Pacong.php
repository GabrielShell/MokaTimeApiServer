<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
class Pacong extends Controller{
	public function getO(){
        $responseData = array();
        $requestData = array();

       $request = Request::instance();
       // $requestData['token'] = $this->_post('token');
       // $requestData['sign'] = $this->_post('sign');
       // write_to_log("Pacong---getOrder(获取邮箱订单号)测试返回结果：".$requestData['token'].'---'.$requestData['sign'], '/WxApi/Log/bank/');
      //  $result = $this->checkSign($requestData);
      //  if($result[0] != 1){
        //    my_json_encode(10008, '验签失败');
       // }

        header("Content-type: text/html; charset=utf-8");
//====================配置商户的宝付接口授权参数============================
       // $path = $_SERVER['DOCUMENT_ROOT']."/jcq/";
        $path = dirname($request->baseFile())."/static/mkapi/";
       
//        $rsapath = "D:/wamp64/www/jcq/Uploads/";	//证书路径,本地路径是这样
        $rsapath = "http://jcq.s8pos.com/Uploads/";//证书路径，网上环境得路径


//        require_once($path."../pacong/HttpCurl.php");
//
//        require_once($path."WxApi/pacong/BaofooUtils.php");
//        require_once($path."WxApi/pacong/Log.php");
//        require_once($path."WxApi/pacong/BFRSA.php");
//        require_once($path."WxApi/pacong/HttpClient.php");

//默认编码格式
        $char_set="UTF-8";
//商户私钥   ---请修改为自己的//
//        $pfxpath="8000013189_pri.pfx";
        $pfxpath=$rsapath."xinyan_pri.pfx";//正式环境

//商户私钥密码 ---请修改为自己的//
//        $pfx_pwd="217526";
        $pfx_pwd="jsd123456";
//公钥 ---请修改为自己的//
//        $cerpath="bfkey_8000013189.cer";
//        $cerpath="bfkey_8150712518.cer";//正式环境
        $cerpath = $rsapath."xinyan_pub.cer";//另外一个正式环境

//终端号 ---请修改为自己的//
//        $terminal_id="8000013189";
        $terminal_id="8150712518";//正式环境
//商户号 ---请修改为自己的//
//        $member_id="8000013189";
        $member_id="8150712518";//正式环境
//数据类型////json/xml
        $data_type="json";

//======预订单======
//测试地址
//        $preOrderAuthUrl="http://test.xinyan.com/gateway-data/data/v1/preOrderRsa";

        $preOrderAuthUrl="https://api.xinyan.com/gateway-data/data/v1/preOrderRsa";

        $txnType="email";//交易类型

//  **组装参数(15)**
        $Pacongorder = new Pacongorder();
        $trans_id=$Pacongorder->create_uuid();//商户订单号
        $trade_date=$Pacongorder->trade_date();//交易时间

        $arrayData=array(
            "memberId"=>$member_id,
            "terminalId"=>$terminal_id,
            "transId"=>$trans_id,
            "txnType"=>$txnType,
            "notifyUrl"=>""
        );
// *** 数据格式化***
        $data_content="";
//==================转换数据类型=============================================

        if($data_type == "json"){
            $data_content = str_replace("\\/", "/",json_encode($arrayData));//转JSON
        }


        //write_to_log("====请求明文：". $data_content, '/WxApi/Log/pacong/');

//        Log::LogWirte("====请求明文：".$data_content);
//        if (!file_exists($pfxpath)) { //检查文件是否存在
//            var_dump(11111);
//            write_to_log("=====私钥不存在", '/WxApi/Log/pacong/');
////            Log::LogWirte("=====私钥不存在");
//            exit;
//        }
//        if (!file_exists($cerpath)) { //检查文件是否存在
//            var_dump(2222222);
//            write_to_log("=====公钥不存在", '/WxApi/Log/pacong/');
////            Log::LogWirte("=====公钥不存在");
//            exit;
//        }

       // write_to_log($pfxpath." ".$cerpath." ".$pfx_pwd, '/WxApi/Log/pacong/');

//        Log::LogWirte($pfxpath." ".$cerpath." ".$pfx_pwd);
// **** 先BASE64进行编码再RSA加密 ***
//        $BFRsa=A(Pacongjiami)->Pacongsd($pfxpath, $cerpath, $pfx_pwd,TRUE);//实例化加密类。

//        $BFRsa = new BFRSA($pfxpath, $cerpath, $pfx_pwd,TRUE); //实例化加密类。

        $Pacongjiami = new Pacongjiami();
        $data_content = $Pacongjiami->encryptedByPrivateKey($pfxpath, $cerpath, $pfx_pwd,TRUE,$data_content);
    
       // write_to_log("====加密串".$data_content, '/WxApi/Log/pacong/');
//        var_dump($data_content);exit();

//        Log::LogWirte("====加密串".$data_content);
        /**============== http 请求==================== **/
        $request_url=$preOrderAuthUrl;
        $PostArry = array(
            "memberId" =>$member_id,
            "terminalId" => $terminal_id,
            "dataContent" => $data_content);

        $Pacongpost = new Pacongpost();
        $return = $Pacongpost->Post($PostArry, $request_url); 
        echo $return;
        exit();
         //发送请求到服务器，并输出返回结果。

      //  write_to_log("请求返回参数：".$return, '/WxApi/Log/pacong/');
//        Log::LogWirte("请求返回参数：".$return);
        if(empty($return)){
            throw new Exception("返回为空，确认是否网络原因！");
        }
//** 处理返回的报文 */
     //   write_to_log("结果：".$return, '/WxApi/Log/pacong/');

//        Log::LogWirte("结果：".$return);
        $a = json_decode($return,true) ;
//        var_dump($a);exit();
        $responseData['success'] = $a['success'];
        $responseData['orderId'] = $a['data'];


        my_json_encode(10000, $responseData);
    }

    
}
