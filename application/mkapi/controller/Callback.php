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
    private $_LklCompOrgCode = 'QFTMPOS';
    private $_LklHashKey = 'mUb46HfgUDfygDq8KrbZTNRObQwhBeFv';
    //private $_LklHashKey = 'wxd9c866ad31c3c6wxd9c866ad31c3c6';
    //拉卡拉服务器端参数
    private $_LklAesKey = '340D2C2F15204082B14092DDE811AA22';
    private $_LklEncryptKeyPath = APP_PATH.'/mkapi/public/key/ct_rsa_private_key.pem';
    private $_LklDecryptKeyPath = APP_PATH.'/mkapi/public/key/lkl_public_key.pem';

    /**
    *拉卡拉开通商户回调
    */
    public function register(){
        //收集拉卡拉的请求数据
        $data = $_REQUEST;
        if(empty($data)){
            $data = file_get_contents("php://input");
        }
        if(!empty($data)){
            write_to_log('【开通商户回调信息：】 '.json_encode($data,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/callback/openMerchant/');
            // 将数据反编码
            $coreData = base64_decode($data);
            $coreData = json_decode($coreData,true);
            $AES = new AesCbc($this->_LklAesKey);
            // 解密
            $decrypted = $AES->decryptString($coreData['params']);
            //验签
            $checkSign = $AES->checkSign($decrypted, $coreData['sign'], $this->_LklDecryptKeyPath);
            //验证通过
            if($checkSign){
                write_to_log('【拉卡拉注册/回调信息解密：】'.json_encode($decrypted,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/callback/openMerchant/');
                $decrypted = json_decode($decrypted, true);
                if(!empty($coreData['ver'])){
                    //更新用户表信息
                    $userData['is_merchant'] = 1;
                    $userData['open_merchant_status'] = 1;
                    $userData['series'] = $decrypted['partnerUserId'];
                    //更新用户表
                    $userResult = Db::name('users')->where('series',$userData['series'])->update($userData);
                    //更新失败
                    if(!$userResult){
                        Log::init(['type'=>'file','path'=>APP_PATH.'mkapi/log/lakala/sql/openMerchant/']);
                        Log::sql('【拉卡拉注册/更新用户信息出错】'.$userData);

                        write_to_log('【拉卡拉注册/绑定通知-无此用户】'.json_encode($userData,JSON_UNESCAPED_UNICODE),'mkapi/log/lakala/sql/openMerchant/');
                        exit();
                    }  
                    //1、配置响应拉卡拉的参数
                    //2、判断商户是否开通
                    //3、更新商户信息表
                    //4、开通D0商户

                    //配置响应拉卡拉的参数
                    $param['isSuccess'] = 'Y';
                    $param['partnerTime'] = date("YmdHis");
                    $json = json_encode($param, JSON_UNESCAPED_UNICODE);
                    $map['ver'] = $coreData['ver'];
                    $map['reqId'] = $coreData['reqId'];
                    $map['params'] = $AES->encryptString($json);
                    $map['sign'] = $AES->sign($json, $this->_LklEncryptKeyPath);
                    $responseData = json_encode($map, JSON_UNESCAPED_UNICODE);

                    write_to_log('【拉卡拉注册/绑定通知输出给拉卡拉的内容】' . $responseData, '/mkapi/log/lakala/callback/openMerchant/');
                    //响应拉卡拉请求
                    echo $responseData;
                    sleep(5);

                    //配置商户表字段信息
                    $merchantData['merchant_no'] = $decrypted['merId'];//商户号
                    $merchantData['bind_time'] = time();//绑定时间
                    $merchantData['series'] = $userData['series'];//用户唯一标识
                    $merchantData['store_name'] = $decrypted['merName'];//店铺名称
                    $merchantData['merchant_name'] = $decrypted['realName'];//商户名称
                    $merchantData['area_name'] = $decrypted['areaName'];
                    $merchantData['address'] = $decrypted['address'];
                    //判断商户是否已经开通
                    $merchantResult = Db::name('merchants')->where('merchant_no',$merchantData['merchant_no'])->find();
                    // 如果已经开通商户终止程序运行
                    if($merchantResult){
                      write_to_log('【拉卡拉注册/此商户已经开通，不能重复开通】' . json_encode($merchantData), '/mkapi/log/lakala/callback/openMerchant/');
                        exit();
                    }
                    // 储存商户信息
                    $merchantResult = Db::name('merchants')->insert($merchantData);
                    if(!$merchantResult){
                        Log::init(['type'=>'file','path'=>APP_PATH.'mkapi/log/lakala/callback/openMerchant/']);
                        Log::sql('【拉卡拉注册/更新用户信息出错】'.$merchantData);
                    }else{
                        write_to_log('【拉卡拉注册/商户开通成功】' . json_encode($merchantData), '/mkapi/log/lakala/callback/openMerchant/');
                        //开通D0
                        $this->openD0($merchantData['merchant_no']);
                    }

                }

            }else{
                write_to_log('【拉卡拉注册/绑定通知-验签失败】' . json_encode($decrypted), '/mkapi/log/lakala/callback/openMerchant/');
            }
      
        }    
    }


    /**
    *拉卡拉D0商户开通接口
    * @param $merId string 商户号
    * @return array
    */
    public function openD0($merchant_no = null){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7011.dor';
       //$curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7011.dor';
        $data['shopNo'] = $merchant_no == null ? $_POST['merchant_no']:$merchant_no;
        if($data['shopNo'] == null){
            return my_json_encode(8,'参数不正确');
        }
        $data['FunCod'] = '7011';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '00';
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=resultOpenFromLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new \XMLWriter();
        $param = $this->toXml($data);
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        write_to_log('【拉卡拉D0开通/请求参数】' . json_encode($data, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openD0/');
        write_to_log('拉卡拉D0交易开通' . $param, '/mkapi/log/lakala/callback/openD0/');
        if ($result['responseCode'] == '000000'){
            write_to_log('【拉卡拉D0开通/请求通知】成功：' . json_encode($result, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openD0/');
            $status = 10000;
            $msg = '开通成功';
            $resData = $merchant_no;
        }else{
            write_to_log('【拉卡拉D0开通/请求通知】失败：' .json_encode($result, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openD0/');
            $status = 10002;
            $msg = '开通失败:'.$result['message'];
            $resData = $merchant_no;
        }
        echo my_json_encode($status,$msg,$resData);
    }

    /**
     * 拉卡拉D0开通通知地址
     */
    public function resultOpenD0(){
        $data = $_REQUEST;
        if (empty($data)) {
            $data = file_get_contents("php://input");
        }
        write_to_log('【拉卡拉D0开通/拉卡拉回调通知】' . $data, '/mkapi/log/lakala/callback/openD0/');
        $result = json_decode($data, true);
        $merchantId = $result['busData']['extInfo']['shopNo'];
        if ($result['busData']['status'] == 'SUCCESS'){
            $dataSave['is_d0'] = 1;
        }else{
            $dataSave['err_note'] = $result['busData']['extInfo']['retMsg'];
        }
        write_to_log('【拉卡拉D0开通成功】' . json_encode($data, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openD0/');
        write_to_log('【拉卡拉D0开通成功】' . $merchantId, '/mkapi/log/lakala/callback/openD0/');
        $result = Db::name("merchants")->where("merchant_no", $merchantId)->find();
        if (!empty($result)){
            
            $result =Db::name("users")->where('series',$result['series'])->update($dataSave);
        }

    }

    /**
     * 拉卡拉查询交易能否支付
     * @author xxw
     * @date 2017年6月15日10:41:29
     */
    public function confirmOrder(){
        $data = $_REQUEST;
        if (empty($data)) {
            $data = file_get_contents("php://input");
        }
        write_to_log('【拉卡拉查询交易能否支付/拉卡拉返回的数据】' . json_encode($data, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openMerchant/');
        //反编码
        $coreData = base64_decode($data);
        $coreData = json_decode($coreData, true);
        $AES = new AesCbc($this->_LklAesKey);
        $decrypted = $AES->decryptString($coreData['params']);
        
        write_to_log('【拉卡拉查询交易能否支付/反编码后的数据】' . json_encode($coreData, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openMerchant/');
        write_to_log('【拉卡拉查询交易能否支付/解密后的数据】' . $decrypted, '/mkapi/log/lakala/callback/openMerchant/');
        //验签
        $checkSign = $AES->checkSign($decrypted, $coreData['sign'],$this->_LklDecryptKeyPath);
        if ($checkSign){
            $decrypted = json_decode($decrypted, true);
            // 获取用户交易订单信息
            $orderInfo = Db::name("lakala_order")->where("order_no", $decrypted['orderId'])->find();
            if(!empty($orderInfo)){
                $param['canPay'] = 'y';
            }else{
                $param['canPay'] = 'n';
                write_to_log('【拉卡拉查询交易能否支付/不存在该订单】' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openMerchant/');
            }
        }else{
            $param['canPay'] = 'n';
            write_to_log('【拉卡拉查询交易能否支付/验签失败】' . $decrypted, '/mkapi/log/lakala/callback/openMerchant/');
        }

        $param['partnerQueryTime'] = date("YmdHis");
        $phone = substr($orderInfo['series'], 2,11);
        $param['partnerBillNo'] = $param['partnerQueryTime'] . $phone;
        //$param['amount'] = round($decrypted['amount'], 2);
        $param['partnerExtendinfo'] = base64_encode($orderInfo['series']);
        $json = json_encode($param, JSON_UNESCAPED_UNICODE);
        $map['ver'] = $coreData['ver'];
        $map['reqId'] = $coreData['reqId'];
        $map['params'] = $AES->encryptString($json);
        $map['sign'] = $AES->sign($json, $this->_LklEncryptKeyPath);
        $map2Json = json_encode($map, JSON_UNESCAPED_UNICODE);
        write_to_log('拉卡拉查询交易能否支付/响应拉卡拉未加密参数' . json_encode($param, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openMerchant/');
        write_to_log('【拉卡拉查询交易能否支/响应拉卡拉的加密参数】' . $map2Json, '/mkapi/log/lakala/callback/openMerchant/');
        echo $map2Json;
    }


    /**
     * 拉卡拉交易支付结果通知
     * @author xxw
     * @date 2017年6月20日09:12:47
     */
    public function orderResult(){
        $data = $_REQUEST;
        if (empty($data)){
            $data = file_get_contents("php://input");
        }
        write_to_log('【拉卡拉交易支付结果通知】' . json_encode($data, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openMerchant/');
        $coreData = base64_decode($data);
        $coreData = json_decode($coreData, true);
        $AES = new AesCbc($this->_LklAesKey);
        $decrypted = $AES->decryptString($coreData['params']);
        write_to_log('【拉卡拉交易支付结果通知/解密】' . $decrypted, '/mkapi/log/lakala/callback/openMerchant/');
        //验签
        $checkSign = $AES->checkSign($decrypted, $coreData['sign'],$this->_LklDecryptKeyPath);
        if ($checkSign){
            $decrypted = json_decode($decrypted, true);
            $orderInfo = Db::name("lakala_order")->where("order_no", $decrypted['partnerOrderId'])->find();
            if($orderInfo){
                $param['isSuccess'] = $decrypted['isSuccess'];
                $param['lakalaBillNo'] = $decrypted['lakalaBillNo'];
                $param['partnerBillNo'] = $decrypted['partnerBillNo'];
                $param['sid'] = $decrypted['sid'];
                $param['partnerPayTime'] = date("YmdHis");
                $json = json_encode($param, JSON_UNESCAPED_UNICODE);
                $map['ver'] = $coreData['ver'];
                $map['reqId'] = $coreData['reqId'];
                $map['params'] = $AES->encryptString($json);
                $map['sign'] = $AES->sign($json, $this->_LklEncryptKeyPath);
                $map2Json = json_encode($map, JSON_UNESCAPED_UNICODE);
                write_to_log('【拉卡拉交易支付结果通知/响应拉卡拉参数】' . $json, '/mkapi/log/lakala/callback/openMerchant/');
    
                write_to_log('【拉卡拉交易支付结果通知/响应拉卡拉参数/生成加密串】' . $map2Json, '/mkapi/log/lakala/callback/openMerchant/');
    
                echo $map2Json;
            }else{
                write_to_log('【拉卡拉交易支付结果通知/订单信息不存在】' . $map2Json, '/mkapi/log/lakala/callback/openMerchant/');
                Log::init(['type'=>'file','path'=>'/mkapi/log/lakala/sql/openMerchant/']);
                Log::sql("【订单信息不存在】");
            }

            //判断交易订单状态
            if ($orderInfo['trade_status'] == 0){
                if (($decrypted['retCode'] == '0000') && ($decrypted['isSuccess'] == 'Y')) {
                    $order['partnerBillNo'] = $decrypted['partnerBillNo'];
                    $order['trade_status'] = 2;
                    $order['pay_time'] = time();
                    $order['update_time'] = time();
                    $order['paycard_type'] = $decrypted['payCardType'];
                } else {
                    $order['trade_status'] = 1;
                    $order['err_note'] = $decrypted['retCode'] . $decrypted['retMsg'];
                }

                //更新订单数据
                $result = Db::name("lakala_order")->where("id", $orderInfo['id'])->update($order);
                if ($result !== false){
                    if ($order['tra_status'] == 2){
                        write_to_log('【拉卡拉交易支付结果通知-订单保存成功】' . json_encode($order,JSON_UNESCAPED_UNICODE) , '/mkapi/log/lakala/callback/openMerchant/');
                        // //只有在设备库中的设备交易能获得分润


                        // $inList = M("Poslist")->where("short='%s'", array($decrypted['terminalno']))->find();
                        // //$inList = M("Poslist")->where("sn='%s' OR short='%s'", array($decrypted['terminalno'], $decrypted['terminalno']))->find();
                        // if (!empty($inList)) {
                        //     //保存设备号
                        //     $management['sn'] = $inList['psam'];
                        //     M("Posmanagement")->where("shop_id=%d AND pos_id=1", array($orderInfo['shop_id']))->save($management);
                        //     switch ($orderInfo['system_id']){
                        //         case 28:
                        //             A("Liquidation")->index($orderInfo['order_no']);
                        //             break;
                        //         default:
                        //             break;
                        //     }
                        // }



                        // 储存商户终端号
                        $merchant['terminalno'] = $decrypted['terminalno'];
                        $merchantResult = Db::name('merchants')->where('series',$orderInfo['series'])->update($merchant);
                        if(!$merchantResult){
                            write_to_log('【拉卡拉交易支付结果通知-储存终端号失败】' . json_encode($merchantResult,JSON_UNESCAPED_UNICODE) , '/mkapi/log/lakala/callback/openMerchant/');
                            Log::init(['type'=>'file','path'=>'/mkapi/log/lakala/sql/openMerchant/']);
                            Log::sql("【储存终端号——失败】");
                        }else{
                             write_to_log('【拉卡拉交易支付结果通知-储存终端号成功】' . json_encode($merchantResult,JSON_UNESCAPED_UNICODE) , '/mkapi/log/lakala/callback/openMerchant/');
                        }
                        // set_time_limit(95);
                        // sleep(85);

                        //$this->withdrawByLkl($orderInfo);




                    }
                }else{
                    write_to_log('【拉卡拉交易支付结果通知/订单保存失败】' . json_encode($decrypted,JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/openMerchant/');
                    Log::init(['type'=>'file','path'=>'/mkapi/log/lakala/sql/openMerchant/']);
                    Log::sql("【订单保存失败——失败】");
                }
            }
        }else{
            write_to_log('【拉卡拉交易支付结果通知-验签失败】' . $decrypted, '/mkapi/log/lakala/callback/openMerchant/');
            exit();
        }
    }

    /**
     * 拉卡拉D0提现接口
     * @param $orderInfo array
     * @param $merId string
     * @return array
     * @author xxw
     * @date 2017年6月28日15:31:34
     */
    public function withdrawByLkl($orderInfo = null){
        //请求地址
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7005.dor';
        //$curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7005.dor';

        if($orderInfo == null){
            // 收集表单数据
            $request = Request::instance();
            $withdraw['withdraw_money'] = $request->post('withdraw_money');
            $withdraw['withdraw_no'] = getNumNo(16);
            $withdraw['series'] = $request->post('series');
            //获取商户号
            $merchant = Db::name("merchants")->field('merchant_no')->where("series",$withdraw['series'])->find();
            $withdraw['merchant_no'] = $merchant['merchant_no'];
            $withdraw['create_time'] = time();

        }else{
            //储存提款记录信息
            $withdraw['order_id'] = $orderInfo['id'];
            $withdraw['withdraw_no'] = getNumNo(16);
            $withdraw['series'] = $orderInfo['series'];
            $withdraw['merchant_no'] = $orderInfo['merchant_no'];
            $withdraw['create_time'] = time();
            $withdraw['withdraw_money'] = $orderInfo['order_money'];
        }

        //请求参数
        $data['FunCod'] = '7005';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = $withdraw['withdraw_no'];
        $data['shopNo'] = $withdraw['merchant_no'];
        $amount = number_format(($withdraw['withdraw_money'] * 0.994 -2), 3, ".", "");
        $data['amount'] = substr($amount, 0, -1);
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=getResultLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $data['amount'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new \XMLWriter();
        $param = $this->toXml($data);
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        write_to_log('【拉卡拉交易/提现请求参数】' . json_encode($data, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        write_to_log('【拉卡拉交易/提现返回数据】' . json_encode($result, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');

        //判断返回信息
        if($result['responseCode'] == '000000'){
            $status = 10000;
            $msg = "提现请求成功";
            $responseData['trannl'] = $result['tranJnl'];
            //储存提款信息
            $withdraw['tranjnl'] = $result['tranJnl'];

            write_to_log('【D0提款请求成功-】' . json_encode($result, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        }else{
            $status = 10002;
            $msg = "提现提现请求失败：". $result['message'];
            //储存失败信息
            $withdraw['err_note'] = $result['tranJnl'];

            $responseData['tranJnl'] = $result['tranJnl'];
          
            write_to_log('【D0提款请求失败-】' . json_encode($result, JSON_UNESCAPED_UNICODE) , '/mkapi/log/lakala/callback/withdraw/');
        }

        $resultWithdraw = Db::name("withdraw")->insert($withdraw);
        if(!$resultWithdraw){
            Log::init(['type'=>'file','path'=>APP_PATH.'/mkapi/log/lakala/sql/withdraw/']);
            Log::sql("拉卡拉提款记录新增失败");
            write_to_log('【拉卡拉提款信息储存失败】' . json_encode($withdraw, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        }else{
            write_to_log('【拉卡拉提款记录新增成功】' . json_encode($withdraw, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        }
        return my_json_encode($status, $msg,$responseData);
    }


    /**
     * 拉卡拉D0提现接口
     * @param $orderInfo array
     * @param $merId string
     * @return array
     * @author xxw
     * @date 2017年6月28日15:31:34
     */
    public function withdraw(){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7005.dor';
        //$curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7005.dor';
        // 收集表单数据
        $request = Request::instance();
        $withdraw['withdraw_money'] = $request->post('withdraw_money');
        $withdraw['withdraw_no'] = getNumNo(16);
        $withdraw['series'] = $request->post('series');
        //获取商户号
        $merchant = Db::name("merchants")->field('merchant_no')->where("series",$withdraw['series'])->find();
        $withdraw['merchant_no'] = $merchant['merchant_no'];

        //配置请求参数
        $data['FunCod'] = '7005';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = $withdraw['withdraw_no'];
        $data['shopNo'] = $withdraw['merchant_no'];
        $amount = number_format(($withdraw['withdraw_money'] * 0.994 -2), 3, ".", "");
        $data['amount'] = substr($amount, 0, -1);
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=getResultLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $data['amount'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new \XMLWriter();
        $param = $this->toXml($data);
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        write_to_log('【拉卡拉交易提现数组】' . json_encode($data, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        if($result['responseCode'] == '000000'){
            $status = 10000;
            $msg = "提现成功";
            $responseData['trannl'] = $result['tranJnl'];
            //储存提款信息

            $withdraw['tranjnl'] = $result['tranJnl'];
            write_to_log('【D0提款新增提款记录成功-】' . json_encode($withdraw, JSON_UNESCAPED_UNICODE) . $withdraw['withdraw_money'] . $merchant['merchant_no'], '/mkapi/log/lakala/callback/withdraw/');
            write_to_log('【D0提款请求成功-】' . json_encode($withdraw, JSON_UNESCAPED_UNICODE) . $withdraw['withdraw_money'] . $merchant['merchant_no'], '/mkapi/log/lakala/callback/withdraw/');
        }else{
            $status = 10002;
            $msg = "提现失败：". $result['message'];
            $responseData['tranJnl'] = $result['tranJnl'];

            //储存失败信息
            $withdraw['err_note'] = $result['tranJnl'];
            write_to_log('【D0提款新增提款记录成功-】' . json_encode($withdraw, JSON_UNESCAPED_UNICODE) . $withdraw['withdraw_money'] . $merchant['merchant_no'], '/mkapi/log/lakala/callback/withdraw/');
            write_to_log('【D0提款请求失败-】' . json_encode($withdraw, JSON_UNESCAPED_UNICODE) . $withdraw['withdraw_money'] . $merchant['merchant_no'], '/mkapi/log/lakala/callback/withdraw/');
        }

        $resultWithdraw = Db::name("withdraw")->insert($withdraw);
        if(!$resultWithdraw){
            Log::init(['type'=>'file','path'=>APP_PATH.'/mkapi/log/lakala/sql/withdraw/']);
            Log::sql("拉卡拉提现信息储存失败");
            write_to_log('【拉卡拉提现信息储存失败】' . json_encode($result, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        }else{
            write_to_log('【拉卡拉提现信息储存成功】' . json_encode($result, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/callback/withdraw/');
        }
        return my_json_encode($status, $msg,$responseData);
    }


     /**
     * 拉卡拉D0提款回调
     */
    public function getResultLkl(){
        $json = $_REQUEST;
        if (empty($data)){
            $json = file_get_contents("php://input");
        }
        write_to_log('【拉卡拉D0提款通知-】' . $json, '/mkapi/log/lakala/sql/withdraw/');
        $data = json_decode($json, true);
        $result = Db::name("withdraw")->where("tranjnl", $data['tranJnl'])->find();
        $orderInfo = Db::name("lakala_order")->where("id", $result['order_id'])->find();


        if ($orderInfo['tra_status'] == 0){
            write_to_log('拉卡拉D0提款结果通知-订单尚未支付-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/sql/withdraw/');exit();
        }

        if ($orderInfo['is_withdraw'] == 'y'){
            write_to_log('拉卡拉D0提款结果通知-订单已完成-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/sql/withdraw/');exit();
        }
        if ($data['responseCode'] == '000000'){
            $save['is_withdraw'] = 'y';
            $save['err_note'] = '';
        }else{
            $save['err_note'] = $data['message'];
        }

        $resultOfOrder = Db::name("lakala_order")->where("order_no", $orderInfo['order_no'])->update($save);
    
        if ($resultOfOrder !== false){
            write_to_log('拉卡拉D0提款结果通知-保存成功-' . json_encode($save, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/sql/withdraw/');
            write_to_log('拉卡拉D0提款结果通知-保存成功-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/sql/withdraw/');
            write_to_log('拉卡拉D0提款结果通知-保存成功-' . $json, '/mkapi/log/lakala/sql/withdraw/');
        }else{
            write_to_log('拉卡拉D0提款结果通知-保存失败-' . json_encode($save, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/sql/withdraw/');
            write_to_log('拉卡拉D0提款结果通知-保存失败-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/mkapi/log/lakala/sql/withdraw/');
        }
        dump($data);
        dump($result);
    }


    /**
     * 拉卡拉D0可提余额查询
     * @param $merId 商户号
     * @author xxw
     * @date 2017年6月28日15:32:56
     */
    public function getMoneyByLkl(){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7001.dor';
        //$curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7001.dor';
        $data['FunCod'] = '7001';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '11';
        //$data['accountNo'] = '62109840500016252';
        $data['shopNo'] = $_POST['merchant_no'];
        $queryString = $data['compOrgCode'] . $data['shopNo'] . $this->_LklHashKey;
        //dump($queryString);
        $data['MAC'] = sha1($queryString);
    
        $this->xml = new \XMLWriter();
        $param = $this->toXml($data);
        /*header('Content-type:text/xml;charset=utf-8');
        echo $param;*/
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        dump($result);
    }



    /**
    *发送请求
    * @param $curl string 请求地址
    * @param $curl bool 是否取消服务器认证
    * @param $method string 请求方法
    * @param $data string/array 请求数据
    * @param $timeout int 发起请求前等待的时间
    * @return array
    */
    function request($curl, $https = true, $method = 'get', $data = null, $timeout = 30){
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
        return $str;
    }

     /**
     * 数组转xml
     * @param $data
     * @param bool $eIsArray
     * @return string
     */
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

    public function test(){
        echo 'hello';
    }
}