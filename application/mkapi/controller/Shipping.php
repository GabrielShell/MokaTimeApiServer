<?php
/**
*管理收货地址
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
class Shipping extends Common{

	//新增收货地址
	public function insert(){
		$data['series'] = $_POST['series'];
		$data['province'] = isset($_POST['province']) ? $_POST['province'] : null;
		$data['city'] = isset($_POST['city']) ? $_POST['city'] : null;
		$data['district'] = isset($_POST['district']) ? $_POST['district'] : null;
		$data['street'] = isset($_POST['street']) ? $_POST['street'] : null;
		$data['consignee'] = isset($_POST['consignee']) ? $_POST['consignee'] : null;
		$data['phone'] = isset($_POST['phone']) ? $_POST['phone'] : null;
		$data['create_time'] = time();
		if($data['province'] == null || $data['city'] == null || $data['district'] == null || $data['street'] == null || $data['consignee'] == null || $data['phone'] == null){
			my_json_encode(8,'参数错误');
			exit();
		}
		
		//设置为默认地址
		if(isset($_POST['is_default'])){
			$data['is_default'] = $_POST['is_default'];
			Db::name('shipping')->where('series',$data['series'])->update(['is_default'=>0]);
		}

		$result = Db::name('shipping')->insert($data);
		if(!$result){
			$errorId = uniqid('ERR');
			Log::error('【'.$errorId.'】新增收货地址失败');
			my_json_encode(10002,'新增收货地址失败：errorId ='.$errorId);
		}else{
			my_json_encode(10000,'success');
		}
	}

	//编辑收货地址
	public function update(){
		$series = $_POST['series'];
		if(isset($_POST['province'])){
			$data['province'] = $_POST['province'];
		}
		if(isset($_POST['city'])){
			$data['city'] = $_POST['city'];
		}
		if(isset($_POST['district'])){
			$data['district'] = $_POST['district'];
		}
		if(isset($_POST['street'])){
			$data['street'] = $_POST['street'];
		}
		if(isset($_POST['consignee'])){
			$data['consignee'] = $_POST['consignee'];
		}
		if(isset($_POST['phone'])){
			$data['phone'] = $_POST['phone'];
		}

		$data['update_time'] = time();
		$data['id'] = isset($_POST['shipping_id']) ? $_POST['shipping_id'] : null;
		
		if($data['id'] == null){
			my_json_encode(8,'参数错误');
			exit();
		}
		//设置为默认地址
		if(isset($_POST['is_default']) && $_POST['is_default'] == 1){
			$data['is_default'] = $_POST['is_default'];
			Db::name('shipping')->where('series',$series)->update(['is_default'=>0]);
		}

		$result = Db::name('shipping')->update($data);
		if(!$result){
			$errorId = uniqid('ERR');
			Log::error('【'.$errorId.'】更新收货地址失败');
			my_json_encode(10002,'更新收货地址失败：errorId ='.$errorId);
		}else{
			my_json_encode(10000,'success');
		}
	}

	//删除收货地址
	public function delete(){
		$shipping_id = isset($_POST['shipping_id']) ? $_POST['shipping_id'] : null;
		if($shipping_id == null){
			my_json_encode(8,'参数错误');
			exit();
		}

		$result = Db::name('shipping')->delete($shipping_id);
		if(!$result){
			$errorId = uniqid('ERR');
			Log::error('【'.$errorId.'】删除收货地址失败');
			my_json_encode(10002,'删除收货地址失败：errorId ='.$errorId);
		}else{
			my_json_encode(10000,'success');
		}
	}

	//读取用户所有收货地址
	public function select(){
		$series = $_POST['series'];
		$result = Db::name('shipping')->field('id as shipping_id,series,province,city,district,street,consignee,phone,is_default')->where('series',$series)->select();

		my_json_encode(10000,'success',$result);
	}
}