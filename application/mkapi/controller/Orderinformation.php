<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Db;
use think\Session;
use think\Log;
use app\mkapi\common\Qiniu\Auth;
class Orderinformation extends Common{
	
	//订单消息
	public function info(){
		$accessKey ="J0xi4pzpMCwBol2t5GiyCTOuE2zucp8y04_8Dcbh";
        $secretKey = "ltPzCfeDFPLTfgbJPPTEWBrpYryNQLclHgrNCPIy";
        $domain = "mkdownload.xmjishiduo.com";
        // 构建Auth对象
        $auth = new Auth($accessKey, $secretKey);

		$series = $_POST['series'];
		$orderList = Db::name('order_information')->field('a.id,a.content,a.order_id,b.delivery_no,c.goods_name,c.goods_thumb,a.update_time')->alias('a')->join('order b','a.order_id = b.id')->join('goods c','b.goods_id = c.id')->where('a.series',$series)->order('a.update_time desc')->select();
		foreach ($orderList as $key => $value) {
			 // 私有空间中的外链 http://<domain>/<file_key>
			$name = substr($value['goods_thumb'], 0,strpos($value['goods_thumb'], '.'));
	        $baseUrl = 'http://'.$domain.'/goods/'.$value['goods_thumb'].'?imageMogr2/interlace/1/format/webp&attname='.$name.'.webp';
			$orderList[$key]['goods_thumb'] = $baseUrl;
		}
		my_json_encode(10000,'success',$orderList);
	}
}
	