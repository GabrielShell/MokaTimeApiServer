<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use crypt\AesCbc;
use think\Log;
/**
* 开通lakala商户
*/
class Lakalaapi extends Common{
	
	private $_LklAesKey = '340D2C2F15204082B14092DDE811AA22';
    private $_LklEncryptKeyPath = APP_PATH.'/mkapi/public/key/ct_rsa_private_key.pem';

    /**
    *拉卡拉支付交易
    */
    public function PaymentTrade(){
        $request = Request::instance();
        
        //收集表单信息
        $series = $request->post('series'); //用户唯一标识
        //$bankbranch_id = $request->post('bankbranch_id');//开户行id
        $channel_id = $request->post('channel_id');//渠道号
        $channel_code = $request->post('channel_code');//渠道码
        $pay_rate = $request->post('pay_rate');//费率
        $amount = $request->post('amount');//金额
        $randnum = $request->post('randnum');//随机数
        $callbackUrl = $request->post('callbackUrl');//回调地址
        $timestamp = $request->post('timestamp'); //报文时间戳
        $expriredtime = $request->post('expriredtime'); //报文截止时间戳

        // 获取用户信息
        $userInfo = Db::name('users')->field('real_name,card_no,bank_no,is_merchant,is_d0,bankbranch_id,phone,is_certificate')->where("series",$series)->find();
        if(!$userInfo){
            $errorId = uniqid("sqlErr");
            Log::init(['type'=>'file','path' => APP_PATH . 'mkapi/log/lakala/sql/openMerchant/']);
            Log::sql("【".$errorId."】用户信息获取失败");
            my_json_encode(10002,'用户信息获取失败:errorId = '.$errorId);
        }else if($userInfo['is_certificate'] == 0){
            //用户未进行实名认证
            my_json_encode(10003,'未进行实名认证');
        }else{
            
            //获取支行信息
            $bankbranchInfo = Db::name('lakala_bankbranch')->field('bankbranch_name,bankbranch_no')->where("id = {$userInfo['bankbranch_id']}")->find();
            if(!$bankbranchInfo){
                $errorId = uniqid("sqlErr");
                Log::init(['type'=>'file','path' => APP_PATH . 'mkapi/log/lakala/sql/openMerchant/']);
                Log::sql("【".$errorId."】用户信息获取失败");
                my_json_encode(10002,'用户信息获取失败:errorId = '.$errorId);
            }
        }

        //创建订单信息
        $data['order_no'] = getNo(1);
        $data['series'] = $series;
        $data['pay_type'] = 1;
        $data['channel_id'] = $channel_id;
        $data['order_money'] = round($amount, 2);
        $data['pay_rate'] = $pay_rate;
        $data['create_time'] = time();
        $data['trade_status'] = 0;
        // if($userInfo['is_d0'] == 1){
        //     $data['other_fee'] = 2;
        //     $data['arrive_money'] = round($amount * (1 - $pay_rate / 100)-2, 2);
        // }else{
        //     $data['other_fee'] = 0;
        //     $data['arrive_money'] = round($amount * (1 - $pay_rate / 100), 2);
        // }

        //储存订单信息
        
        //拉卡拉交易
       
        //lakala交易业务参数
        $encryptData['userId'] = $series;
        $encryptData['phoneNumber'] = $userInfo['phone'];
        $encryptData['timestamp'] = $timestamp;//报文的时间戳
        //$encryptData['callbackUrl'] = $param['callbackUrl'];
        $encryptData['orderId'] = $data['order_no'];
        $encryptData['productName'] = 'test';//订单名称
        $encryptData['productDesc'] = 'test';//订单描述
        $encryptData['remark'] = 'test';//备注
        $encryptData['amount'] = $data['order_money'];//交易金额
        $encryptData['expriredtime'] = $expriredtime;//失效时间
        $encryptData['randnum'] = $randnum;//随机数
        $encryptData['transCardNo'] = null;//交易卡号
        $encryptData['realName'] = $userInfo['real_name'];//真实姓名
        $encryptData['idCardNo'] = $userInfo['card_no'];//身份证号
        $encryptData['tranceCardType'] = 'C';
        $encryptData['callbackUrl'] = $callbackUrl;//回调地址

        //判断用户是否开通商户
        if($userInfo['is_merchant'] == 1){
            $encryptData['optCode'] = 'P00001';//业务代码

            write_to_log('【拉卡拉交易的订单参数：】'.json_encode($encryptData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');

            //获取商户号
            $merchantResult = Db::name('merchants')->field('merchant_no')->where('series',$series)->find(); 
            $data['merchant_no'] = $merchantResult['merchant_no'];
            $result = Db::name("lakala_order")->insert($data);
            if(!$result){
                $errorId = uniqid("sqlErr");
                Log::init(['type'=>'file','path' => APP_PATH . 'mkapi/log/lakala/sql/openMerchant/']);
                Log::sql("【".$errorId."】订单信息插入失败");
                my_json_encode(10004,'订单信息创建失败:errorId = '.$errorId);
            }else{
                 write_to_log('【插入的订单数据：】'.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');

            }
        }else{
            // 扩展参数，开通商户需要的参数
            $encryptData['optCode'] = 'B00002';
            $encryptData['legalPersonName'] = $userInfo['real_name'];//法人名称
            $encryptData['idCardId'] = $userInfo['card_no'];//法人身份证号
            $encryptData['accountName'] = $userInfo['real_name'];// 开户人名称
            $encryptData['accountNo'] = $userInfo['bank_no'];// 收款账户
            $encryptData['branchBankname'] = $bankbranchInfo['bankbranch_name'];// 开户支行
            $encryptData['branchBankno'] = $bankbranchInfo['bankbranch_no'];// 开户银行行号
            $encryptData['settleBankname'] = $bankbranchInfo['bankbranch_name'];
            $encryptData['settleBankno'] = $bankbranchInfo['bankbranch_name'];
            $encryptData['accountType'] = '0'; //账户类型 0代表对私

            write_to_log('【拉卡拉开通商户参数：】'.json_encode($encryptData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');
        }

        //加密参数
        $jsonData = json_encode($encryptData,JSON_UNESCAPED_UNICODE);
        $AES = new AesCbc($this->_LklAesKey);
        $map['channelCode'] = $channel_code;
        $map['ver'] = '1.4';
        $map['params'] = $AES->encryptString($jsonData);
        $map['sign'] = $AES->sign($jsonData, $this->_LklEncryptKeyPath);
        $param = base64_encode(json_encode($map, JSON_UNESCAPED_UNICODE));

        write_to_log('【请求拉卡拉params参数：】'.json_encode($jsonData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');
        write_to_log('【请求拉卡拉待加密参数：】'.json_encode($map,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');
        write_to_log('【请求拉卡拉param加密参数：】'.json_encode($param,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');


        //发送参数
        my_json_encode(10000,'success',array(
            'param'=>$param
            ));

        //发送参数
        // my_json_encode(0,'success',array(
        //     'param'=>$AES->decryptString($map['params'])
        //     ));

    }


    public function PaymentTrade1(){
        $request = Request::instance();
        
        //收集表单信息
        $series = $request->post('series'); //用户唯一标识
        //$bankbranch_id = $request->post('bankbranch_id');//开户行id
        $channel_id = $request->post('channel_id');//渠道号
        $channel_code = $request->post('channel_code');//渠道码
        $pay_rate = $request->post('pay_rate');//费率
        $amount = $request->post('amount');//金额
        $randnum = $request->post('randnum');//随机数
        $callbackUrl = $request->post('callbackUrl');//回调地址
        $timestamp = $request->post('timestamp'); //报文时间戳
        $expriredtime = $request->post('expriredtime'); //报文截止时间戳

        // 获取用户信息
        $userInfo = Db::name('users')->field('real_name,card_no,bank_no,is_merchant,is_d0,bankbranch_id,phone,is_certificate')->where("series",$series)->find();
        if(!$userInfo){
            $errorId = uniqid("sqlErr");
            Log::init(['type'=>'file','path' => APP_PATH . 'mkapi/log/lakala/sql/openMerchant/']);
            Log::sql("【".$errorId."】用户信息获取失败");
            my_json_encode(10002,'用户信息获取失败:errorId = '.$errorId);
        }else if($userInfo['is_certificate'] == 0){
            //用户未进行实名认证
            my_json_encode(10003,'未进行实名认证');
        }else{
            
            //获取支行信息
            $bankbranchInfo = Db::name('lakala_bankbranch')->field('bankbranch_name,bankbranch_no')->where("id = {$userInfo['bankbranch_id']}")->find();
            if(!$bankbranchInfo){
                $errorId = uniqid("sqlErr");
                Log::init(['type'=>'file','path' => APP_PATH . 'mkapi/log/lakala/sql/openMerchant/']);
                Log::sql("【".$errorId."】用户信息获取失败");
                my_json_encode(10002,'用户信息获取失败:errorId = '.$errorId);
            }
        }

        //创建订单信息
        $data['order_no'] = getNo(1);
        $data['series'] = $series;
        $data['pay_type'] = 1;
        $data['channel_id'] = $channel_id;
        $data['order_money'] = round($amount, 2);
        $data['pay_rate'] = $pay_rate;
        $data['create_time'] = time();
        $data['trade_status'] = 0;
        // if($userInfo['is_d0'] == 1){
        //     $data['other_fee'] = 2;
        //     $data['arrive_money'] = round($amount * (1 - $pay_rate / 100)-2, 2);
        // }else{
        //     $data['other_fee'] = 0;
        //     $data['arrive_money'] = round($amount * (1 - $pay_rate / 100), 2);
        // }

        //储存订单信息
        
        //拉卡拉交易
       
        //lakala交易业务参数
        $encryptData['userId'] = $series;
        $encryptData['phoneNumber'] = $userInfo['phone'];
        $encryptData['timestamp'] = $timestamp;//报文的时间戳
        //$encryptData['callbackUrl'] = $param['callbackUrl'];
        $encryptData['orderId'] = $data['order_no'];
        $encryptData['productName'] = 'test';//订单名称
        $encryptData['productDesc'] = 'test';//订单描述
        $encryptData['remark'] = 'test';//备注
        $encryptData['amount'] = $data['order_money'];//交易金额
        $encryptData['expriredtime'] = $expriredtime;//失效时间
        $encryptData['randnum'] = $randnum;//随机数
        $encryptData['transCardNo'] = null;//交易卡号
        $encryptData['realName'] = $userInfo['real_name'];//真实姓名
        $encryptData['idCardNo'] = $userInfo['card_no'];//身份证号
        $encryptData['tranceCardType'] = 'C';
        $encryptData['callbackUrl'] = $callbackUrl;//回调地址

        //判断用户是否开通商户
        if($userInfo['is_merchant'] == 1){
            $encryptData['optCode'] = 'P00001';//业务代码

            write_to_log('【拉卡拉交易的订单参数：】'.json_encode($encryptData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');

            //获取商户号
            $merchantResult = Db::name('merchants')->field('merchant_no')->where('series',$series)->find(); 
            $data['merchant_no'] = $merchantResult['merchant_no'];
            $result = Db::name("lakala_order")->insert($data);
            if(!$result){
                $errorId = uniqid("sqlErr");
                Log::init(['type'=>'file','path' => APP_PATH . 'mkapi/log/lakala/sql/openMerchant/']);
                Log::sql("【".$errorId."】订单信息插入失败");
                my_json_encode(10004,'订单信息创建失败:errorId = '.$errorId);
            }else{
                 write_to_log('【插入的订单数据：】'.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');

            }
        }else{
            // 扩展参数，开通商户需要的参数
            $encryptData['optCode'] = 'B00002';
            $encryptData['legalPersonName'] = $userInfo['real_name'];//法人名称
            $encryptData['idCardId'] = $userInfo['card_no'];//法人身份证号
            $encryptData['accountName'] = $userInfo['real_name'];// 开户人名称
            $encryptData['accountNo'] = $userInfo['bank_no'];// 收款账户
            $encryptData['branchBankname'] = $bankbranchInfo['bankbranch_name'];// 开户支行
            $encryptData['branchBankno'] = $bankbranchInfo['bankbranch_no'];// 开户银行行号
            $encryptData['settleBankname'] = $bankbranchInfo['bankbranch_name'];
            $encryptData['settleBankno'] = $bankbranchInfo['bankbranch_name'];
            $encryptData['accountType'] = '0'; //账户类型 0代表对私

            write_to_log('【拉卡拉开通商户参数：】'.json_encode($encryptData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');
        }

        //加密参数
        $jsonData = json_encode($encryptData,JSON_UNESCAPED_UNICODE);
        $AES = new AesCbc($this->_LklAesKey);
        $map['channelCode'] = $channel_code;
        $map['ver'] = '1.4';
        $map['params'] = $AES->encryptString($jsonData);
        $map['sign'] = $AES->sign($jsonData, $this->_LklEncryptKeyPath);
        $param = base64_encode(json_encode($map, JSON_UNESCAPED_UNICODE));

        write_to_log('【请求拉卡拉params参数：】'.json_encode($jsonData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');
        write_to_log('【请求拉卡拉待加密参数：】'.json_encode($map,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');
        write_to_log('【请求拉卡拉param加密参数：】'.json_encode($param,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/param/openMerchant/');


        //发送参数
        my_json_encode(10000,'success',array(
            'param'=>$param
            ));

        //发送参数
        // my_json_encode(0,'success',array(
        //     'param'=>$AES->decryptString($map['params'])
        //     ));

    }
}