<?php
/*
*商城管理
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
class Goods extends Common{
	public function goodsList(){
		$goodsList = Db::name('goods')->field('id as goods_id,goods_name,goods_img,goods_money,is_limit,limit_money')->select();
		foreach ($goodsList as $key => $value) {
			$handle = fopen($value['goods_img'],'r');
			$imgData = fread($handle,filesize($value['goods_img']));
			$goodsList[$key]['goods_img'] = base64_encode($imgData);
			fclose($handle);
		}
		my_json_encode(10000,'success',$goodsList);
	}
}