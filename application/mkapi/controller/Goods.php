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
class Goods extends Common{
	//获取商品列表
	public function goodsList(){
		$goodsList = Db::name('goods')->field('id as goods_id,goods_name,goods_money,is_limit,limit_money')->select();
		my_json_encode(10000,'success',$goodsList);
	}

	//获取商品图片列表
	public function goodsImgList(){
		$goodsImgList = Db::name('goods')->field('id as goods_id,goods_img')->select();
		foreach ($goodsImgList as $key => $value) {
			$handle = fopen($value['goods_img'],'r');
			$imgData = fread($handle,filesize($value['goods_img']));
			$goodsImgList[$key]['goods_img'] = base64_encode($imgData);
			fclose($handle);
		}
		my_json_encode(10000,'success',$goodsImgList);
	}

	//获取商品属性
	public function goodsAttribute(){
		$goodsId = $_POST['goods_id'];
		$goodsAttribute = Db::name('goods_attribute')->field('id as attribute_id,attribute_name,attribute_value,attribute_img')->where('goods_id',$goodsId)->select();
		foreach($goodsAttribute as $key => $value){
			$handle = fopen($value['attribute_img'],'r');
			$imgData = fread($handle,filesize($value['attribute_img']));
			$goodsAttribute[$key]['attribute_img'] = base64_encode($imgData);
			fclose($handle);
		}

		$reorganization = array();
		foreach($goodsAttribute as $key => $value){
			$reorganization[$value['attribute_name']]['attribute_value'][] = $value['attribute_value'];
			$reorganization[$value['attribute_name']]['attribute_id'][] = $value['attribute_id'];
			$reorganization[$value['attribute_name']]['attribute_img'][] = $value['attribute_img'];
		}

		$attributeList = array();
		foreach ($reorganization as $key => $value){
			$attributeList[]['attribute_name'] = $key;
			$attributeList[]['attribute_value'] = $value['attribute_value'];
			$attributeList[]['attribute_id'] = $value['attribute_id'];
			$attributeList[]['attribute_img'] = $value['attribute_img'];
		}
		
		my_json_encode(10000,'success',$attributeList);
	}
}