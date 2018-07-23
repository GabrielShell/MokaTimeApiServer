<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use crypt\AesCbc;
/**
* 开通lakala商户
*/
class Openmerchant extends Common{
	
	private $_key = '340D2C2F15204082B14092DDE811AA22';
    private $_AESFile = './WxApi/Public/key/ct_rsa_private_key.pem';
	
	public function openMerchant($data=null){
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

    public function transfer($param,$data,$role){
        $str = substr($data[$role], 0,2);
        if($str = 'mk'){
            $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/register';
           $this->curlRequest($url,true,'post',$param);
        }
    }
}