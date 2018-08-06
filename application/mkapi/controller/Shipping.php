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
		$data['province'] = $_POST['province'];
		$data['city'] = $_POST['city'];
		$data['district'] = $_POST['district'];
		$data['street'] = $_POST['street'];
		$data['consignee'] = $_POST['consignee'];
		$data['phone'] = $_POST['phone'];
	
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
			my_json_encode(10000,'新增收货地址成功');
		}
	}

	//编辑收货地址
	public function update(){

	}

	//删除收货地址
	public function delete(){

	}

	//读取用户所有收货地址
	public function select(){

	}
}