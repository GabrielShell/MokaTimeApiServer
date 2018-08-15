<?php

/**
*用户管理控制器
*/
namespace app\mkapi\controller;
use think\Controller;
use think\Request;
use think\Db;
use think\Session;
use think\Log;
class User extends Common{
	//用户登录
	public function sendVerify(){
		// 收集表单信息
		$request = Request::instance();
		//用户电话号码
		// $data['phone'] = '17359491816';
		$data['phone'] = $request->post('phone');
		if(!preg_match('/^1[34578]{1}\d{9}$/', $data['phone'])){
            my_json_encode(2, '手机号码错误');
        }else{
        	//生成验证码
        	$verify_code = getNumNo(4);
        	@Session::set($data['phone'],$verify_code);
        	//发送短信
        	$sms = new Sms();
        	$res = $sms->send( $data['phone'], '您的短信验证码是：'.$verify_code.'请尽快进行验证【厦门刷呗】');
        	if( $res ){
			    if( isset( $res['error'] ) &&  $res['error'] == 0 ){
			        my_json_encode(0, 'success');
			    }else{
			    	if($res['error'] == '-42'){
			    		$msg = '验证码发送太频繁';
			    	}else if($res['error'] == '-40'){
			    		$msg = '手机号错误';
			    	}

			    	$errorId = uniqid('ERR');
					Log::error("【".$errorId."】验证码发送错误：".$res['msg']);
			        my_json_encode($res['error'],$msg);
			    }
			}else{
			    my_json_encode(3,'error',$sms->last_error());
			}

        }
	}

	// 用户登录
	public function login(){
		$request = Request::instance();
		// 收集表单信息
		$verify_code = $request->post('verify_code');
		$data['phone'] = $request->post('phone');
		$data['sys_type'] = $request->post('sys_type');
		$data['device_token'] = $request->post('device_token');
		//判断验证码是否正确
      	//echo 'pre';
      	//echo Session::get($data['phone']);
      	//echo $verify_code;
      //exit();
		if(Session::get($data['phone']) !== $verify_code){
			my_json_encode(5,'验证码错误');
			exit();
		}
		Session::delete($data['phone']);
		if(empty($data)){
			my_json_encode(8,'参数不正确');
		}else{
			//实例化模型
			$User = model('Users');
			$result = $User->where('phone',$data['phone'])->find();
			//判断用户是否已经注册
			if(empty($result)){
				$series = $this->register($data);
			}else{
				$series = $result['series'];
				$User->save(['device_token' => $data['device_token']],['series'=>$series]);
			}

			//实例化tokenMessage模型
			$tokenMessage = model('TokenMessages');
			//储存token数据
			$token_data['access_token'] = getKey(32);
			$token_data['refresh_token'] = getKey(32);
			$token_data['ac_start_time'] = time();
			$token_data['re_start_time'] = time();

			$toke_result = $tokenMessage->where('series',$series)->find();
			if(!empty($toke_result)){
				$saveResult = $tokenMessage->save($token_data,['series' => $series]);
			}else{
				$token_data['series'] = $series;//用户唯一标识
				$tokenMessage->data($token_data);
				$saveResult = $tokenMessage->save();
				//判断token是否储存成功
			}

			//判断数据是否返回成功
			if($saveResult){
				$responseData = array(
					'series' => $series,
					'access_token' => $token_data['access_token'],
					'refresh_token' => $token_data['refresh_token']
					);
				    my_json_encode(0,'success',$responseData);
			}else{
				$errorId = uniqid('sqlErr');
				Log::sql("【".$errorId."】token储存失败");
				my_json_encode(9,'token储存失败:errorId='.$errorId);
            }
		}
	}

	//用户刷新access_token重新登录
	public function reLogin(){
		$request = Request::instance();
		//收集表单数据
		$series = $request->post('series');
		$access_token = $request->post('access_token');
		$refresh_token = $request->post('refresh_token');
		//验证access_token格式是否正确
        if(empty($access_token) || !preg_match('/[a-z0-9]{32}/', $access_token)){
            my_json_encode(8, 'access_token为空或者格式不正确');
        }

        //验证refresh_token格式是否正确
        if(empty($refresh_token) || !preg_match('/[a-z0-9]{32}/', $refresh_token)){
            my_json_encode(8, 'refresh_token为空或者格式不正确');
        }

        // 判断token是否存在
       $result = Db::query("select * from mk_token_messages where series = '$series' and re_start_time > unix_timestamp()-2592000");
    	//var_dump($result);
        if(empty($result)){//token过期
        	my_json_encode(7, 'token过期，请重新登录');
        }else{
        	if($access_token !== $result[0]['access_token'] || $result[0]['ac_start_time'] < time() - 3600){
        		$data['access_token'] = getkey(32);
        		$data['ac_start_time'] = time();
              	$tokenMessage = model('tokenMessages');
        		$tokenMessage->save($data,['series' => $series]);
        		my_json_encode(6,'notice',array('access_token已过期，请刷新token','access_token'=>$data['access_token']));
        	}else{
        		my_json_encode(0,'success');
        	}
        }
	}

