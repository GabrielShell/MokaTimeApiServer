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
class Test extends Common{
	//拉卡拉交易D0提款参数
    // 机构号： 425510
    private $_LklCompOrgCode = 'QFTMPOS';
    private $_LklHashKey = 'mUb46HfgUDfygDq8KrbZTNRObQwhBeFv';
   	//private $_LklCompOrgCode = 'QFDT';
   	//private $_LklHashKey = 'wxd9c866ad31c3c6wxd9c866ad31c3c6';
    //拉卡拉服务器端参数
    private $_LklAesKey = '340D2C2F15204082B14092DDE811AA22';
    private $_LklEncryptKeyPath = APP_PATH.'/mkapi/public/key/ct_rsa_private_key.pem';
    private $_LklDecryptKeyPath =  APP_PATH.'/mkapi/public/key/lkl_public_key.pem';
//    private $_LklAesKey = '12345678901234561234567890123456';
//    private $_LklEncryptKeyPath = './WxApi/Public/key/lkl_private_key.pem';
//    private $_LklDecryptKeyPath = './WxApi/Public/key/test_lkl_public_key.pem';
	public function openMerchant(){

		//$url = "http://10.7.111.16:8080/icp/CTHDTPCD/6001.dor";
		//业务参数
		$data['userId'] = '222';//用户id
		$data['phoneNumber'] = '17359491816';//用户手机号
        $data['timestamp'] = date("yMdHms",time());//报文的时间戳
		$data['expriredtime'] = date("yMdHms",time()+24*3600*1000);//失效时间
		
		$data['productName'] = 'test';//订单名称
		$data['productDesc'] = 'test';//订单描述
		$data['remark'] = 'test';//备注
		$data['amount'] = 0.0;//金额
		$data['randnum'] = 'c46a2b';//随机数
		$data['optCode'] = 'B00002';//业务代码
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
		$data['legalPersonName'] = '林巧玲';//法人名称
		$data['idCardId'] = '350426199401025521';//法人身份证号
		$data['phone'] = '17359491816';//手机号
		$data['accountName'] = '林巧玲';// 开户人名称
		$data['accountNo'] = '6217001930006814207';// 收款账户
		// $data['branchBankname'] = '中国建设银行';// 开户支行
		// $data['branchBankno'] = '105100000017';// 开户银行行号

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

	//D0商户开通接口
	public function openD0(){
		$url = "https://api.lakala.com/thirdpartplatform/merchmanage/7011.dor";

        //请求参数
        $data['FunCod'] = '7011';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '11';
        $data['shopNo'] = '222';
        //$data['retUrl'] = 'http://lqlxiaoyouxi.cn/public/mkapi.php/mkapi/CallBack/openD0CallBack';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        // $this->xml = new XMLWriter();
        // $param = $this->toXml($data);
        // $param = xml($data);
        // echo "<pre>";
        // var_dump($param);
        $this->xml = new \XMLWriter('1.0','gb2312');
        $param = $this->toXml($data);
        $result = $this->curlRequest($url, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if ($result['responseCode'] == '000000'){
            $status = 1;
            $msg = $merId;
        }else{
            $status = 2;
            $msg = $result['message'];
        }

        return json(array('status'=>$status, 'msg'=>$msg));
	}

	/*
	*请求接口
	*
	*/
	public function curlRequest($curl, $https = true, $method = 'get', $data = null, $timeout = 30){
        $ch = curl_init();//初始化
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);        // 让cURL自己判断使用哪个版本
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);        // 在HTTP请求中包含一个"Index-Agent: "头的字符串。
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);                    // 在发起连接前等待的时间，如果设置为0，则无限等待
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);                            // 设置cURL允许执行的最长秒数
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);//只获取页面内容，但不输出
        curl_setopt($ch, CURLOPT_ENCODING, FALSE);                            // HTTP请求头中"Accept-Encoding: "的值。支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，请求头会发送所有支持的编码类型。
        curl_setopt($ch, CURLOPT_HEADER, false);//设置不需要头信息

        if ($https) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);//不做服务器认证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);//不做客户端认证
        }

        if ($method == 'post') {
            $httpHeaders = array(
                'Content-Type: text/xml; charset=gbk',
            );
            curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeaders);
            //dump($data);
            curl_setopt($ch, CURLOPT_POST, true);//设置请求是POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);//设置POST请求的数据
        }
        curl_setopt($ch, CURLOPT_URL, $curl);//设置访问的URL
        curl_setopt($ch, CURLINFO_HEADER_OUT, TRUE);

