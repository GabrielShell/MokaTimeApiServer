<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use app\mkapi\model\Tradenotice as Tn;
class CjTradeNotice extends Controller{
    public function index(Request $request){
        header('Content-type: application/json;charset=GBK');

        $params = $request->param();
        $res = json_encode($params);

        $tn = new Tn;
        $tn->content = $res;
        $tn->save();

        $returnArr = [
            'tranTime' => array_key_exists('tranTime',$params) ? $params['tranTime'] : '',
            'tranNo' => array_key_exists('tranNo',$params) ? $params['tranNo'] : '',
            'rrn' => array_key_exists('rrn',$params) ? $params['rrn'] : '',
            'merchantId' => array_key_exists('merchantId',$params) ? $params['merchantId'] : '',
            'termId' => array_key_exists('termId',$params) ? $params['termId'] : '',
            'responseCode' => '00',
            'responseDesc' => '通知成功',
            'extData' => '',
        ];

        $sign = self::sign($returnArr);
        $returnArr['sign'] = $sign;
        echo json_encode($returnArr,JSON_UNESCAPED_UNICODE);
    }

    public static function sign($array){
        ksort($array);
        $valueStr = '';
        foreach($array as $item){
            $valueStr = $valueStr . $item;
        }

        //测试环境密钥串(key)
        $key = '0123456789ABCDEFFEDCBA9876543210';

        $valueStr = $key . $valueStr;
        
        return md5($valueStr);
        
    }
}