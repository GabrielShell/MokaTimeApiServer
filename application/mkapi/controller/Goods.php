<?php
/*
*商品管理
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
use app\mkapi\common\Qiniu\Auth;
class Goods extends Common{
	private $accessKey ="J0xi4pzpMCwBol2t5GiyCTOuE2zucp8y04_8Dcbh";
    private $secretKey = "ltPzCfeDFPLTfgbJPPTEWBrpYryNQLclHgrNCPIy";
    private $domain = "mkdownload.xmjishiduo.com";
	//获取商品列表
	public function goodsList(){
		$goodsList = Db::name('goods')->field('id as goods_id,goods_name,goods_money,goods_thumb,is_limit,limit_money,goods_desc')->select();
		$auth = new Auth($this->accessKey, $this->secretKey);
		foreach ($goodsList as $key => $value) {
			$name = substr($value['goods_thumb'],0, strpos($value['goods_thumb'], '.'));
			$baseUrl = 'http://'.$this->domain.'/goods/'.$value['goods_thumb'];
			// $goodsList[$key]['goods_thumb'] = $auth->privateDownloadUrl($baseUrl);
			$goodsList[$key]['goods_thumb'] = $baseUrl;
			
		}
		my_json_encode(10000,'success',$goodsList);
	}

	//获取商品详情
	public function getDetail(){
		$goodsId = $_POST['goods_id'];
		//================================获取商品属性===========================//
		$goodsAttribute = Db::name('goods_attribute')->field('id as attribute_id,attribute_name,attribute_value,attribute_img')->where('goods_id',$goodsId)->select();
		$auth = new Auth($this->accessKey, $this->secretKey);
		foreach($goodsAttribute as $key => $value){
			$name = substr($value['attribute_img'],0, strpos($value['attribute_img'], '.'));
			$baseUrl = 'http://'.$this->domain.'/goods/attribute/'.$value['attribute_img'].'?imageMogr2/interlace/1/format/webp&attname='.$name.'.webp';
			// $goodsAttribute[$key]['attribute_img'] = $auth->privateDownloadUrl($baseUrl);
			$goodsAttribute[$key]['attribute_img'] = $baseUrl;
		}

		//==============================重组商品属性=============================//
		$reorganization = array();
		$index = 0;
		foreach($goodsAttribute as $key => $value){
			$reorganization[$value['attribute_name']]['attribute_value'][$index]['id'] = $value['attribute_id'];
			$reorganization[$value['attribute_name']]['attribute_value'][$index]['value'] = $value['attribute_value'];
			$reorganization[$value['attribute_name']]['attribute_value'][$index]['img'] = $value['attribute_img'];
			$index++;
		}

		$attributeList = array();
		$index = 0;
		foreach ($reorganization as $key => $value){
			$attributeList[$index]['attribute_name'] = $key;
			$attributeList[$index]['attribute_value'] = $value['attribute_value'];
			$index++;
		}
		
		//==============================重组商品属性=============================//

		//=====================商品详情（轮播图片，图文详情）======================//

		$goodsAlbum = Db::name('commodity_album')->field('img_url')->where('goods_id',$goodsId)->order('is_font desc')->select();
		foreach($goodsAlbum as $key => $value){
			$name = substr($value['img_url'],0, strpos($value['img_url'], '.'));
			$baseUrl = 'http://'.$this->domain.'/goods/album/'.$value['img_url'].'?imageMogr2/interlace/1/format/webp&attname='.$name.'.webp';
			// $imgUrl[] = $auth->privateDownloadUrl($baseUrl);
			$imgUrl[] = $baseUrl;
		}

		//获取去商品详情图片
		$goodsDetail = Db::name('goods')->field('goods_detail')->where('id',$goodsId)->find();
		// $detailUrllist = explode(',', $goodsDetail['goods_detail']);
		// foreach ($detailUrllist as $key => $value) {
		// 	$baseUrl = 'http://'.$this->domain.'/goods/detail/'.$value;
		// 	$detailUrllist[$key] = $auth->privateDownloadUrl($baseUrl);
 	// 	}
		$name = substr($goodsDetail['goods_detail'],0, strpos($goodsDetail['goods_detail'], '.'));
		$baseUrl = 'http://'.$this->domain.'/goods/detail/'.$goodsDetail['goods_detail'].'?imageMogr2/interlace/1/format/webp&attname='.$name.'.webp';
		// $goodsDetail = $auth->privateDownloadUrl($baseUrl);
		$goodsDetail = $baseUrl;

		$returnInfo['goods_details']['runbo'] = $imgUrl;
		$returnInfo['goods_details']['detail_img'] = $goodsDetail;
		$returnInfo['goods_attribute'] = $attributeList;
		
		my_json_encode(10000,'success',$returnInfo);
	}

}