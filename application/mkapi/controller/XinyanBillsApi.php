<?php
namespace app\mkapi\controller;
use app\mkapi\controller\Common;
use think\Controller;
use think\Request;
use app\mkapi\common\CurlRequest;
use app\mkapi\common\Xinyan\Order;
use app\mkapi\common\Xinyan\Crypt;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Bills;
use app\mkapi\model\Shopping_records;

/**
 * 新颜账单查询API接口
 */
class XinyanBillsApi extends Common{
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
        //默认请求头
        private $headers;

        public function __construct(){
                parent::__construct();
                $this->headers = [
                        "memberId:".$this->member_id,
                        "terminalId:".$this->terminal_id
                ];
        }

        /**
         * 邮箱方式获取账单API预订单接口
         */
        public function getEmailOrderNo(){
                $txnType = 'email';
                $this->getOrderNo($txnType);
        }

        /**
         * 网银方式获取账单API预订单接口
         */
        public function getBankOrderNo(){
                $txnType = 'bank';
                $this->getOrderNo($txnType);
        }

        /**
         * 获取预订单号(预订单) 
         */
	public function getOrderNo($txnType){
                $responseData = array();
                $requestData = array();

                $request = Request::instance();
                //$path = dirname($request->baseFile())."/static/mkapi/";


                header("Content-type: text/html; charset=utf-8");

                //======预订单======
                //测试地址
                //        $preOrderAuthUrl="http://test.xinyan.com/gateway-data/data/v1/preOrderRsa";

                $preOrderAuthUrl="https://api.xinyan.com/gateway-data/data/v1/preOrderRsa";


                //  **组装参数(15)**
                $order = new Order();
                $trans_id=$order->create_uuid();//商户订单号
                $trade_date=$order->trade_date();//交易时间

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


                $crypt = new Crypt();
                $data_content = $crypt->encryptedByPrivateKey($this->pfxpath, $this->cerpath, $this->pfx_pwd,TRUE,$data_content);
        
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

        /**
         * 邮箱查询账单接口
         * @param orderNo 预订单号
         */
        public function emailQueryBills(Request $request){
                $billsUrl="https://api.xinyan.com/data/email/v2/bills/";

                $orderNo = $request->post('orderNo');
                $requestUrl = $billsUrl.$orderNo;

                $result = CurlRequest::get($requestUrl,$this->headers);

                $data = json_decode($result,true);
                if($data["success"] == 'true'){
                        //每条账单查询对应的消费记录
                        foreach($data['data']['bills'] as &$bill){
                                $shoppingRecord = [];
                                $shoppingRecordResult = $this->emailQueryShoppingRecords($orderNo,$bill['bill_id']);

                                if($shoppingRecordResult['success'] == 'true')
                                        $shoppingRecord = $shoppingRecordResult['data']['shopping_sheets'];
                                
                                $bill['shopping_records'] = $shoppingRecord;
                        }

                        //查找数据库是否已有该信用卡，没有则插入信用卡，有则更新信用卡数据
                        $searched = []; //已经在数据库找到的记录 key=unique_string value=card_id
                        foreach($data['data']['bills'] as $bill){
                                $unique_string = $bill['bank_id'] . $bill['name_on_card'] . $bill['card_number'];
                                $card_id = 0;
                                if(!isset($searched[$unique_string])){
                                        //如果未搜索过数据库则搜索数据库
                                        $card = Credit_cards::get(['unique_string' => $unique_string]);
                                        if(!$card){
                                                //如果搜索数据库没有该信用卡，则执行插入操作
                                                $card = new Credit_cards;
                                                $card->unique_string = $unique_string;
                                                $card->bank_id = $bill['bank_id'];
                                                $card->name_on_card = $bill['name_on_card'];
                                                $card->card_no_last4 = $bill['card_number'];
                                                $card->card_no = $bill['card_no'];
                                                $card->bill_date = date('d',strtotime($bill['bill_date']));
                                                if(date('m',strtotime($bill['payment_due_date'])) == date('m',strtotime($bill['bill_date']))){
                                                        $card->due_date = '-'.date('d',strtotime($bill['payment_due_date']));
                                                }else {
                                                        $card->due_date = '*'.date('d',strtotime($bill['payment_due_date']));
                                                }
                                                $card->credit_limit = $bill['credit_limit'];
                                                $card->point = $bill['point'];
                                                $card->save();

                                                $searched[$unique_string] = $card->id;
                                                $card_id = $card->id;
                                        }else{
                                                $searched[$unique_string] = $card->id;
                                                $card_id = $card->id;
                                        }
                                }else{
                                        //已经搜索过数据库不需要重复搜索
                                        $card_id = $searched[$unique_string];
                                }

                                //将账单插入数据库
                                $bill_record  = new Bills;
                                $bill_record->series = $bill['bill_id'];
                                $bill_record->credit_card_id = $card_id;
                                $bill_record->origin_type = 'email';
                                $bill_record->bill_start_date = $bill['bill_start_date'];
                                $bill_record->bill_date = $bill['bill_date'];
                                $bill_record->payment_due_date = $bill['payment_due_date'];
                                $bill_record->credit_limit = $bill['credit_limit'];
                                $bill_record->usd_credit_limit = $bill['usd_credit_limit'];
                                $bill_record->new_balance = $bill['new_balance'];
                                $bill_record->usd_new_balance = $bill['usd_new_balance'];
                                $bill_record->min_payment = $bill['min_payment'];
                                $bill_record->usd_min_payment = $bill['usd_min_payment'];
                                $bill_record->point = $bill['point'];
                                $bill_record->save();
                                
                                foreach($bill['shopping_records'] as $shopping_record){
                                        $db_shopping_record = new Shopping_records;
                                        $db_shopping_record->credit_card_id = $card_id;
                                        $db_shopping_record->bill_id = $bill_record->id;
                                        $db_shopping_record->amount_money = $shopping_record['amount_money'];
                                        $db_shopping_record->trans_addr = $shopping_record['trans_addr'];
                                        $db_shopping_record->trans_date = $shopping_record['trans_date'];
                                        $db_shopping_record->trans_type = $shopping_record['trans_type'];
                                        $db_shopping_record->bank_id = $shopping_record['bank_id'];
                                        $db_shopping_record->card_no_last4 = $shopping_record['card_no'];
                                        $db_shopping_record->currency_type = $shopping_record['currency_type'];
                                        $db_shopping_record->description = $shopping_record['description'];
                                        $db_shopping_record->post_date = $shopping_record['post_date'];
                                        $db_shopping_record->save();
                                }
                        }
                }
                $result = ['code'=>0,'msg'=>'账单导入成功!'];
                return json_encode($result);
        }

        /**
         * 邮箱查询账单消费记录
         */
        public function emailQueryShoppingRecords($orderNo,$billId){
                $page = 1;
                $size = 1000;
                $shoppingUrl="https://api.xinyan.com/data/email/v2/bills/shopping/";
                $requestUrl = $shoppingUrl.$orderNo."?billId=".$billId."&page=".$page."&size=".$size;

                $result = CurlRequest::get($requestUrl,$this->headers);
                return json_decode($result,true);
        }

        /**
         * 查询支持银行列表接口
         */

}
