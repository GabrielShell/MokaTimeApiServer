<?php
namespace app\mkapi\common\Xinyan;
use think\Controller;
use think\Request;
class Order extends Controller{
	static  function trade_date(){//生成时间
        date_default_timezone_set('PRC');
        return date('YmdHis',time());

    }
    /**
     * 生成唯一订单号
     */
    static function create_uuid($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        // $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }
    static function create_uuids($prefix = ""){    //可以指定前缀
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4);
    //    $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }
}