<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use app\mkapi\common\CurlRequest;
class Pacong extends Controller{
        private $rsapath= "http://jcq.s8pos.com/Uploads/";//证书路径，网上环境得路径;
        //默认编码格式
        private $char_set="UTF-8";
        //商户私钥
        private $pfxpath="http://jcq.s8pos.com/Uploads/xinyan_pri.pfx";
        //商户私钥密码
        private $pfx_pwd="jsd123456";
        //公钥
        private $cerpath= "http://jcq.s8pos.com/Uploads/xinyan_pub.cer";
        //终端号
        private $terminal_id="8150712518";
        //商户号
        private $member_id="8150712518";
        private $data_type="json";

	public function getO(){
                $responseData = array();
                $requestData = array();

                $request = Request::instance();
                //$path = dirname($request->baseFile())."/static/mkapi/";

                header("Content-type: text/html; charset=utf-8");

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
                        "memberId"=>$this->member_id,
                        "terminalId"=>$this->terminal_id,
                        "transId"=>$trans_id,
                        "txnType"=>$txnType,
                        "notifyUrl"=>""
                );
                // *** 数据格式化***
                $data_content="";
                //==================转换数据类型=============================================

                if($this->data_type == "json"){
                        $data_content = str_replace("\\/", "/",json_encode($arrayData));//转JSON
                }


                $Pacongjiami = new Pacongjiami();
                $data_content = $Pacongjiami->encryptedByPrivateKey($this->pfxpath, $this->cerpath, $this->pfx_pwd,TRUE,$data_content);
        
                /**============== http 请求==================== **/
                $request_url=$preOrderAuthUrl;
                $PostArry = array(
                        "memberId" =>$this->member_id,
                        "terminalId" => $this->terminal_id,
                        "dataContent" => $data_content
                );

                $return = CurlRequest::Post($PostArry, $request_url); 
                echo $return;
                exit();
                //发送请求到服务器，并输出返回结果。

                if(empty($return)){
                        throw new Exception("返回为空，确认是否网络原因！");
                }
                //** 处理返回的报文 */
                $a = json_decode($return,true) ;
                $responseData['success'] = $a['success'];
                $responseData['orderId'] = $a['data'];


                my_json_encode(10000, $responseData);
    }

    public function queryResult(Request $request){
        $billsUrl="https://api.xinyan.com/data/email/v2/bills/";

        $orderNo = $request->post('orderNo');
        $request_url = $billsUrl.$orderNo;

        $headers = array(
                "memberId:".$this->member_id,
                "terminalId:".$this->terminal_id
            );
        $result = CurlRequest::get($request_url,$headers);
        return $result;
    }

    
}
