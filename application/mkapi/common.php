<?php 
/*
*系统辅助函数
*/
function httpHost($curlPost,$url){
	$curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
    $return_str = curl_exec($curl);
    curl_close($curl);
    return $return_str;
}

/**
  * 生成字母数字混合的订单号
  * @param int $type 订单类型
  * @param int $num 订单长度
  * @author: ZhaoYang
  */
function getNo($type=1, $num=8){
     $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
     switch ($type){
         case 1: $no = 'mk';break;
         case 2: $no = 'FR';break;
         case 3: $no = 'BK';break;
         case 4: $no = 'FR';break;
         case 5: $no = 'TP';break;
         default: $no = '';
     }
     $no .= date('ymdHis');
     for($i=0; $i < $num; $i++){
         $j = rand(0, 35);
         $no .= $str[$j];
     }
     return $no;
 }

 /**
  * 生成用户唯一id
  * @param int $type 订单类型
  * @param int $num 订单长度
  * @author: ZhaoYang
  */
function getUserId($num=6,$phone){
     $str = '0123456789';
  
     $no = 'mk'.$phone;
     for($i=0; $i < $num; $i++){
         $j = rand(0, 9);
         $no .= $str[$j];
     }
     return $no;
 }

   /**
   * 生成纯数字订单
   * 
   * 
   * @param int $num
   */
 function getNumNo($num=12){
     $no = '';
     for($i = 0; $i < $num; $i++){
         $no .= rand(0, 9);
     }
     return $no;
 }

  /**
   * 写日志文件
   * @param string $msg 日志内容
   * @param string $path 保存文件路径
   */
 function write_to_log($msg, $path='/log/'){
    $save_path = str_replace('\\', '/', APP_PATH. $path);
    $ym = date('Y_m');
    $save_path .= $ym . '/';
    if (!file_exists($save_path)) {
        mkdir($save_path);
    }
    $d = date('d');
    $save_path .= $d . '/';
    if (!file_exists($save_path)) {
       mkdir($save_path);
    }
    $date = date('YmdH');
    $file_name = $date;
    $file_path = $save_path . $file_name;
//     dump($file_path);
    $msg = date('H:i:s') . '  ' . $msg . PHP_EOL;
//     dump($msg);
    file_put_contents($file_path, $msg, FILE_APPEND);
}

 /**
   * 写日志文件
   * @param string $msg 日志内容
   * @param string $path 保存文件路径
   */
 function write_to_log1($msg, $path='/log/'){
    $save_path = str_replace('\\', '/', APP_PATH. $path);
    $ym = date('Y_m');
    $save_path .= $ym . '/';
    if (!file_exists($save_path)) {
        mkdir($save_path);
    }
    $d = date('d');
    $save_path .= $d . '/';
    if (!file_exists($save_path)) {
       mkdir($save_path);
    }
    $date = date('YmdH');
    $file_name = $date;
    $file_path = $save_path . $file_name;
//     dump($file_path);
    $msg = date('H:i:s') . '  ' . $msg . PHP_EOL;
//     dump($msg);
    file_put_contents($file_path, print_r($msg), FILE_APPEND);
}

 /**
  * 生成秘钥
  * @date: 2017年2月10日 下午2:03:45
  * @author: ZhaoYang
  */
 function getKey($num = 32){
     $str = 'abcdefghij0123456789klmnopqist0123456789uvwxyz0123456789';
     $no = '';
     for($i = 0; $i < $num; $i++){
         $j = rand(0, 55);
         $no .= $str[$j];
     }
     return $no;
 }


 /**
  * token验证统一响应模板
  * @date: 2017年2月10日 下午2:03:45
  * @author: ZhaoYang
  */
 function my_json_encode($status,$msg, $responseData=null){
    header('Content-type: text/json;charset=utf-8');
    echo json_encode(array(
        'status' => $status,
         'msg' => $msg,
         'data' => $responseData
    ), JSON_UNESCAPED_UNICODE);exit;
}

 