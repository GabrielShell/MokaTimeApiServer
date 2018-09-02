
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

/**
 * 网银账单状态查询接口 [POST]
 * @param tradeNo 新颜订单号
 */
Route::post('api/v1/bills/bank/task-status','mkapi/XinyanBillsApi/cyberBankQueryTaskStatus');

/**
 * 网银账单查询验证码输入接口[POST]
 * @param tradeNo 新颜订单号
 * @param input 验证码
 */
Route::post('api/v1/bills/bank/task-input','mkapi/XinyanBillsApi/cyberBankQueryTaskInput');

/**
 * 网银查询银行卡卡号和账单信息[POST]
 * @param tradeNo 新颜订单号
 */
Route::post('api/v1/bills/bank/bills','mkapi/XinyanBillsApi/cyberBankQueryBills');

/**
 * 网银查询银行卡所有信息[POST]
 * @param tradeNo 新颜订单号
 */
Route::post('api/v1/bills/bank/cards','mkapi/XinyanBillsApi/cyberBankQueryCards');

/**
 * 拉卡拉开通商户/支付交易接口[post]
 * @param channel_id 渠道号
 * @param channel_code 渠道码
 * @param pay_rate 费率
 * @param amount 订单金额
 * @param callbackUrl SDK回调地址
 * @param timestamp 报文时间戳
 * @param expriredtime 报文截止时间戳
 */
Route::post('api/v1/open/merchant/','mkapi/Lakalaapi/PaymentTrade');


// +----------------------------------------------------------------------
// | 信用卡接口
// +----------------------------------------------------------------------
/**
 * 查询用户信用卡列表[POST]
 */
Route::post('api/v1/card/userCardList','mkapi/Card/userCardList');

/**
 * 标记已还接口[POST]
 * @param series 用户series
 * @param card_id 卡片ID
 */
Route::post('api/v1/card/markRepaid','mkapi/Card/markRepaid');

/**
 * 信用卡详情数据接口
 * @param series 用户series
 * @param card_id 信用卡ID
 */
Route::post('api/v1/card/cardDetail','mkapi/Card/cardDetail');


/**
 * 获取现有还款计划
 * @param series 用户series
 * @param card_id 信用卡ID
 */
Route::post('api/v1/card/getRepayPlan','mkapi/Card/getRepayPlan');

/**
 * 获取新还款计划
 * @param series 用户series
 * @param card_id 信用卡ID
 */
Route::post('api/v1/card/getNewRepayPlan','mkapi/Card/getNewRepayPlan');


/**
 * 获取信用卡可用计划日期接口
 * @param int card_id 信用卡ID
 */
Route::post('api/v1/card/availablePlanDate','mkapi/Card/availablePlanDate');


/**
 * 还款计划列表接口
 * @param string type 类型 "today" 今日计划; "future" 未来计划; "past" 过去计划
 * @param string series 用户series
 */
Route::post('api/v1/card/repayPlanList','mkapi/Card/repayPlanList');

// +----------------------------------------------------------------------
// | 客户端版本管理接口
// +----------------------------------------------------------------------
/**
 * 查询客户端最新版本号[POST]
 * @param os 客户端操作系统,'a'=Android，'i'=iOS
 */
Route::post('api/v1/version/getLatestVersion','mkapi/Version/getLatestVersion');


// +----------------------------------------------------------------------
// | 统计数据接口
// +----------------------------------------------------------------------
/**
 * 首页统计数据接口[POST]
 * @param series 用户series
 */
Route::post('api/v1/stat/home','mkapi/StatData/homePageStatData');