//     //dump($data);exit;
        $str = curl_exec($ch);//执行访问，返回结果
        curl_close($ch);//关闭curl，释放资源
        echo $str;
    }

    //生成XML文件
    private function toXml($data, $eIsArray = FALSE){
        if (!$eIsArray) {
            $this->xml->openMemory();
            $this->xml->startDocument("1.0", "GBK");
            $this->xml->startElement("xml");
        }
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->xml->startElement($key);
                $this->toXml($value, true);
                $this->xml->endElement();
                continue;
            }
            $this->xml->startElement($key);
            $this->xml->writeCData($value);
            $this->xml->endElement();
        }
        if (!$eIsArray) {
            $this->xml->endElement();
            return $this->xml->outputMemory(true);
        }
    }

    private function echo_json($status, $msg, $data=array()){
        header('Content-type: text/json;charset=utf-8');
        echo json_encode(array(
            'status' => $status,
            'msg' => $msg,
            'data' => $data
        ), JSON_UNESCAPED_UNICODE);exit;
    }


    //用户登录
    public function sendVerify(){
        // 收集表单信息
        $request = Request::instance();
        //用户电话号码
        $data['phone'] = '17359491816';
        if(!preg_match('/^1[34578]{1}\d{9}$/', $data['phone'])){
            my_json_encode(2, '手机号码错误');
        }else{
            //生成验证码
            $verify_code = getKey(4);
            @session('verify_code',$verify_code);
            //发送短信
            $sms = new Sms();
            $res = $sms->send( $data['phone'], '您的短信验证码是：'.$verify_code.'请尽快进行验证【厦门刷呗】');
            if( $res ){
                if( isset( $res['error'] ) &&  $res['error'] == 0 ){
                    my_json_encode(0, 'success');
                }else{
                    my_json_encode(2,'failed,code:'.$res['error'].',msg:'.$res['msg']);
                }
            }else{
                my_json_encode(3,$sms->last_error());
            }

        }
    }

    
   //用户实名认证
    public function certificate(){
        $request = Request::instance();
        $data['series'] = "mk17359491816701669";
        $data['card_no'] = "350426199401025521";
        $data['real_name'] = "林巧玲";
        $data['bank_no'] = "6217001930006814207";
        $result = $this->certificateRequest($data['bank_no'],$data['card_no'],$data['real_name']);
        $result = json_decode($result,true);
        $msg = '';
        switch($result['status']){
            case '01': $msg = '验证通过';break;
            case '02': $msg = '验证不通过';break;
            case '202': $msg = '无法验证';break;
            case '203': $msg = '异常情况';break;
            case '204': $msg = '姓名错误';break;
            case '205': $msg = '身份证号错误';break;
            case '206': $msg = '银行卡号错误';break;
        }

        if($result['status'] == '01'){
            $users = model('Users');
            //var_dump($users);
            if($re = $users->save($data,['series'=>$data['series']])){
                echo $re;
                exit();
                my_json_encode($result['status'],$msg);
            }else{
                my_json_encode(9,'erro:数据储存失败');
            }
        }
    }

    //实名认证请求
    public function certificateRequest($accountNo,$idCard,$name){
        $host = "https://tbank.market.alicloudapi.com";
        $path = "/bankCheck";
        $method = "GET";
        $appcode = "16ca317574514528952b05eaedc353f0";
        $headers = array();
        array_push($headers, "Authorization:APPCODE " . $appcode);
        $querys = "accountNo=$accountNo&idCard=$idCard&name=$name";
        $bodys = "";
        $url = $host . $path . "?" . $querys;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        if (1 == strpos("$".$host, "https://"))
        {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }
        $out_put = curl_exec($curl);
        return $out_put;
    }
  
  	public function testUpload(){
       	$request = Request::instance();
        $file = $request->file('file');
        if(empty($file)){
            echo my_json_encode(2,'没有文件上传');
        }else{
          	echo my_json_encode(0,'success');
        } 
    }

}