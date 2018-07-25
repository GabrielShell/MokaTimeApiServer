<?php
namespace app\mkapi\controller;
use app\mkapi\controller\Common;
use think\Controller;
use think\Request;
use think\Log;
use app\mkapi\common\CurlRequest;
use app\mkapi\common\Xinyan\Order;
use app\mkapi\common\Xinyan\Crypt;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Bills;
use app\mkapi\model\Shopping_records;
use app\mkapi\model\Xinyan_banks;

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
                return $this->getOrderNo($txnType);
        }

        /**
         * 网银方式获取账单API预订单接口
         */
        public function getBankOrderNo(){
                $txnType = 'bank';
                return $this->getOrderNo($txnType);
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
                $arr_result = json_decode($return,true);
                if(is_array($arr_result) && $arr_result['success'] == 'true'){
                        return json_encode(['status'=>0,'msg'=>'预订单成功！','data'=>$arr_result['data']]);
                }else{
                        $errorId = uniqid("ERR");
                        Log::error('【'.$errorId.'】新颜API-预订单接口错误(bills),API接口返回信息不能解析，API接口返回信息：'.$return);
                        return json_encode(['status'=>1,'msg'=>'查询失败！操作ID:'.$errorId],JSON_UNESCAPED_UNICODE);
                }
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
                if(is_array($data) && $data["success"] == 'true'){
                        //每条账单查询对应的消费记录
                        foreach($data['data']['bills'] as &$bill){
                                $shoppingRecord = [];
                                $shoppingRecordResult = $this->emailQueryShoppingRecords($orderNo,$bill['bill_id']);

                                if($shoppingRecordResult['success'] == 'true')
                                        $shoppingRecord = $shoppingRecordResult['data']['shopping_sheets'];
                                
                                $bill['shopping_records'] = $shoppingRecord;
                        }

                        //查询支持银行列表
                        $supportBanks = Xinyan_banks::all();
                        $banks = [];
                        foreach($supportBanks as $supportBank){
                                $banks[$supportBank->id] = $supportBank->bank_name;
                        }


                        //查找数据库是否已有该信用卡，没有则插入信用卡，有则更新信用卡数据
                        $searched = []; //已经在数据库找到的记录 key=unique_string value=card_id
                        foreach($data['data']['bills'] as $bill){
                                $unique_string = $banks[$bill['bank_id']] .'-'. $bill['name_on_card'] .'-'. $bill['card_number'];
                                $card_id = 0;
                                if(!isset($searched[$unique_string])){
                                        //如果未搜索过数据库则搜索数据库
                                        $card = Credit_cards::get(['unique_string' => $unique_string]);
                                        if(!$card){
                                                //如果搜索数据库没有该信用卡，则执行插入操作
                                                $card = new Credit_cards;
                                                $card->unique_string = $unique_string;
                                                $card->bank_name = $banks[$bill['bank_id']];
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
                                $bill_record->new_balance = $bill['new_balance'];
                                $bill_record->min_payment = $bill['min_payment'];
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
                                        $db_shopping_record->bank_name = $banks[$shopping_record['bank_id']];
                                        $db_shopping_record->card_no_last4 = $shopping_record['card_no'];
                                        $db_shopping_record->currency_type = $shopping_record['currency_type'];
                                        $db_shopping_record->description = $shopping_record['description'];
                                        $db_shopping_record->post_date = $shopping_record['post_date'];
                                        $db_shopping_record->save();
                                }
                        }
                }else{
                        $errorId = uniqid("ERR");
                        Log::error('【'.$errorId.'】新颜API-查询邮箱账单接口错误(bills),API接口返回信息不能解析，API接口返回信息：'.$result);
                        return json_encode(['status'=>1,'msg'=>'查询失败！操作ID:'.$errorId]);
                }
                $result = ['status'=>0,'msg'=>'账单导入成功!'];
                return json_encode($result,JSON_UNESCAPED_UNICODE);
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
        public function querySupportBanks(){
                $querySupportBanksUrl = 'https://api.xinyan.com/gateway-data/bank/v1/config/list';
                $result = CurlRequest::get($querySupportBanksUrl,$this->headers);
                $data = json_decode($result,true);
                if(!$data || $data['success'] !='true'){
                        $errorId = uniqid("ERR");
                        Log::error('【'.$errorId.'】新颜API-查询支持银行列表接口错误(support-banks),API接口返回信息不能解析，API接口返回信息：'.$result);
                        return json_encode(['status'=>1,'msg'=>'查询失败！操作ID:'.$errorId]);
                }

                foreach($data['data'] as $bankList){
                        $cardType = $bankList['card_type'];
                        foreach($bankList['bank_list'] as $supportBank){
                                $bankRecord = Xinyan_banks::get(['bank_name'=>$supportBank['name']]);
                                if($bankRecord){
                                        $bankRecord->bank_abbr = $supportBank['abbr'];
                                        if($cardType == 'CREDITCARD'){
                                                $bankRecord->credit_support = 1;
                                        }else{
                                                $bankRecord->debit_support = 1;
                                        }
                                        $bankRecord->save();
                                }
                        }
                }

                $supportBanks = Xinyan_banks::field('bank_name,bank_abbr')->where('credit_support' , 1)->select();
                $return = ['status'=>0,'msg'=>'查询成功！','data'=>$supportBanks];
                return json_encode($return,JSON_UNESCAPED_UNICODE);
        }

        /**
         * 查询银行登录配置信息
         */
        public function queryBankConfigLogin(Request $request){
                $bankcode = $request->post('bankcode');
                $cardtype = $request->post('cardtype');
                $configLoginUrl = 'https://api.xinyan.com/gateway-data/bank/v1/config/login/';
                $requestUrl = $configLoginUrl.$bankcode.'/'.$cardtype;
                $result = CurlRequest::get($requestUrl,$this->headers);
                $arr_result = json_decode($result,true);

                $return = [];
                if($arr_result && $arr_result['success'] == 'true'){
                        $return = ['status'=>0,'msg' => '查询成功！','data'=>$arr_result['data']['logins']];
                }else{
                        $errorId = uniqid("ERR");
                        $return = ['status'=>1,'msg' => '查询失败！操作ID:'.$errorId];
                        Log::error('【'.$errorId.'】新颜API-查询银行登录配置信息错误（config-login）,API接口返回信息不能解析，API接口返回信息：'.$result);
                }
                return json_encode($return,JSON_UNESCAPED_UNICODE);
        }

        /**
         * 网银账单查询创建任务
         */
        public function cyberBankQueryTaskCreate(Request $request){
                $bank = $request->post('bank');
                $account=$request->post('account');
                $password=$request->post('password');
                $login_target='CREDITCARD';
                $login_type=$request->post('login_type');
                $id_card=$request->post('id_card');
                $real_name=$request->post('real_name');
                $origin=$request->post('origin');
                $notify_url="";
                $area_code="";
                $location="";
                $gps="";
                $ip="";

                $order = new Order();
                $member_trans_id=$order->create_uuid();//商户订单号
                $member_trans_date=$order->trade_date();//交易时间
                $user_id=$order->create_uuid();

                $arrayData=array(
                        "member_id"=>$this->member_id,
                        "terminal_id"=>$this->terminal_id,
                        "member_trans_date"=>$member_trans_date,
                        "member_trans_id"=>$member_trans_id,
                        "notify_url"=>$notify_url,
                        "user_id"=>$user_id,
                        "bank"=>$bank,
                        "account"=>$account,
                        "password"=>$password,
                        "login_target"=>$login_target,
                        "login_type"=>$login_type,
                        "origin"=>$origin,
                        "id_card"=>$id_card,
                        "real_name"=>$real_name,
                        "area_code"=>$area_code,
                        "location"=>$location,
                        "gps"=>$gps,
                        "ip"=>$ip
                );

                // *** 数据格式化***
                $data_content="";
                //==================转换数据类型=============================================
                if($this->data_type == "json"){
                        $data_content = str_replace("\\/", "/",json_encode($arrayData));//转JSON
                }


                $crypt = new Crypt();
                $data_content = $crypt->encryptedByPrivateKey($this->pfxpath, $this->cerpath, $this->pfx_pwd,TRUE,$data_content);

                $PostArry = array(
                        "member_id" =>$this->member_id,
                        "terminal_id" => $this->terminal_id,
                        "data_type" => $this->data_type,
                        "data_content" => $data_content
                );

                $PostArryJson = str_replace("\\/", "/",json_encode($PostArry));//转JSON
                $requestUrl = 'https://api.xinyan.com/gateway-data/bank/v1/task/create';

                $header = array(
                        'Content-Type: application/json; charset=utf-8',
                        'Content-Length: ' . strlen($PostArryJson)
                );

                $result = CurlRequest::request($requestUrl,'post',$PostArryJson,$header,20);
                $arr_result = json_decode($result[0],true);
                if(is_array($arr_result) && $arr_result['success'] == 'true'){
                        $return = ['status' => 0,'msg'=>'创建任务成功！','data'=>['tradeNo'=>$arr_result['data']['tradeNo']]];
                }else{
                        $errorId = uniqid("ERR");
                        $return = ['status'=>1,'msg' => '创建任务失败！操作ID:'.$errorId];
                        Log::error('【'.$errorId.'】新颜API-创建任务失败（task-create）,API接口返回信息不能解析，API接口返回信息：'.$result[0]);
                }
                return json_encode($return,JSON_UNESCAPED_UNICODE);
        }

        /**
         * 网银账单状态查询接口
         */
        public function cyberBankQueryTaskStatus(Request $request){
                $tradeNo = $request->post('tradeNo');
                $requestUrl = 'https://api.xinyan.com/gateway-data/bank/v1/task/status'.'/'.$tradeNo;
                $result = CurlRequest::get($requestUrl,$this->headers);
                $arr_result = json_decode($result,true);
                if(is_array($arr_result) && $arr_result['success'] == 'true'){
                        $return = ['status'=>0,'msg'=>'状态查询成功！','data'=>$arr_result['data']];
                }else{
                        $errorId = uniqid("ERR");
                        $return = ['status'=>1,'msg' => '【'.$errorId.'】新颜API-查询状态失败（task-status）,API接口返回信息不能解析，API接口返回信息：'.$result];
                        Log::error('【'.$errorId.'】新颜API-查询状态失败（task-status）,API接口返回信息不能解析，API接口返回信息：'.$result);
                }
                return json_encode($return,JSON_UNESCAPED_UNICODE);

        }


        /**
         * 网银账单查询验证码输入接口
         */
        public function cyberBankQueryTaskInput(Request $request){
                $tradeNo = $request->post('tradeNo');
                $input = $request->post('input');
                $requestUrl = 'https://api.xinyan.com/gateway-data/bank/v1/task/input'.'/'.$tradeNo;
                $PostArray = ['input'=>$input];
                $PostArryJson = str_replace("\\/", "/",json_encode($PostArray));//转JSON
                //对于json 设置请求头
                $header = array(
                        'Content-Type: application/json; charset=utf-8',
                        'Content-Length: ' . strlen($PostArryJson),
                        "memberId:".$this->member_id,
                        "terminalId:".$this->terminal_id
                );
                $result = CurlRequest::request($requestUrl,'post',$PostArryJson,$header,20);
                $arr_result = json_decode($result[0],true);
                if(is_array($arr_result) && $arr_result['success'] == 'true'){
                        $return = ['status' => 0,'msg'=>'验证码已输入！','data'=>['tradeNo'=>$arr_result['data']['tradeNo']]];
                }else{
                        $errorId = uniqid("ERR");
                        $return = ['status'=>1,'msg' => '验证码输入失败！操作ID:'.$errorId];
                        Log::error('【'.$errorId.'】新颜API-验证码输入失败（task-input）,API接口返回信息不能解析，API接口返回信息：'.$result[0]);
                }
                return json_encode($return,JSON_UNESCAPED_UNICODE);
        }


        /**
         * 网银账单查询银行卡卡号和账单信息
         */
        public function cyberBankQueryBills(Request $request){
                $tradeNo = $request->post('tradeNo');
                $requestUrl = 'https://api.xinyan.com/data/bank/v2/bills/id/';
                $requestUrl .= $tradeNo;
                $result = CurlRequest::get($requestUrl,$this->headers);
                $arr_result = json_decode($result,true);
                if(is_array($arr_result) && $arr_result['success'] == 'true'){
                        $return = ['status'=>0,'msg'=>'查询成功！','data'=>$arr_result['data']];
                }else{
                        $errorId = uniqid("ERR");
                        $return = ['status'=>1,'msg' => '查询状态失败！操作ID:'.$errorId];
                        Log::error('【'.$errorId.'】新颜API-查询失败（bank-bills）,API接口返回信息不能解析，API接口返回信息：'.$result[0]);
                }
                return json_encode($return,JSON_UNESCAPED_UNICODE);
        }

        /**
         * 网银查询任务采集的银行卡所有信息
         */
        public function cyberBankQueryCards(Request $request){
                $tradeNo = $request->post('tradeNo');
                $requestUrl = 'https://api.xinyan.com/data/bank/v2/cards/all/'.$tradeNo;
                $result = CurlRequest::get($requestUrl,$this->headers);
                $arr_result = json_decode($result,true);

                if(is_array($arr_result) && $arr_result['success'] == 'true'){
                        //将查询的数据写入数据库
                        foreach($arr_result['data'] as $card){
                                //如果账单具有信用额度，则为信用卡，只处理信用卡
                                if(isset($card['bills'][0]) && $card['credit_limit'] > 0){
                                        $bank_name = $card['bank_name'];
                                        $name_on_card = $card['name_on_card'];
                                        $card_no = $card['full_card_num'];
                                        $card_no_last4 = substr($card['full_card_num'],-4);
                                        $credit_limit = $card['credit_limit'];
                                        $balance = $card['balance'];
                                        //获取账单日还款日
                                        $bill_date = $card['bills'][0]['bill_date'];
                                        $due_date = $card['bills'][0]['payment_due_date'];
                                        $unique_string = $bank_name.'-'.$name_on_card.'-'.$card_no_last4;

                                        //验证卡片是否已经存在于数据库
                                        $cardDbInstance = Credit_cards::get(['unique_string'=>$unique_string]);
                                        if($cardDbInstance){
                                                //如果该卡片已存在，则更新该信用卡记录

                                        }else{
                                                //不存在，则创建信用卡记录

                                        }


                                }
                        }
                        $return = ['status'=>0,'msg'=>'查询成功！'];
                }else{
                        $errorId = uniqid("ERR");
                        $return = ['status'=>1,'msg' => '查询状态失败！操作ID:'.$errorId];
                        Log::error('【'.$errorId.'】新颜API-查询失败（bank-cards）,API接口返回信息不能解析，API接口返回信息：'.$result[0]);
                }
                return json_encode($return,JSON_UNESCAPED_UNICODE);
        }

}
