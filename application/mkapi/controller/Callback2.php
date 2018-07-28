<?php
/**
 * 用于：回调
 * author： Xiaoxiaowei
 * Date：  2017-06-15 10:39
 */
class CallbackAction extends Action
{
    //拉卡拉交易D0提款参数
    // 机构号： 425510
    private $_LklCompOrgCode = 'QFTMPOS';
    private $_LklHashKey = 'mUb46HfgUDfygDq8KrbZTNRObQwhBeFv';
//    private $_LklCompOrgCode = 'QFDT';
//    private $_LklHashKey = 'wxd9c866ad31c3c6wxd9c866ad31c3c6';
    //拉卡拉服务器端参数
    private $_LklAesKey = '340D2C2F15204082B14092DDE811AA22';
    private $_LklEncryptKeyPath = './WxApi/Public/key/ct_rsa_private_key.pem';
    private $_LklDecryptKeyPath = './WxApi/Public/key/lkl_public_key.pem';
//    private $_LklAesKey = '12345678901234561234567890123456';
//    private $_LklEncryptKeyPath = './WxApi/Public/key/lkl_private_key.pem';
//    private $_LklDecryptKeyPath = './WxApi/Public/key/test_lkl_public_key.pem';
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
        write_to_log('拉卡拉查询交易能否支付' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        $coreData = base64_decode($data['param']);
        $coreData = json_decode($coreData, true);
        import('ORG.Crypt.AesCbc');
        $AES = new AesCbc($this->_LklAesKey);
        $decrypted = $AES->decryptString($coreData['params']);
        write_to_log('拉卡拉查询交易能否支付' . json_encode($decrypted, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉查询交易能否支付' . json_encode($coreData, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        //验签
        $checkSign = $AES->checkSign($decrypted, $coreData['sign'],$this->_LklDecryptKeyPath);
        $decrypted = json_decode($decrypted, true);

        $orderInfo = M("Order")->where("order_no='%s'", array($decrypted['orderId']))->find();
        if ($checkSign) {
            if (!empty($orderInfo)) {
                $param['canPay'] = 'y';
            }else{
                $param['canPay'] = 'n';
                write_to_log('拉卡拉查询交易能否支付生成加密串-不存在该订单' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
                //巧玲修改
                $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/confirmOrder';
                $responseData = $this->transfer($data,$decrypted,'orderId',$url);
                write_to_log('拉卡拉查询交易能否支付生成加密串' . $responseData, '/WxApi/Log/Lakala/');
                echo $responseData;
                exit();
                //巧玲修改
            }
        }else {
            $param['canPay'] = 'n';
            write_to_log('拉卡拉查询交易能否支付生成加密串-验签失败' . $decrypted, '/WxApi/Log/Lakala/');
        }
        $param['partnerQueryTime'] = date("YmdHis");
        $param['partnerBillNo'] = $param['partnerQueryTime'] . $orderInfo['tel'];
        //$param['amount'] = round($decrypted['amount'], 2);
        $param['partnerExtendinfo'] = base64_encode($orderInfo['tel']);
        $json = json_encode($param, JSON_UNESCAPED_UNICODE);
        $map['ver'] = $coreData['ver'];
        $map['reqId'] = $coreData['reqId'];
        $map['params'] = $AES->encryptString($json);
        $map['sign'] = $AES->sign($json, $this->_LklEncryptKeyPath);
        $map2Json = json_encode($map, JSON_UNESCAPED_SLASHES);
        write_to_log('拉卡拉查询交易能否支付生成加密串' . $map2Json, '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉查询交易能否支付生成加密串' . json_encode($param, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        echo $map2Json;
    }

    /**
     * 拉卡拉交易支付结果通知
     * @author xxw
     * @date 2017年6月20日09:12:47
     */
    public function orderResult(){
        $data = $_REQUEST;
        if (empty($data)) {
            $data = file_get_contents("php://input");
        }
        write_to_log('拉卡拉交易支付结果通知' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        $coreData = base64_decode($data['param']);
        $coreData = json_decode($coreData, true);
        import('ORG.Crypt.AesCbc');
        $AES = new AesCbc($this->_LklAesKey);
        $decrypted = $AES->decryptString($coreData['params']);
        //验签
        $checkSign = $AES->checkSign($decrypted, $coreData['sign'],$this->_LklDecryptKeyPath);
        $decrypted = json_decode($decrypted, true);

        $orderInfo = M("Order")->where("order_no='%s'", array($decrypted['partnerOrderId']))->find();
    if(!$orderInfo){
            //巧玲修改
            $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/orderResult';
            $responseData = $this->transfer($data,$decrypted,'partnerOrderId',$url);
            echo $responseData;
            //巧玲修改
        }
        if ($checkSign) {
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
            $map2Json = json_encode($map, JSON_UNESCAPED_SLASHES);
            echo $map2Json;
            if ($orderInfo['tra_status'] == 2) {
                if (($decrypted['retCode'] == '0000') && ($decrypted['isSuccess'] == 'Y')) {
                    $order['no'] = $decrypted['partnerBillNo'];
                    $order['tra_status'] = 1;
                    $order['pay_time'] = time();
                } else {
                    $order['err_note'] = $decrypted['retCode'] . $decrypted['retMsg'];
                }
                $result = M("Order")->where("id=%d", array($orderInfo['id']))->save($order);
                if ($result !== false){
                    if ($order['tra_status'] == 1){
                        write_to_log('拉卡拉交易支付结果通知-提现结果' . $result , '/WxApi/Log/Lakala/');
                        //只有在设备库中的设备交易能获得分润
                        $inList = M("Poslist")->where("short='%s'", array($decrypted['terminalno']))->find();
                        //$inList = M("Poslist")->where("sn='%s' OR short='%s'", array($decrypted['terminalno'], $decrypted['terminalno']))->find();
                        if (!empty($inList)) {
                            //保存设备号
                            $management['sn'] = $inList['psam'];
                            M("Posmanagement")->where("shop_id=%d AND pos_id=1", array($orderInfo['shop_id']))->save($management);
                            switch ($orderInfo['system_id']){
                                case 28:
                                    A("Liquidation")->index($orderInfo['order_no']);
                                    break;
                                default:
                                    break;
                            }

                        }
                        $merchant = M("Posmanagement")->field('merchantId')->where("shop_id=%d", array($orderInfo['shop_id']))->find();//调用提现接口
                        write_to_log('8-提现结果' . $orderInfo['shop_id'] , '/WxApi/Log/Lakala/');
                        write_to_log('9-提现结果' . $merchant , '/WxApi/Log/Lakala/');
                        $shopLkl['order_id'] = $orderInfo['id'];
                        $shopLkl['withdraw_no'] = $orderInfo['no'];
                        $shopLkl['shop_id'] = $orderInfo['shop_id'];
                        $resultOfSave = M("Shop_lkl")->add($shopLkl);
                        if ($resultOfSave) {
                            write_to_log('拉卡拉交易支付结果通知-新增成功shop_lkl-' . json_encode($shopLkl, JSON_UNESCAPED_UNICODE) . $orderInfo['order_money'] . $merchant['merchantId'], '/WxApi/Log/Lakala/');
                        }else{
                            write_to_log('拉卡拉交易支付结果通知-新增失败shop_lkl-' . json_encode($shopLkl, JSON_UNESCAPED_UNICODE) . $orderInfo['order_money'] . $merchant['merchantId'], '/WxApi/Log/Lakala/');
                        }
                        set_time_limit(120);
                        sleep(100);
                        $resultOfWithdraw = $this->withdrawByLkl($orderInfo,$merchant['merchantId']);
                        if ($resultOfWithdraw['status'] == 1){
                            $orderWithdraw['tranJnl'] = $resultOfWithdraw['msg'];
                        }else{
                            $orderWithdraw['err_note'] = $resultOfWithdraw['msg'];
                        }
                        $resultOfSave = M("Shop_lkl")->where("order_id=%d", array($orderInfo['id']))->save($orderWithdraw);
                        if ($resultOfSave) {
                            write_to_log('拉卡拉交易支付结果通知-新增成功shop_lkl-' . json_encode($resultOfWithdraw, JSON_UNESCAPED_UNICODE) . $orderInfo['order_money'] . $merchant['merchantId'], '/WxApi/Log/Lakala/');
                        }else{
                            write_to_log('拉卡拉交易支付结果通知-新增失败shop_lkl-' . json_encode($resultOfWithdraw, JSON_UNESCAPED_UNICODE) . $orderInfo['order_money'] . $merchant['merchantId'], '/WxApi/Log/Lakala/');
                        }
                    }
                }else{
                    write_to_log('拉卡拉交易支付结果通知-订单保存失败' . $decrypted, '/WxApi/Log/Lakala/');
                }
            }
        } else {
            write_to_log('拉卡拉交易支付结果通知-验签失败' . $decrypted, '/WxApi/Log/Lakala/');
            exit();
        }
    }

//    public function decrypt(){
//
//        $data = '{"param":"eyJjaGFubmVsQ29kZSI6IlQ0MDAwMDE3IiwidmVyIjoiMS40IiwicGFyYW1zIjoiOHBcLzFGMFZcL0J5VFZTa2ZZODR6UHFCbCt5Qzh5VUkrR09LMzRkbitRVzQ3MWpWZDRUUzY0UEgxdEdwV1wva29ldzVKSUdLc0lVa045UG5HMmt2c2lSNVR4c0xGZ2VLMm8wVm0ySTc5anlmVlgrR3hQa2lTcEVUamFEQitWNERcL2N2ZTdobTZ5Z21DdU9hS1hxc1dLdnBSZ3NTT1R5MUZsaTRUNlpyR0RGRUsybGdOV3VUQ3VxUFZLamFJSEEyQVIwU0l4bHRhQmI2SFVQWlJ0MjlYZzdEdU5HK3RIbTRMTXd4T1c1MFJcLzhJTzEwNm9GR01TcUd5OXE5U3RpYTNYVUV2VEFaVTlXZDlBOEg2dmRBdGVEdHNmN1lETFlvSHRLQWVBVGxkQ1N4c3BQYThobDBJMEMrcXRFemxFVStNYk1JanpmbklFTXhrYlZsUzZtbHVGS0tEYzhWMEc0VkpQVmhDTEdqN2hlQ1FZMTEwUXc2Mytua0Y1emdkbGVtWG5ObjA3U1VCSERkVUhtUXV4MnAraG9wXC9WUXR5K2RsRDJUb3lVQkxQU2NCcENrZUhPQmtucTZ2NjM1M2xBMUVNXC9UZXJaRDVySUU3R2RsbnR5anZnVzFJRjYxU3lcL1czWXNnSE1LcFZ0eXNsYktwNE9jQmdBUDFvVU8xM0hzN2x4Wm16TCtOUVlmM1VyRThcL0pJRVwvcEp4N1dDdlVrVkphcnRlSUJvazNLNnpMQTR3bz0iLCJzaWduIjoiRTZxZFh1akJjSmxvbElrSGk3QnlcL2JcL3d1ZFY1YTc4dUI0cDRJMk4zYlI5eWFISEtudUlicjhKSjU5V1lRejFTN3F3azY4MWpcL0xLR3FOQjNJK0gxZnJreFdPb1wvS1VJWjZ1M1hMSnFSS1wvQWFqVWVoc2dhdlVxY282WFQ2ZitnYWZtcCtBa3BlaHhuK2FqQTZZTjlIdllnWjNxb21JY3R3SlFrMGhCcWdMXC9BPSJ9"}';
//        $data = json_decode($data, true);
//        $coreData = base64_decode($data['param']);
//        $coreData = json_decode($coreData, true);
//        dump($coreData);
//        import('ORG.Crypt.AesCbc');
//        $AES = new AesCbc($this->_LklAesKey);
//        $decrypted = $AES->decryptString($coreData['params']);
//
//
//        //验签
//        $checkSign = $AES->checkSign($decrypted, $coreData['sign'],$this->_LklDecryptKeyPath);
//        dump($checkSign);
//        $decrypted = json_decode($decrypted, true);
//        dump(json_encode($decrypted, JSON_UNESCAPED_UNICODE));
////        $inList = M("Poslist")->where("short='%s'", array($decrypted['terminalno']))->find();
////        $orderInfo = M("Order")->where("order_no='%s'", array($decrypted['partnerOrderId']))->find();
////        if (!empty($inList)) {
////            $management['sn'] = $inList['psam'];
////            dump($inList);
////            dump($management);
////            M("Posmanagement")->where("shop_id=%d AND pos_id=1", array($orderInfo['shop_id']))->save($management);
////        }
//
//        dump($decrypted);
//        dump($checkSign);
//    }

    /**
     * 拉卡拉注册/绑定通知
     * @author xxw
     * @date 2017年6月20日09:13:55
     */
    public function register(){
        $data = $_REQUEST;
        if (empty($data)) {
            $data = file_get_contents("php://input");
        }
        write_to_log('拉卡拉注册/绑定通知' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉注册/绑定通知' . $data, '/WxApi/Log/test/');
        $coreData = base64_decode($data['param']);
        $coreData = json_decode($coreData, true);
        import('ORG.Crypt.AesCbc');
        $AES = new AesCbc($this->_LklAesKey);
        $decrypted = $AES->decryptString($coreData['params']);
        //验签
        $checkSign = $AES->checkSign($decrypted, $coreData['sign'], $this->_LklDecryptKeyPath);
        if ($checkSign) {
            $decrypted = json_decode($decrypted, true);
      

            if (!empty($coreData['ver'])) {
                //写入
                $posManage['status'] = 1;
                $posManage['bind_time'] = time();
                $posManage['merchantId'] = $decrypted['merId'];
                $result = M("Posmanagement")->where("tel='%s' AND pos_id=1", array($decrypted['partnerUserId']))->save($posManage);

                if (!$result) {
                    write_to_log('拉卡拉注册/绑定通知-无此用户' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
                    //巧玲修改
                    $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/register';
                    $responseData = $this->transfer($data['param'],$decrypted,'partnerUserId',$url);
                    echo $responseData;
                    write_to_log('拉卡拉注册/绑定通知输出给拉卡拉的内容' . $responseData, '/WxApi/Log/Lakala/');
                    //巧玲修改
                    exit();
                }
                //只有注册/绑定时才需要返回以下数据给拉卡拉
                $param['isSuccess'] = 'Y';
                $param['partnerTime'] = date("YmdHis");
                $json = json_encode($param, JSON_UNESCAPED_UNICODE);
                $map['ver'] = $coreData['ver'];
                $map['reqId'] = $coreData['reqId'];
                $map['params'] = $AES->encryptString($json);
                $map['sign'] = $AES->sign($json, $this->_LklEncryptKeyPath);
                $map2Json = json_encode($map, JSON_UNESCAPED_SLASHES);
                write_to_log('拉卡拉注册/绑定通知输出给拉卡拉的内容' . $map2Json, '/WxApi/Log/Lakala/');
                write_to_log('拉卡拉注册/绑定通知输出给拉卡拉的内容' . json_encode($decrypted, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
                echo $map2Json;
                sleep(5);
                //开通D0
                $result = $this->openD0($decrypted['merId']);
                if ($result['status'] == 1){
                    $manage['openStatus'] = 1;
                    M("Posmanagement")->where("tel='%s' AND pos_id=1", array($decrypted['partnerUserId']))->save($manage);
                    write_to_log('拉卡拉-开通D0成功' . $result['msg'], '/WxApi/Log/Lakala/');
                }else{
                    write_to_log('拉卡拉-开通D0失败' . $result['msg'], '/WxApi/Log/Lakala/');
                }
            }
        }
        write_to_log('拉卡拉注册/绑定通知-验签失败' . json_encode($decrypted), '/WxApi/Log/Lakala/');
    }
    /**
     * 拉卡拉D0提现接口
     * @param $orderInfo array
     * @param $merId string
     * @return array
     * @author xxw
     * @date 2017年6月28日15:31:34
     */
    public function withdrawByLkl($orderInfo, $merId){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7005.dor';
//        $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7005.dor';
        $data['FunCod'] = '7005';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = $orderInfo['no'];
        $data['shopNo'] = $merId;
        $amount = number_format(($orderInfo['order_money'] * 0.994 -2), 3, ".", "");
        $data['amount'] = substr($amount, 0, -1);
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=getResultLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $data['amount'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        write_to_log('拉卡拉交易提现数组' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        if ($result['responseCode'] == '000000'){
            $status = 1;
            $msg = $result['tranJnl'];
            write_to_log('拉卡拉提现成功' . json_encode($result, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        }else{
            $status = 2;
            $msg = $result['tranJnl'] . $result['message'];
            write_to_log('拉卡拉提现失败' . json_encode($result, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        }
        return array('status'=>$status, 'msg'=>$msg);
    }

    /**
     * 拉卡拉D0提现接口
     * @param $arriveMoney string
     * @param $merId string
     * @return array
     * @author xxw
     * @date 2017年6月28日15:31:34
     */
    public function withdrawByLkl1(){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7005.dor';
//        $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7005.dor';
        $data['FunCod'] = '7005';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . rand(0000,9999);
        $data['shopNo'] = '822295454118907';
        $amount = 2421.37;
        $data['amount'] = substr($amount, 0, -1);
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=getResultLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $data['amount'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        dump($param);
        $result = $this->request($curlUrl, true, 'post', $param);
        dump($result);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
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
//            $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7001.dor';
        $data['FunCod'] = '7001';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '11';
        //$data['accountNo'] = '62109840500016252';

        $data['shopNo'] = '822295454118028';
        $queryString = $data['compOrgCode'] . $data['shopNo'] . $this->_LklHashKey;
        //dump($queryString);
        $data['MAC'] = sha1($queryString);
        dump($data);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        /*header('Content-type:text/xml;charset=utf-8');
        echo $param;*/
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        dump($result);
    }

    /**
     * 提款手续费查询接口
     * @author xxw
     * @date 2017年6月28日19:34:09
     */
    public function getFeeByLkl(){
        $money = $this->_get("money");
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7002.dor';
//        $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7002.dor';
        $merchantInfo = M("Posmanagement")->field("id,merchantId")->where("shop_id=%d", array(25422))->find();
        $data['FunCod'] = '7002';
        $data['compOrgCode'] = 'WLTK';
        $data['reqLogNo'] = date("YmdHis") . '11';
        $data['shopNo'] = '822295454118328';
        $data['amount'] = round($money, 2);
        $data['accountNo'] = '6226021521232123';
        $queryString = $data['compOrgCode'] . $data['shopNo'] . $data['amount'] . $data['accountNo'] .'wxd9c866ad31c3c6wxd9c866ad31c3c6';
        $data['MAC'] = sha1($queryString);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        /*header('Content-type:text/xml;charset=utf-8');
        echo $param;*/
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        dump($result);
    }

    /**
     * 拉卡拉D0商户开通接口
     * @param $merId string
     * @return array
     * @author xxw
     * @date 2017年6月28日19:46:42
     */
    public function openD0($merId){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7011.dor';
//        $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7011.dor';
        $data['FunCod'] = '7011';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '11';
        $data['shopNo'] = $merId;
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=resultOpenFromLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        $result = $this->request($curlUrl, true, 'post', $param);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        write_to_log('拉卡拉D0交易开通' . json_encode($result, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉D0交易开通' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉D0交易开通' . $param, '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉D0交易开通' . $result, '/WxApi/Log/Lakala/');
        if ($result['responseCode'] == '000000'){
            $status = 1;
            $msg = $merId;
        }else{
            $status = 2;
            $msg = $result['message'] . $merId;
        }
        return array('status'=>$status, 'msg'=>$msg);
    }

    /**
     * 拉卡拉D0商户开通接口
     * @param $merId string
     * @return array
     * @author xxw
     * @date 2017年6月28日19:46:42
     */
    public function openD01(){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7011.dor';
//        $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7011.dor';
        $data['FunCod'] = '7011';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '11';
        $data['shopNo'] = '822295454118334';
        $data['retUrl'] = 'http://wk.xmjishiduo.com/wxApi.php?m=Callback&a=resultOpenFromLkl';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['shopNo'] . $this->_LklHashKey;
        $data['MAC'] = sha1($queryString);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        $result = $this->request($curlUrl, true, 'post', $param);
        dump($result);
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        write_to_log('拉卡拉D0交易开通' . json_encode($result, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉D0交易开通' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        dump($data);
        dump($result);
    }

    public function getMerchant(){
        $phone = $this->_get("phone");
        $pos = M("Posmanagement")->where("tel='%s'", array($phone))->find();
        if (empty($pos)){
            echo '不存在该商户!';exit();
        }
        if (empty($pos['merchantId'])){
            echo '该商户暂未进件拉卡拉审核!';exit();
        }
        echo $pos['shop_name'] . ": " . $pos['tel'] .": " .$pos['merchantId'];
    }

    /**
     * 拉卡拉D0开通通知接口
     * @author xxw
     * @date 2017年6月28日19:47:06
     */
    public function resultOpenFromLkl(){
        $data = $_REQUEST;
        if (empty($data)) {
            $data = file_get_contents("php://input");
        }
        write_to_log('拉卡拉D0开通通知-' . $data, '/WxApi/Log/Lakala/');
        $result = json_decode($data, true);
        $merchantId = $result['busData']['extInfo']['shopNo'];
        if ($result['busData']['status'] == 'SUCCESS'){
            $dataSave['openStatus'] = 2;
        }else{
            $dataSave['openStatus'] = 1;
            $dataSave['err_note'] = $result['busData']['extInfo']['retMsg'];
        }
        write_to_log('拉卡拉D0开通成功' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉D0开通成功' . $merchantId, '/WxApi/Log/Lakala/');
        $result = M("Posmanagement")->where("merchantId='%s'", array($merchantId))->find();
        if (!empty($result)){
            M("Posmanagement")->where("id=%d", array($result['id']))->save($dataSave);
        }
    //巧玲修改
        else{
            $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/resultOpenD0';
            $this->request($url,true,'post',$data);
        }
        //巧玲修改

        dump($data);
        dump($result);
    }

    public function getResultOfWithdraw(){
        $curlUrl = 'https://api.lakala.com/thirdpartplatform/merchmanage/7004.dor';
//        $curlUrl = 'https://124.74.143.162:15023/thirdpartplatform/merchmanage/7004.dor';
        $data['FunCod'] = '7004';
        $data['compOrgCode'] = $this->_LklCompOrgCode;
        $data['reqLogNo'] = date("YmdHis") . '11';
        $data['tranJnl'] = '217122003674413';
        $data['shopNo'] = '822295454118028';
        $queryString = $data['compOrgCode'] . $data['reqLogNo'] . $data['tranJnl'] .  $data['shopNo'] . $this->_LklHashKey;
        //dump($queryString);
        $data['MAC'] = sha1($queryString);
        dump($data);
        $this->xml = new XMLWriter();
        $param = $this->toXml($data);
        /*header('Content-type:text/xml;charset=utf-8');
        echo $param;exit();*/
        $result = $this->request($curlUrl, true, 'post', $param);
        write_to_log('拉卡拉D0交易查询' . $result, '/WxApi/Log/Lakala/');
        /*$p = xml_parser_create();
        xml_parse_into_struct($p, $result, $vals, $index);
        dump($vals);
        dump($index);*/
        /*dump(simplexml_load_string($result, 'SimpleXMLElement'));*/
        $result = json_decode(json_encode(simplexml_load_string($result, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $signString = $result['responseCode'] . $result['reqLogNo'] . $result['shopNo'] .
            $result['accountId'] . $result['tranAmt'] . $result['feeAmt'] . $result['accAmt'] . $this->_LklHashKey;
        $mac = sha1($signString);
        if ($mac == $result['MAC']){
            dump($result);
            echo 'success';
        }else{
            dump($result);
            echo 'fail';
        }
    }

    /**
     * 拉卡拉D0提款回调
     */
    public function getResultLkl(){
        $json = $_REQUEST;
        if (empty($data)) {
            $json = file_get_contents("php://input");
        }
        write_to_log('拉卡拉通知-' . $json, '/WxApi/Log/Lakala/');
        $data = json_decode($json, true);
        $result = M("Shop_lkl")->where("tranJnl='%s'", array($data['tranJnl']))->find();
    
    //巧玲修改
        if(empty($result)){
            $url = 'http://mk.xmjishiduo.com/mkapi.php/mkapi/Callback/getResultLkl';
            $this->request($url,true,'post',$json);
        }
         //巧玲修改

        $orderInfo = M("Order")->where("id=%d", array($result['order_id']))->find();
        if ($orderInfo['tra_status'] == 2){
            write_to_log('拉卡拉D0提款结果通知-订单尚未支付-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');exit();
        }

        if ($orderInfo['set_status'] == 1){
            write_to_log('拉卡拉D0提款结果通知-订单已完成-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');exit();
        }
        if ($data['responseCode'] == '000000'){
            $save['set_status'] = 1;
            $save['err_note'] = '';
        }else{
            $save['err_note'] = $data['message'];
        }
        
        $resultOfOrder = M("Order")->where("order_no='%s'", array($orderInfo['order_no']))->save($save);
        write_to_log('拉卡拉D0提款saveToOrder' . M("Order")->getDbError(), '/WxApi/Log/Lakala/');
        write_to_log('拉卡拉D0提款saveToOrder' . M("Order")->getDbError(), '/WxApi/Log/Lakala/');
        if ($resultOfOrder !== false){
            write_to_log('拉卡拉D0提款结果通知-保存失败-' . json_encode($save, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
            write_to_log('拉卡拉D0提款结果通知-保存失败-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
            write_to_log('拉卡拉D0提款结果通知-保存成功-' . $json, '/WxApi/Log/Lakala/');
        }else{
            write_to_log('拉卡拉D0提款结果通知-保存失败-' . json_encode($save, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
            write_to_log('拉卡拉D0提款结果通知-保存失败-' . json_encode($orderInfo, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Lakala/');
        }
        dump($data);
        dump($result);
    }

    /**
     * 米联微信支付宝回调
     * @author xxw
     * @date 2017年6月29日17:43:16
     */
    public function milian(){
        $data['ORDER_ID'] = $this->_post('ORDER_ID');
        if (!$data['ORDER_ID']){
            unset($data);
            $json = file_get_contents("php://input");
            $data = json_decode($json, true);
        }else {
            $data['ORDER_AMT'] = $this->_post('ORDER_AMT');
            $data['PAYCH_TIME'] = $this->_post('PAYCH_TIME');
            $data['ORDER_TIME'] = $this->_post('ORDER_TIME');
            $data['RESP_CODE'] = $this->_post('RESP_CODE');
            $data['USER_ID'] = $this->_post('USER_ID');
            $data['SIGN'] = $this->_post('SIGN');
            $data['SIGN_TYPE'] = $this->_post('SIGN_TYPE');
            $data['BUS_CODE'] = $this->_post('BUS_CODE');
            $data['CNY'] = $this->_post('CNY');
        }
        echo 'SUCCESS';
        write_to_log('米联微信支付宝：' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Callback/');
        $orderInfo = M('Posorder')->where("order_no='%s'", array($data['ORDER_ID']))->find();
        if(empty($orderInfo)){
            write_to_log('米联微信支付宝-该订单不存在   ' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Callback/');exit();
        }
        if(($orderInfo['status'] == 1) || ($orderInfo['status'] == 2) || ($orderInfo['status'] == 4)){
            write_to_log('米联微信支付宝-订单已发货   ' . json_encode($data, JSON_UNESCAPED_UNICODE), '/WxApi/Log/Callback/');
            return false;
        }
        //保存支付信息
        $dataPay = array();
        if($data['RESP_CODE'] == '0000'){
            //订单状态更改为待发货
            $dataPay['error_note'] = '';
            $dataPay['pay_time'] = time();
            if ($orderInfo['type'] == 2){
                //线下购买，订单状态改为已发货
                $dataPay['status'] = 1;
            }else{
                //线上购买
                $dataPay['status'] = 2;
            }
        }else{
            $dataPay['err_note'] = urldecode($data['RESP_DESC']);
        }
        $result = M('Posorder')->where("id=%d", array($orderInfo['id']))->save($dataPay);

        if($result !== false) {
            //新增一台未绑定设备
            $category = M("Poscategory")->field("category,channel_id,channel_name")->where("id=%d", array($orderInfo['pos_id']))->find();
            $Model = M("Shop");
            $shopInfo = $Model->field(array(
                'shop.id' => 'id',
                'shop.username' => 'tel',
                'system.id' => 'system_id',
                'system.system_name' => 'system_name',
                'platform.id' => 'platform_id',
                'platform.platform_name' => 'platform_name'
            ))
                ->table(array('eb_shop'=>'shop','eb_system'=>'system','eb_platform'=>'platform'))
                ->where("shop.id=%d AND system.id=shop.system_id AND platform.id=shop.platform_id", array($orderInfo['shop_id']))
                ->find();
            $posOrder['sn'] = '等待绑定设备';
            $posOrder['shop_id'] = $orderInfo['shop_id'];
            $posOrder['shop_name'] = $orderInfo['shop_name'];
            $posOrder['tel'] = $orderInfo['tel'];
            $posOrder['system_id'] = $shopInfo['system_id'];
            $posOrder['system_name'] = $shopInfo['system_name'];
            $posOrder['platform_id'] = $shopInfo['platform_id'];
            $posOrder['platform_name'] = $shopInfo['platform_name'];
            $posOrder['pos_id'] = $orderInfo['pos_id'];
            $posOrder['category'] = $category['category'];
            $posOrder['pos_name'] = $orderInfo['pos_name'];
            $posOrder['status'] = 2;
            $posOrder['channel_id'] = $category['channel_id'];
            $posOrder['channel_name'] = $category['channel_name'];
            $posOrder['refund_status'] = 2;
            $posOrder['openStatus'] = 1;
            $posOrder['create_time'] = time();
            $posManagement = M("Posmanagement");
            $posAddResult = $posManagement->add($posOrder);
            if ($posAddResult){
                write_to_log('米联微信支付宝-新增POS设备成功' . json_encode($posOrder, JSON_UNESCAPED_UNICODE), '/Log/Callback/');
            }else{
                write_to_log('米联微信支付宝-新增POS设备失败' . json_encode($data, JSON_UNESCAPED_UNICODE), '/Log/Callback/');
            }
            write_to_log('米联微信支付宝-订单状态更改成功' . json_encode($data, JSON_UNESCAPED_UNICODE), '/Log/Callback/');exit();
        }else{
            write_to_log('米联微信支付宝-订单状态更改失败' . json_encode($data, JSON_UNESCAPED_UNICODE), '/Log/Callback/');exit();
        }
    }

    public function ledgerQRCode(){
        $json = file_get_contents("php://input");
        $data = json_decode($json, true);
        write_to_log('分賬-订单原始数据   ' . json_encode($data), '/Log/Callback/');
        echo 'success';
        $orderInfo = M('Posorder')->where("order_no='%s'", array($data['msg']['outTradeNo']))->find();
        dump($orderInfo);
        if(empty($orderInfo)){
            write_to_log('分賬-该订单不存在   ' . json_encode($data), '/Log/Callback/');
            die();
        }
        if ($orderInfo['tra_status'] == 1){
            die();
        }
        if ($orderInfo['set_status'] == 1){
            die();
        }
        $saveData['id'] = $orderInfo['id'];
        if ($data['msg']['payStatus'] == 'success'){
            //订单状态更改为待发货
            $dataPay['error_note'] = '';
            $dataPay['pay_time'] = time();
            if ($orderInfo['type'] == 2){
                //线下购买，订单状态改为已发货
                $dataPay['status'] = 1;
            }else{
                //线上购买
                $dataPay['status'] = 2;
            }
        }else{
            $dataPay['err_msg'] = $data['msg']['errMsg'];
        }
        $result = M('Posorder')->where("id=%d", array($orderInfo['id']))->save($dataPay);
        if($result !== false) {
            //新增一台未绑定设备
            $category = M("Poscategory")->field("category,channel_id,channel_name")->where("id=%d", array($orderInfo['pos_id']))->find();
            $Model = M("Shop");
            $shopInfo = $Model->field(array(
                'shop.id' => 'id',
                'shop.username' => 'tel',
                'system.id' => 'system_id',
                'system.system_name' => 'system_name',
                'platform.id' => 'platform_id',
                'platform.platform_name' => 'platform_name'
            ))
                ->table(array('eb_shop'=>'shop','eb_system'=>'system','eb_platform'=>'platform'))
                ->where("shop.id=%d AND system.id=shop.system_id AND platform.id=shop.platform_id", array($orderInfo['shop_id']))
                ->find();
            $posOrder['sn'] = 'CBC'. date("YmdHis") . getNumNo(4);
            $posOrder['shop_id'] = $orderInfo['shop_id'];
            $posOrder['shop_name'] = $orderInfo['shop_name'];
            $posOrder['tel'] = $orderInfo['tel'];
            $posOrder['system_id'] = $shopInfo['system_id'];
            $posOrder['system_name'] = $shopInfo['system_name'];
            $posOrder['platform_id'] = $shopInfo['platform_id'];
            $posOrder['platform_name'] = $shopInfo['platform_name'];
            $posOrder['pos_id'] = $orderInfo['pos_id'];
            $posOrder['category'] = $category['category'];
            $posOrder['pos_name'] = $orderInfo['pos_name'];
            $posOrder['status'] = 2;
            $posOrder['channel_id'] = $category['channel_id'];
            $posOrder['channel_name'] = $category['channel_name'];
            $posOrder['refund_status'] = 2;
            $posOrder['openStatus'] = 1;
            $posOrder['create_time'] = time();
            $posManagement = M("Posmanagement");
            $posAddResult = $posManagement->add($posOrder);
            if ($posAddResult){
                write_to_log('米联微信支付宝-新增POS设备成功' . json_encode($posOrder, JSON_UNESCAPED_UNICODE), '/Log/Callback/');
            }else{
                write_to_log('米联微信支付宝-新增POS设备失败' . json_encode($data, JSON_UNESCAPED_UNICODE), '/Log/Callback/');
            }
            write_to_log('米联微信支付宝-订单状态更改成功' . json_encode($data, JSON_UNESCAPED_UNICODE), '/Log/Callback/');exit();
        }else{
            write_to_log('米联微信支付宝-订单状态更改失败' . json_encode($data, JSON_UNESCAPED_UNICODE), '/Log/Callback/');exit();
        }
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

    public function benefit(){
        $order = $this->_get("order");
        A("Liquidation")->shangyunke($order);
    }
    
    //回调中转
    public function transfer($param,$data,$role,$url){
        if(is_array($data[$role])){
            return;
        }else{
            $str = substr($data[$role], 0,2);
            if($str = 'mk'){
                $result = $this->request($url,true,'post',$param);
                return $result;
            }
        }
    }
}
