<?php
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
class Bank extends Controller{

	//获取银行信息
	public function getBank(){
		$bankList = Db::name('lakala_bank')->field('id bank_id,bank_name')->order('id asc')->select();
		if(empty($bankList)){
			$errorId = uniqid('sqlErr');
			Log::sql("【"$errorId"】银行信息获取失败");
			my_json_encode(10002,'数据获取失败');
		}else{
			my_json_encode(10000,'success',$bankList);
		}
	}

	// 获取支行信息
	public function getBankBranch(){
		$request = Request::instance();
		//收集表单数据
		$bank_id = $request->post('bank_id');//银行id
		$province = $request->post('province');
		$city = $request->post('city');

		// $bank_id ="2";//银行id
		// $province = "福建省";
		// $city = "三明市";

		$bankBranchList = Db::name('lakala_bankbranch')->field('bankbranch_no,bankranch_name')->where([
			'bank_id'=>['=',$bank_id],
			'province' =>['like',$province.'%'],
			'city' => ['like',$city.'%']
			])->order('bankbranch_no asc')->select();
		if(empty($bankBranchList)){
			$errorId = uniqid('sqlErr');
			Log::sql("【"$errorId"】支行信息获取失败");
			my_json_encode(10002,'数据获取失败');
		}else{
			// echo "<pre>";
			// var_dump($bankBranchList);
			my_json_encode(10000,'success',$bankBranchList);
		}
	}

}