<?php
/**
 * Created by PhpStorm.
 * User: 淘气
 * Date: 2017/02/04
 * Time: 23:32
 */
header("Content-type: text/html; charset=utf-8");
//====================配置商户的宝付接口授权参数============================
$path = $_SERVER['DOCUMENT_ROOT']."/Spider-email-api/";
$rsapath = $path."library/rsa/";	//证书路径
require_once($path."library/utils/HttpCurl.php");
require_once($path."library/utils/BaofooUtils.php");
require_once($path."library/utils/Log.php");
require_once($path."library/utils/BFRSA.php");
require_once($path."library/utils/HttpClient.php");


//默认编码格式//
$char_set="UTF-8";
//商户私钥   ---请修改为自己的//
$pfxpath=$rsapath."8000013189_pri.pfx";
//商户私钥密码 ---请修改为自己的//
$pfx_pwd="217526";
//公钥 ---请修改为自己的//
$cerpath=$rsapath."bfkey_8000013189.cer";
//终端号 ---请修改为自己的//
$terminal_id="8000013189";
//商户号 ---请修改为自己的//
$member_id="8000013189";
//数据类型////json/xml
$data_type="json";


//======创建任务======
//测试地址
$taskCreateUrl="http://test.xinyan.com/gateway-data/email/v1/task/create";


//======状态查询======
//测试地址
$taskStatusUrl="http://test.xinyan.com/gateway-data/email/v1/task/status";

//======验证码输入======
//测试地址
$taskinputUrl="http://test.xinyan.com/gateway-data/email/v1/task/input";

//------------------------------------------------------------------------------

//测试地址
$billsUrl="http://test.xinyan.com/data/email/v2/bills/";

//测试地址
$shoppingUrl="http://test.xinyan.com/data/email/v2/bills/shopping/";


//测试地址
$installmentUrl="http://test.xinyan.com/data/email/v2/bills/installment/";