	/**
	*用户注册
	*@param $data array 用户信息
	*@return string 用户唯一标识
	*/
	public function register($data){
		//实例化用户模型
		$User = model('Users');
		$data['series'] = getUserId(6,$data['phone']);
		$User->data($data);
		if($User->save()){
			return $data['series'];
		}
	}

	//用户退出登录
	public function logout(){
		// 收集表单信息
		$request = Request::instance();
		//获取用户唯一标识
		$series = $request->post('series');
        //$series = "mk15570161512922579";
		if($series == null){
			my_json_encode(8,'请求参数错误');
		}else{
        	$tokenMessage = model('TokenMessages');
			$result = $tokenMessage->destroy(['series'=>$series]);
        }
		if($result){
			my_json_encode(0,'success');
		}else{
			my_json_encode(9,'发生系统错误');
		}
	}



	// //用户实名认证
	// public function certificate(){
	// 	$request = Request::instance();
	// 	$data['series'] = $request->post('series');
	// 	$data['card_no'] = $request->post('card_no');
	// 	$data['real_name'] = $request->post('real_name');
	// 	$data['bank_no'] = $request->post('bank_no');
	// 	$data['bankbranch_id'] = $request->post('bankbranch_id');

	// 	if(empty($data['series']) || empty($data['card_no']) || empty($data['real_name']) || empty($data['bank_no'])){
	// 		my_json_encode(8,"参数不正确");
	// 		exit();
	// 	}

	// 	// 发送身份验证信息，并得到返回结果
	// 	$result = $this->certificateRequest($data['bank_no'],$data['card_no'],$data['real_name']);
	// 	$result = json_decode($result,true);
	// 	$msg = '';

	// 	if($result == null){
	// 		$errorId = uniqid("ERR");
	// 		Log::error("【".$errorId."】" .json_encode($result,JSON_UNESCAPED_UNICODE));
	// 		my_json_encode(9,'服务器错误：errorid='.$errorId);
	// 		exit();
	// 	}
	// 	//判断返回状态码
	// 	switch($result['status']){
	// 		case '01': 
	// 					$status = 0;
	// 				    $msg = '验证通过';break;
	// 	}


	// 	// 身份验证通过
	// 	if($result['status'] == '01'){
	// 		// 采集用户信息
	// 		$data['bank_name'] = $result['bank'];  //开户行名称
	// 		$data['card_name'] = $result['cardName'];  //银行卡名称
	// 		$data['card_type'] = $result['cardType'];  //银行卡类型
	// 		$data['sex'] = $result['sex'];  //性别
	// 		$data['province'] = $result['province'];  //省
	// 		$data['city'] = $result['city'];  //市
	// 		$data['prefecture'] = $result['prefecture'];  //区县
	// 		$data['birthday'] = $result['birthday'];  //生日
	// 		$data['addr_code'] = $result['addrCode']; //地区代码

	// 		// 收集用户证件照
	// 		$cardFace = $request->file('cardFace') !== null ? $request->file('cardFace'):null; //身份证正面
	// 		$cardBack = $request->file('cardBack') !== null ? $request->file('cardBack'):null;  //身份证反面
	// 		$bankFace = $request->file('bankFace') !== null ? $request->file('bankFace'):null;  //银行卡正面
	// 		//判断是否上传了文件
	// 		if($cardFace !== null && $cardBack !== null && $bankFace !== null){
	// 			// 上传文件
	// 			$cardFaceInfo = $this->uploadImg($cardFace,$data['series']);
	// 			$cardBackInfo = $this->uploadImg($cardBack,$data['series']);
	// 			$bankFaceInfo = $this->uploadImg($bankFace,$data['series']);
	// 			if($cardFaceInfo['msg'] == 'success' && $cardBackInfo['msg'] == 'success' && $bankFaceInfo['msg'] == 'success'){
	// 				//数据库中储存文件路径
	// 				$data['card_face_img'] = "user/card/".$cardFaceInfo['data'];
	// 				$data['card_back_img'] = "user/card/".$cardBackInfo['data'];
	// 				$data['bank_face_img'] = "user/card/".$bankFaceInfo['data'];
	// 				$data['is_certificate'] = "1";

	// 			}else{
	// 				$data['is_certificate'] = "2";
	// 				my_json_encode(10,'文件上传错误',array('cardFace'=>$cardFaceInfo,'cardBack'=>$cardBackInfo,'bankFace'=>$bankFaceInfo));
	// 			}
	// 		}else{
	// 			$data['is_certificate'] = "2";
	// 		}
			
