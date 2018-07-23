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
		$data['phone_type'] = $request->post('phone_type');
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
				if(!$series = $this->register($data)){
					my_json_encode(9,'系统异常');
			 	}
			}else{
				$series = $result['series'];
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
					'msg' => 'success',
					'series' => $result['series'],
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

	// 用户注册
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


	//用户实名认证
	public function certificate(){
		$request = Request::instance();
		$data['series'] = $request->post('series');
		$data['card_no'] = $request->post('card_no');
		$data['real_name'] = $request->post('real_name');
		$data['bank_no'] = $request->post('bank_no');
		$data['bankbranch_id'] = $request->post('bankbranch_id');

		// 收集用户证件照
		$cardFace =$request->file('cardFace'); //身份证正面
		$cardBack =$request->file('cardBack');  //身份证反面
		$bankFace =$request->file('bankFace');  //银行卡正面

		if(empty($data['series']) || empty($data['card_no']) || empty($data['real_name']) || empty($data['bank_no'])){
			my_json_encode(8,"参数不正确");
			exit();
		}

		// 发送身份验证信息，并得到返回结果
		$result = $this->certificateRequest($data['bank_no'],$data['card_no'],$data['real_name']);
		$result = json_decode($result,true);
		$msg = '';

		//判断返回状态码
		switch($result['status']){
			case '01': 
						$status = 101;
					    $msg = '验证通过';break;
			case '02': 
						$status = 102;
						$msg = '验证不通过';break;
			case '202': 
						$status = 202;
						$msg = '无法验证';break;
			case '203': 
						$status = 203;
						$msg = '异常情况';break;
			case '204': 
						$status = 204;
						$msg = '姓名错误';break;
			case '205': 
						$status = 205;
						$msg = '身份证号错误';break;
			case '206': 
						$status = 206;
						$msg = '银行卡号错误';break;
		}

		// 身份验证通过
		if($result['status'] == '01'){
			// 采集用户信息
			$data['bank_name'] = $result['bank'];  //开户行名称
			$data['card_name'] = $result['cardName'];  //银行卡名称
			$data['card_type'] = $result['cardType'];  //银行卡类型
			$data['sex'] = $result['sex'];  //性别
			$data['province'] = $result['province'];  //省
			$data['city'] = $result['city'];  //市
			$data['prefecture'] = $result['prefecture'];  //区县
			$data['birthday'] = $result['birthday'];  //生日
			$data['addr_code'] = $result['addrCode']; //地区代码
		
			// 上传文件
			$cardFaceInfo = $this->uploadImg($cardFace);
			$cardBackInfo = $this->uploadImg($cardBack);
			$bankFaceInfo = $this->uploadImg($bankFace);
	
			//判断文件是否都上传成功
			if($cardFaceInfo['msg'] == 'success' && $cardBackInfo['msg'] == 'success' && $bankFaceInfo['msg'] == 'success'){
				//数据库中储存文件路径
				$data['card_face_img'] = "user/card/".$cardFaceInfo['data'];
				$data['card_back_img'] = "user/card/".$cardBackInfo['data'];
				$data['bank_face_img'] = "user/card/".$bankFaceInfo['data'];
				
				//标明用户通过实名认证
				$data['is_certificate'] = "1";
				$users = model('Users');
				if($users->save($data,['series'=>$data['series']])){
					my_json_encode($status,$msg);
				}else{
					my_json_encode(9,'数据储存失败');
				}
			}else{
				my_json_encode(10,$bankFaceInfo['data']);
			}
		}
	}


	//上传实名认证照片
	public function uploadImg($file){
        $upload = new Upload();
        // 验证文件是否合法
        $result = $upload->check($file);
        if($result == 'success'){
        	//文件保存位置
            $path = APP_PATH.'/mkapi/public/upload/user/card/';
            $result = $upload->uploadOne($file,$path);
            return $result;
        }else{
            return array('msg'=>'error','data'=>$result);
        }
	}

	//实名认证请求
	public function certificateRequest($accountNo,$idCard,$name){
		$host = "https://tbank.market.alicloudapi.com";
	    $path = "/bankCheck";
	    $method = "GET";
	    $appcode = "16ca317574514528952b05eaedc353f0";
	    $headers = array();
	    array_push($headers, "Authorization:APPCODE " . $appcode);
	    $querys = "accountNo=$accountNo&idCard=$idCard&name=$name";
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
}