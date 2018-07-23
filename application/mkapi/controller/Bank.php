<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
class Bank extends Controller{

	//获取银行信息
	public function getBank(){
		$bankList = Db::name('lakala_bank')->field('id bank_id,bank_name')->select();
		if(empty($bankList)){
			my_json_encode(10002,'数据获取失败');
		}else{
			my_json_encode(10000,'success',$bankList);
		}
	}

	// 获取支行信息
	public function getBankBranch(){
		$request = Request::instance();
		// $bank_id = $request->post('bank_id');//银行id
		// $province = $request->post('province');
		// $city = $request->post('city');

		$bank_id ="2";//银行id
		$province = "福建省";
		$city = "三明市";

		$bankBranchList = Db::name('lakala_bankbranch')->field('bank_id,bankranch_name,province,city')->where([
			'bank_id'=>['=',$bank_id],
			'province' =>['like',$province.'%'],
			'city' => ['like',$city.'%']
			])->select();
		if(empty($bankBranchList)){
			my_json_encode(10002,'数据获取失败');
		}else{
			// echo "<pre>";
			// var_dump($bankBranchList);
			my_json_encode(10000,'success',$bankBranchList);
		}
	}

}