	// 		//更新用户信息
	// 		$users = model('Users');
	// 		if($users->save($data,['series'=>$data['series']])){
	// 			my_json_encode($status,$msg);

	// 		}else{
	// 			$errorId = uniqid("ERR");
	// 			Log::error("【".$errorId."】" .json_encode($data,JSON_UNESCAPED_UNICODE));
	// 			my_json_encode(9,'数据储存失败：errorId='.$errorId);
	// 		}

	// 	}else{
			
	// 		my_json_encode(102,$result['msg']);
	// 	}
	// }

	//用户实名认证
	public function certificate(){
		$request = Request::instance();
		$data['series'] = $request->post('series');
		$data['card_no'] = $request->post('card_no');
		$data['real_name'] = $request->post('real_name');
		$data['bank_no'] = $request->post('bank_no');
		$data['bankbranch_id'] = rand(1,3000);

		if(empty($data['series']) || empty($data['card_no']) || empty($data['real_name']) || empty($data['bank_no'])){
			my_json_encode(8,"参数不正确");
			exit();
		}

		// 发送身份验证信息，并得到返回结果
		$result = $this->certificateRequest($data['bank_no'],$data['card_no'],$data['real_name']);
		$result = json_decode($result,true);
		$msg = '';

		if($result == null){
			$errorId = uniqid("ERR");
			Log::error("【".$errorId."】" .json_encode($result,JSON_UNESCAPED_UNICODE));
			my_json_encode(9,'服务器错误：errorid='.$errorId);
			exit();
		}
		//判断返回状态码
		switch($result['resp']['code']){
			case '0': 
					$status = 0;
					$msg = '验证通过';break;	
		}


		// 身份验证通过
		if($result['resp']['code'] == '0'){
			// 采集用户信息
			$data['bank_name'] = $result['data']['bank_name'];  //开户行名称
			$data['card_name'] = $result['data']['card_name'];  //银行卡名称
			$data['card_type'] = $result['data']['card_type'];  //银行卡类型
			$data['bank_logo'] = $result['data']['bank_logo'];  //银行logo

			// 收集用户证件照
			$cardFace = $request->file('cardFace') !== null ? $request->file('cardFace'):null; //身份证正面
			$cardBack = $request->file('cardBack') !== null ? $request->file('cardBack'):null;  //身份证反面
			$bankFace = $request->file('bankFace') !== null ? $request->file('bankFace'):null;  //银行卡正面
			//判断是否上传了文件
			if($cardFace !== null && $cardBack !== null && $bankFace !== null){
				// 上传文件
				$cardFaceInfo = $this->uploadImg($cardFace,$data['series']);
				$cardBackInfo = $this->uploadImg($cardBack,$data['series']);
				$bankFaceInfo = $this->uploadImg($bankFace,$data['series']);
				if($cardFaceInfo['msg'] == 'success' && $cardBackInfo['msg'] == 'success' && $bankFaceInfo['msg'] == 'success'){
					//数据库中储存文件路径
					$data['card_face_img'] = "user/card/".$cardFaceInfo['data'];
					$data['card_back_img'] = "user/card/".$cardBackInfo['data'];
					$data['bank_face_img'] = "user/card/".$bankFaceInfo['data'];
					$data['is_certificate'] = "1";

				}else{
					$data['is_certificate'] = "2";
					my_json_encode(10,'文件上传错误',array('cardFace'=>$cardFaceInfo,'cardBack'=>$cardBackInfo,'bankFace'=>$bankFaceInfo));
				}
			}else{
				$data['is_certificate'] = "2";
			}
			
			//更新用户信息
			$users = model('Users');
			if($users->save($data,['series'=>$data['series']])){
				my_json_encode($status,$msg);

			}else{
				$errorId = uniqid("ERR");
				Log::error("【".$errorId."】" .json_encode($data,JSON_UNESCAPED_UNICODE));
				my_json_encode(9,'数据储存失败：errorId='.$errorId);
			}

		}else{
			
			my_json_encode(102,$result['resp']['desc']);
		}
	}


