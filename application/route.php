<?php
use think\Route;

Route::rule('hello/:name', function ($name) {
    return 'Hello,' . $name . '!';
});

// +----------------------------------------------------------------------
// | 摩卡时代API v1
// +----------------------------------------------------------------------
// | 2018/07/22
// +----------------------------------------------------------------------

// +----------------------------------------------------------------------
// | 新颜征信接口
// +----------------------------------------------------------------------

/**
 * 邮箱方式获取账单API预订单接口 [POST]
*/
Route::post('api/v1/bills/email/order-no','mkapi/XinyanBillsApi/getEmailOrderNo');

/**
 * 网银方式获取账单API预订单接口 [POST]
*/
Route::post('api/v1/bills/bank/order-no','mkapi/XinyanBillsApi/getBankOrderNo');

/**
 * 邮箱查询账单接口[POST]
* @param orderNo 预订单号
*/
Route::post('api/v1/bills/email/query-bills','mkapi/XinyanBillsApi/emailQueryBills');

/**
 * 查询支持银行列表 [POST]
 */
Route::post('api/v1/bills/bank/support-banks','mkapi/XinyanBillsApi/querySupportBanks');

/**
 * 查询银行登录配置信息 [POST]
 * @param bankcode 银行代码（支持银行列表中的bank_abbr），如交通银行是"COMM"
 * @param cardtype 银行卡类型 "CREDITCARD"或"DEBITCARD"
 */
Route::post('api/v1/bills/bank/config-login','mkapi/XinyanBillsApi/queryBankConfigLogin');

/**
 * 网银账单查询创建人物 [POST]
 * @param bank 银行代码（支持银行列表中的bank_abbr），如交通银行是"COMM"
 * @param account 账号
 * @param password 密码
 * @param login_type 登录信息接口返回的login_type
 * @param id_card 身份证号码
 * @param real_name 用户姓名
 * @param origin 交互方式;  2有验证码 ；3 无验证码 如不确定该银行登录是否有验证方式请填写2
 */
Route::post('api/v1/bills/bank/task-create','mkapi/XinyanBillsApi/cyberBankQueryTaskCreate');
