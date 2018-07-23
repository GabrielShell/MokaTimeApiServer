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