	//用户上传身份证照片
	public function uploadCard(){
		$request = Request::instance();
		$series = $request->post('series');
		// 收集用户证件照
		$cardFace = $request->file('cardFace') !== null ? $request->file('cardFace'):null; //身份证正面
		$cardBack = $request->file('cardBack') !== null ? $request->file('cardBack'):null;  //身份证反面
		$bankFace = $request->file('bankFace') !== null ? $request->file('bankFace'):null;  //银行卡正面

		if($cardFace !== null && $cardBack !== null && $bankFace !== null){
			// 上传文件
			$cardFaceInfo = $this->uploadImg($cardFace,$series);
			$cardBackInfo = $this->uploadImg($cardBack,$series);
			$bankFaceInfo = $this->uploadImg($bankFace,$series);
			if($cardFaceInfo['msg'] == 'success' && $cardBackInfo['msg'] == 'success' && $bankFaceInfo['msg'] == 'success'){
				//数据库中储存文件路径
				$data['card_face_img'] = "user/card/".$cardFaceInfo['data'];
				$data['card_back_img'] = "user/card/".$cardBackInfo['data'];
				$data['bank_face_img'] = "user/card/".$bankFaceInfo['data'];
				$data['is_certificate'] = "1";
				//更新用户信息
				$users = model('Users');
				if($users->save($data,['series'=>$series])){
					my_json_encode(10000,'success');

				}else{
					$errorId = uniqid("ERR");
					Log::error("【".$errorId."】" .json_encode($data,JSON_UNESCAPED_UNICODE));
					my_json_encode(9,'数据储存失败：errorId='.$errorId);
				}

			}else{
				my_json_encode(10,'文件上传错误',array('cardFace'=>$cardFaceInfo,'cardBack'=>$cardBackInfo,'bankFace'=>$bankFaceInfo));
			}
		}else{
			my_json_encode(8,'请上传三张证件照');
		}
	}

	/**
	*上传文件
	*@param $file file post提交的文件类
	*@return array 文件上传结果
	*/
	public function uploadImg($file,$series){
        $upload = new Upload();
        // 验证文件是否合法
        $result = $upload->check($file);
        if($result == 'success'){
        	//文件保存位置
            $path = APP_PATH.'/mkapi/public/upload/user/card/';
            $result = $upload->uploadOne($file,$path,$series);
            return $result;
        }else{
            return array('msg'=>'error','data'=>$result);
        }
	}


	/**
	*实名认证接口请求
	*@param $accountNo string 银行卡号
	*@param $idCard string 身份证号
	*@param $name string 姓名
	*@return array  第三方返回请求结果
	*/
	public function certificateRequest($accountNo,$idCard,$name){
		$host = "http://lundroid.market.alicloudapi.com";
	    $path = "/lianzhuo/verifi";
	    $method = "GET";
	    $appcode = "770c11c80bdf461690d2944da6acc06e";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "acct_pan=$accountNo&cert_id=$idCard&acct_name=$name";
	    $bodys = "";
	    $url = $host . $path . "?" . $querys;

	    $curl = curl_init();
	    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
	    curl_setopt($curl, CURLOPT_URL, $url);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	    curl_setopt($curl, CURLOPT_FAILONERROR, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    if (1 == strpos("$".$host, "https://"))
	    {
	        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
	        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
	    }
	    $out_put = curl_exec($curl);
	    return $out_put;
	}


	/**
	*获取用户信息
	*@return array 放回信息列表
	*/
	public function getInfo(){
		$request = Request::instance();
		$series = $request->post('series');
		$result = Db::name('users')->field('real_name,card_no')->where('series',$series)->find();
		if(!$result){
			$errorId = uniqid('sqlErr');
			Log::sql("【".$errorId."】查找用户信息失败");
			return my_json_encode(10002,'信息获取失败：'.$errorId);
		}else{
			return my_json_encode(10000,'success',$result);
		}
	}


	/**
	*获取用户信息
	*@return array 放回信息列表
	*/
	public function index(){
		$series = $_POST['series'];
		$userInfo = Db::name('users')->field('real_name,phone,is_certificate,is_d0,is_merchant,user_point,bank_no,card_no,bank_name')->where('series',$series)->find();
		$beginToday = mktime(0,0,0,date('m'),date('d'),date('y'));
		
		if($userInfo['is_d0'] == 1){
			$callback = new Callback();
			$userInfo['balance'] = $callback->getMoneyByLkl($series);
		}else{

			$balance= Db::name('lakala_order')->where([
				'series' => ['=',$series],
				'pay_time' => ['>',$beginToday],
				'trade_status' =>['=',2],
				'is_withdraw' => ['=','n'],
			])->sum('arrive_money');

			$balance = bcadd($balance,0,2);
			$userInfo['balance'] = $balance;
		}

		$userInfo['today_income'] = Db::name('lakala_order')->where([
				'series' => ['=',$series],
				'pay_time' => ['>',$beginToday],
				'trade_status' =>['=',2],
			])->sum('order_money');


		$userInfo['gross_income'] = Db::name('lakala_order')->where([
				'series' => ['=',$series],
				'trade_status' =>['=',2],
			])->sum('order_money');
		
		return my_json_encode(10000,'success',$userInfo);
	}

}