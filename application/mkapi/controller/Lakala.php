<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use crypt\AesCbc;
/**
* 开通lakala商户
*/
class LakalaApi extends Common{
	
	private $_key = '340D2C2F15204082B14092DDE811AA22';
    private $_AESFile = './WxApi/Public/key/ct_rsa_private_key.pem';
	
	public function Payment(){
		$request = Request::instance();

		
		if($data == null){
			//业务参数
			$data['userId'] = $request->post('series');//用户id
			$data['randnum'] = $request->post('randnum');//随机数
	        $data['timestamp'] = date("yMdHms",time());//报文的时间戳
			$data['expriredtime'] = date("yMdHms",time()+24*3600*1000);//失效时间
			$data['productName'] = 'openMerchant';//订单名称
			$data['productDesc'] = 'openMerchant';//订单描述
			$data['remark'] = 'test';//备注
			$data['amount'] = 0.0;//金额

			$data['transCardNo'] = null;//交易卡号
	        $data['realName'] = '林巧玲';//真实姓名
	        $data['idCardNo'] = '350426199401025521';//身份证号
	        $data['tranceCardType'] = 'C';
	        //$data['callbackUrl'] ='http://lqlxiaoyouxi.cn/public/mkapi.php/mkapi/CallBack/openMerchantCallBack';
	        //业务扩展参数（新增商户）
			//$data['businessName'] = '';//营业执照号
			// $data['province'] = '福建省';//商户注册地址省代码
			// $data['city'] = '厦门市';//商户注册地址市代码
			// $data['district'] = '湖里区';//商户注册地址区代码
			// $data['address'] = '中江路879弄26号楼';//商户详细地址
			$data['optCode'] = 'B00002';//业务代码
			$data['phoneNumber'] = '17359491816';//用户手机号
			$data['legalPersonName'] = '林巧玲';//法人名称
			$data['idCardId'] = '350426199401025521';//法人身份证号
			$data['phone'] = '17359491816';//手机号
			$data['accountName'] = '林巧玲';// 开户人名称
			$data['accountNo'] = '6217001930006814207';// 收款账户
			// $data['branchBankname'] = '中国建设银行';// 开户支行
			// $data['branchBankno'] = '105100000017';// 开户银行行号

		}
		
		$jsonData = json_encode($data,JSON_UNESCAPED_UNICODE);
		$AES = new AesCbc($this->_LklAesKey);
		$map['channelCode'] = 'T4000017';
        $map['ver'] = '1.4';
        $map['params'] = $AES->encryptString($jsonData);
        $map['sign'] = $AES->sign($jsonData, APP_PATH.'/mkapi/public/key/ct_rsa_private_key.pem');
        $param = base64_encode(json_encode($map, JSON_UNESCAPED_UNICODE));

        $this->echo_json('1','success',$param);
	}



	public function index(){
        write_to_log('订单数据' .json_encode($_REQUEST, JSON_UNESCAPED_UNICODE), '/WxApi/Log/get/');
        $requestData = array();
        $requestData = $_REQUEST;
        foreach($requestData as $k => $v){
            if(empty($v)){
                $this->echo_json(10002, '参数不能为空');
            }
        }
        $result = $this->checkSign($requestData);
        if($result[0] != 1){
            $this->echo_json(10008, '验签失败');
        }

        $param['channelCode'] = $this->_post('channelCode');
        $param['amount'] = $this->_post('amount');
        $param['callbackUrl'] = $this->_post('callbackUrl');
        $param['randnum'] = $this->_post('randnum');
        $param['timestamp'] = $this->_post('timestamp');
        $param['expriredtime'] = $this->_post('expriredtime');
        $param['phone'] = $this->_post('phone');
        $param['ver'] = $this->_post('ver');
        if($param['amount'] < 20){
            $this->echo_json(9998, '金额不能低于20元！');
        }
        $shop_id = session('shop_id');
        $Model = M("Shop");
        $shopInfo = $Model->field(array(
            'shop.id' => 'id',
            'shop.username' => 'tel',
            'system.id' => 'system_id',
            'system.system_name' => 'system_name',
            'platform.id' => 'platform_id',
            'platform.platform_name' => 'platform_name',
            'grade.id' => 'grade_id',
            'grade.grade_name' => 'grade_name',
            'grade.mpos_rate' => 'mpos_rate',
            'shop.real_name' => 'real_name',
            'shop.card_no' => 'card_no',
            'shop.bank_no' => 'bank_no',
            'shop.bank_branch' => 'bank_branch',
            'shop.bankbranch_id' => 'bankbranch_id'
        ))
            ->table(array('eb_shop'=>'shop','eb_grade'=>'grade','eb_system'=>'system','eb_platform'=>'platform'))
            ->where("shop.id=%d AND grade.id=shop.grade_id AND system.id=shop.system_id AND platform.id=shop.platform_id", array($shop_id))
            ->find();
        $parderus = M('Shop')->where('id=' . $shopInfo['id'])->find();
        $pid232 = M('Shop')->field('real_name')->where('id=' . $parderus['pid'] )->find();
        $data = array();
        $data['no'] = getNumNo(20);
        $data['order_no'] = getNo(1);
        $data['pay_type'] = 10;
        $data['order_money'] = round($param['amount'], 2);
        $data['pay_rate'] = $shopInfo['mpos_rate'];
        $data['arrive_money'] = round($param['amount'] * (1 - $data['pay_rate'] / 100), 2);
        $data['cre_time'] = time();
        $data['channel_id'] = 28;
        $data['real_name'] = $shopInfo['real_name'];
        $data['tel'] = $shopInfo['tel'];
        $data['shop_id'] = $shopInfo['id'];
        $data['platform_id'] = $shopInfo['platform_id'];
        $data['platform_name'] = $shopInfo['platform_name'];
        $data['system_id'] = $shopInfo['system_id'];
        $data['system_name'] = $shopInfo['system_name'];
        $data['order_type'] = 1;
        $data['order_settype'] = 1;
        $data['proxy'] = $pid232['real_name'];
        write_to_log('订单数据' .json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/get/');
        $order = M("Order");
        $orderAddResult = $order->add($data);
        if ($orderAddResult){
            $encryptData['userId'] = $shopInfo['tel'];
            $encryptData['phoneNumber'] = $shopInfo['tel'];
            $encryptData['timestamp'] = $param['timestamp'];
            $encryptData['callbackUrl'] = $param['callbackUrl'];
            $encryptData['orderId'] = $data['order_no'];
            $encryptData['productName'] = 'test';
            $encryptData['productDesc'] = 'test';
            $encryptData['remark'] = 'test';
            $encryptData['amount'] = round($param['amount'], 2);
            $encryptData['expriredtime'] = $param['expriredtime'];
            $encryptData['randnum'] = $param['randnum'];
            $encryptData['transCardNo'] = null;
            $encryptData['realName'] = $shopInfo['real_name'];
            $encryptData['idCardNo'] = $shopInfo['card_no'];;
            $encryptData['tranceCardType'] = 'C';
            $posManagement = M("Posmanagement")->where("shop_id=%d AND status=1", array($shop_id))->find();
            write_to_log('params参数' . json_encode($encryptData), '/WxApi/Log/test/');
            write_to_log('params参数' . M("Posmanagement")->getLastSql(), '/WxApi/Log/test/');
            if ($posManagement){
                //POS机已绑定
                $encryptData['optCode'] = 'P00001';

            }else{
                //POS机未绑定
                $bankInfo = M('Bankbranch')->where("id=%d", array($shopInfo['bankbranch_id']))->find();
                write_to_log('params参数' . $bankInfo, '/WxApi/Log/test/');
                $encryptData['optCode'] = 'B00002';
                $encryptData['legalPersonName'] = $shopInfo['real_name'];
                $encryptData['idCardId'] = $shopInfo['card_no'];
                $encryptData['accountName'] = $shopInfo['real_name'];
                $encryptData['accountNo'] = $shopInfo['bank_no'];
                $encryptData['branchBankname'] = $shopInfo['bank_branch'];
                $encryptData['branchBankno'] = $bankInfo['bankBranch_no'];
                $encryptData['settleBankname'] = $shopInfo['bank_branch'];
                $encryptData['settleBankno'] = $bankInfo['bankBranch_no'];
                $encryptData['accountType'] = '0';
            }
            $jsonData = json_encode($encryptData, JSON_UNESCAPED_UNICODE);
            import('ORG.Crypt.AesCbc');
            $AES = new AesCbc('340D2C2F15204082B14092DDE811AA22');
            $map['channelCode'] = $param['channelCode'];
            $map['ver'] = $param['ver'];
            $map['params'] = $AES->encryptString($jsonData);
            $map['sign'] = $AES->sign($jsonData, './WxApi/Public/key/ct_rsa_private_key.pem');
            $param = base64_encode(json_encode($map, JSON_UNESCAPED_UNICODE));
            write_to_log('params参数' . $jsonData, '/WxApi/Log/Lakala/');
            write_to_log('待加密参数' . json_encode($map, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
            write_to_log('加密后参数' . $param, '/WxApi/Log/Lakala/');
            $this->echo_json('10000', 'success',
                array(
                    'param' => $param,
                )
            );
        }
        $this->echo_json('0001', '订单失败', '');
    }

}