<?php
namespace app\mkapi\controller;

use app\mkapi\common\RepayPlan\RepayPlan;
use app\mkapi\model\Bills;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Repay_plans;
use app\mkapi\model\Shopping_records;
use app\mkapi\model\Users;
use think\Request;

/**
 * 信用卡API
 */
class Card extends Common
{
    public function userCardList(Request $request)
    {
        $user_series = $request->post('series');
        $userId = Users::where(['series' => $user_series])->value('id');
        $cards = Credit_cards::all(['user_id' => $userId]);
        $resultData = [];
        foreach ($cards as $card) {
            //计算账单日还款日
            $billDueRes = $card->getThisBillDateAndDueDate();
            $bill_date = $billDueRes[0];
            $due_date = $billDueRes[1];
            // if(date('d') >= $due_date_pure){
            //     $bill_date = date('Y-m-' . $card->bill_date,strtotime('+1 month'));

            //     if (substr($card->due_date, 0, 1) == '-') {
            //         //还款日与账单日同月
            //         $due_date = date('Y-m-' . $due_date_pure,strtotime('+1 month'));
            //     } else {
            //         //还款日与账单日不同月
            //         $due_date = date('Y-m-d', strtotime('+2 month', strtotime(date('Y-m-' . $due_date_pure))));
            //     }
            // }else{
            //     $bill_date = date('Y-m-' . $card->bill_date);

            //     if (substr($card->due_date, 0, 1) == '-') {
            //         //还款日与账单日同月
            //         $due_date = date('Y-m-' . $due_date_pure);
            //     } else {
            //         //还款日与账单日不同月
            //         $due_date = date('Y-m-d', strtotime('+1 month', strtotime(date('Y-m-' . $due_date_pure))));
            //     }
            // }
            $billsDbResult = Bills::all(['credit_card_id' => $card->id]);
            $billsResult = [];
            foreach ($billsDbResult as $billDbResult) {
                $billsResult[] = [
                    'bill_id' => $billDbResult->id,
                    'origin_type' => $billDbResult->origin_type,
                    'bill_type' => $billDbResult->bill_type,
                    'repay_amount' => $billDbResult->repay_amount,
                    'bill_start_date' => $billDbResult->bill_start_date,
                    'bill_date' => $billDbResult->bill_date,
                    'payment_due_date' => $billDbResult->payment_due_date,
                    'new_balance' => $billDbResult->new_balance,
                    'min_payment' => $billDbResult->min_payment,
                    'point' => $billDbResult->point,
                    'repaid' => $billDbResult->repaid,
                    'create_time' => $billDbResult->create_time,
                ];

            }

            $resultData[] = [
                'card_id' => $card->id,
                'bank_name' => $card->bank_name,
                'name_on_card' => $card->name_on_card,
                'card_on_last4' => $card->card_no_last4,
                'card_no' => $card->card_no,
                'bill_date' => $bill_date,
                'due_date' => $due_date,
                'credit_limit' => $card->credit_limit,
                'balance' => $card->balance,
                'point' => $card->point,
                'import_time' => $card->import_time,
                'bills' => $billsResult,
            ];
        }
        $result = [
            'status' => 0,
            'msg' => '',
            'data' => $resultData,
        ];
        return $result;

    }

    /**
     * 标记已还接口
     */
    public function markRepaid(Request $request)
    {
        $billId = $request->post('bill_id');
        if (empty($billId)) {
            return ['status' => 2, 'msg' => 'bill_id不能为空'];
        }

        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            return ['status' => 2, 'msg' => 'series不能为空'];
        }

        $userId = Users::where(['series' => $userSeries])->value('id');

        $bill = Bills::get(['user_id' => $userId, 'id' => $billId]);
        if (!$bill) {
            return ['status' => 1, 'msg' => '该用户没有指定ID的账单'];
        }

        $bill->repaid = 1;
        $bill->save();
        return ['status' => 0, 'msg' => '成功标记已还'];
    }

    /**
     * 信用卡详情数据接口
     * @param series 用户series
     * @param card_id 信用卡ID
     */
    public function cardDetail(Request $request)
    {
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $cardId = $request->post('card_id');
        if (empty($cardId)) {
            my_json_encode(2, 'card_id不能为空');
        }

        $cardDbInstance = Credit_cards::get($cardId);
        if (!$cardDbInstance) {
            my_json_encode(3, '该用户下找不到对应ID的信用卡');
        }

        $cardDetailResult = [];

        //查询账单和消费记录信息
        $billsDbResult = Bills::where('user_id', $userId)
            ->where('credit_card_id', $cardId)
            ->order('bill_month', 'desc')
            ->select();
        $billsResult = [];
        foreach ($billsDbResult as $billDbResult) {
            $shoppingRecordsDbInstance = Shopping_records::where('user_id', $userId)
                ->where('bill_id', $billDbResult->id)
                ->order('id', 'asc')
                ->select();
            $shoppingRecords = [];
            foreach ($shoppingRecordsDbInstance as $srDbRecord) {
                $shoppingRecords[] = [
                    'amount_money' => $srDbRecord->amount_money,
                    'trans_date' => $srDbRecord->trans_date,
                    'trans_type' => $srDbRecord->trans_type,
                    'currency_type' => $srDbRecord->currency_type,
                    'des' => $srDbRecord->description,
                ];
            }

            $billsResult[] = [
                'bill_id' => $billDbResult->id,
                'origin_type' => $billDbResult->origin_type,
                'bill_type' => $billDbResult->bill_type,
                'repay_amount' => $billDbResult->repay_amount,
                'bill_start_date' => $billDbResult->bill_start_date,
                'bill_date' => $billDbResult->bill_date,
                'payment_due_date' => $billDbResult->payment_due_date,
                'new_balance' => $billDbResult->new_balance,
                'min_payment' => $billDbResult->min_payment,
                'point' => $billDbResult->point,
                'repaid' => $billDbResult->repaid,
                'create_time' => $billDbResult->create_time,
                'shopping_records' => $shoppingRecords,
            ];
        }

        //查询还款计划信息
        $repayPlansDbResult = Repay_plans::where('user_id', $userId)
            ->where('credit_card_id', $cardId)
            ->order('bill_month desc,sort asc')
            ->select();
        $repayPlansResult = [];
        $repayPlansTmpResult = [];
        foreach ($repayPlansDbResult as $rpDbResult) {
            $repayPlansTmpResult[date('Y-m', strtotime($rpDbResult->bill_month))][] = [
                'plan_id' => $rpDbResult->id,
                'sort' => $rpDbResult->sort,
                'action' => $rpDbResult->action,
                'amount' => $rpDbResult->amount,
                'action_date' => $rpDbResult->action_date,
            ];
        }

        //改变格式
        foreach ($repayPlansTmpResult as $bill_month => $rpMonthPlan) {
            $repayPlansResult[] = [
                'month' => $bill_month,
                'plans' => $rpMonthPlan,
            ];
        }

        //示例数据
        $repayPlansResult = [

            [
                "month" => "2018-07",
                "plans" => [

                    [
                        'plan_id' => 5,
                        'sort' => 1,
                        'action' => 'repay',
                        'amount' => '1002',
                        'action_date' => '2018-07-03',
                    ],
                    [
                        'plan_id' => 6,
                        'sort' => 2,
                        'action' => 'pay',
                        'amount' => '1002',
                        'action_date' => '2018-07-03',
                    ],
                    [
                        'plan_id' => 7,
                        'sort' => 3,
                        'action' => 'repay',
                        'amount' => '998',
                        'action_date' => '2018-07-04',
                    ],
                    [
                        'plan_id' => 8,
                        'sort' => 4,
                        'action' => 'pay',
                        'amount' => '998',
                        'action_date' => '2018-07-04',
                    ],
                ],
            ],
            [
                "month" => '2018-06',
                "plans" => [

                    [
                        'plan_id' => 1,
                        'sort' => 1,
                        'action' => 'repay',
                        'amount' => '1002',
                        'action_date' => '2018-06-03',
                    ],
                    [
                        'plan_id' => 2,
                        'sort' => 2,
                        'action' => 'pay',
                        'amount' => '1002',
                        'action_date' => '2018-06-03',
                    ],
                    [
                        'plan_id' => 3,
                        'sort' => 3,
                        'action' => 'repay',
                        'amount' => '998',
                        'action_date' => '2018-06-04',
                    ],
                    [
                        'plan_id' => 4,
                        'sort' => 4,
                        'action' => 'pay',
                        'amount' => '998',
                        'action_date' => '2018-06-04',
                    ],
                ],
            ],
        ];
        $cardDetailResult['bills'] = $billsResult;
        $cardDetailResult['repay_plan'] = $repayPlansResult;
        my_json_encode(0, '', $cardDetailResult);
    }

    /**
     * 获取现有还款计划接口
     */
    public function getRepayPlan(Request $request)
    {
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $cardId = $request->post('card_id');
        if (empty($cardId)) {
            my_json_encode(2, 'card_id不能为空');
        }

        $card = Credit_cards::where('id', $cardId)->where('user_id', $userId)->select();
        if (!$card) {
            my_json_encode(3, '该用户没有指定ID的信用卡');
        }

        $billDueRes = $card[0]->getThisBillDateAndDueDate();
        $billMonth = date('Ym', strtotime($billDueRes[0]));
        $repayPlanDbInstArr = Repay_plans::where('credit_card_id', $cardId)
        ->where('bill_month', $billMonth)
        ->order('sort asc')
        ->select();

        $planData = [];
        foreach ($repayPlanDbInstArr as $repayPlanDbInst) {
            $planData[$repayPlanDbInst->action_date][] = [
                'id' => $repayPlanDbInst->id,
                'type' => $repayPlanDbInst->action,
                'amount' => $repayPlanDbInst->amount
            ];

        }

        $planResult = [];
        foreach($planData as $actionDate => $planList){
            $planResult[] = [
                'date' => $actionDate,
                'plan' => $planList
            ];
        }

        $data = ['plan' => $planResult];
        my_json_encode(0, '', $data);

    }

    /**
     * 获取新还款计划接口
     * @param int cardId 卡片ID
     * @param string dayList 日期列表,英文逗号分隔，如"2018-07-03,2018-03-04"
     * @param int repayAmount 还款金额，只接受整数
     * @param int planType 规划模式 1=资金不过夜：当日还，当日刷; 2=资金过夜：当日还，次日刷
     */
    public function getNewRepayPlan(Request $request)
    {
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $cardId = $request->post('card_id');
        if (empty($cardId)) {
            my_json_encode(2, 'card_id不能为空');
        }

        $card = Credit_cards::where('id', $cardId)->where('user_id', $userId)->select();
        if (!$card) {
            my_json_encode(3, '该用户没有指定ID的信用卡');
        }

        $dayListStr = $request->post('dayList');
        if (empty($dayListStr)) {
            my_json_encode(4, 'dayList不能为空');
        }
        $dayList = explode(',', $dayListStr);
        if (!is_array($dayList) || count($dayList) < 1) {
            my_json_encode(5, 'dayList格式不正确');
        }

        $repayAmount = $request->post('repayAmount');
        if (empty($repayAmount)) {
            my_json_encode(6, 'repayAmount不能为空');
        }
        if($repayAmount < 1000){
            my_json_encode(11, '还款金额大于1000才能提供计划');
        }
        if (!is_numeric($repayAmount)) {
            my_json_encode(8, 'repayAmount格式不正确');
        } else if (strpos($repayAmount, '.')) {
            my_json_encode(9, 'repayAmount只接受整数');
        }

        $planType = $request->post('planType');
        if (empty($planType)) {
            my_json_encode(7, 'planType不能为空');
        }
        if ($planType != 1 && $planType != 2) {
            my_json_encode(10, 'planType格式错误');
        }

        // //得到本期账单
        // $bill = Bills::where('credit_card_id', $cardId)->where('bill_type', 'DONE')->order('bill_month', 'desc')->limit(1)->select();
        // if (!$bill) {
        //     my_json_encode(4, '请导入账单');
        // }
        // //验证是否本期账单
        // $bill_date = $bill[0]->bill_date;
        // $payment_due_date = $bill[0]->payment_due_date;

        // //TODO 此处需要换成time();
        // $nowTime = time();
        // // $nowTime = strtotime('2018-07-25 00:00:00');
        // if (!($nowTime >= strtotime($bill_date) && $nowTime < strtotime($payment_due_date))) {
        //     //如果不是本期账单
        //     my_json_encode(4, '未找到本期账单，请更新账单或重新导入账单');
        // }
        $billDueRes = $card[0]->getThisBillDateAndDueDate();
        $billMonth = date('Ym', strtotime($billDueRes[0]));

        //根据计划天数得到计划日期列表
        $dayCount = count($dayList);

        $repayPlan = new RepayPlan;
        //得到计划
        $plan = $repayPlan->getPlan($dayCount, $repayAmount, $planType);

        //删除数据库中已存在的计划
        // $bill_month = $bill[0]->bill_month;
        Repay_plans::where('credit_card_id', $cardId)->where('bill_month', $billMonth)->delete();
        //将计划与卡片还款日期关联,同时储存到数据库
        $resultPlan = [];
        $sort = 0;
        foreach ($plan as $key => $dailyPlan) {
            $resultPlan[$key] = [
                'date' => $dayList[$key],
                'plan' => $dailyPlan,
            ];
            foreach ($dailyPlan as $action) {
                $repayPlanDbInst = new Repay_plans;
                $repayPlanDbInst->user_id = $userId;
                $repayPlanDbInst->credit_card_id = $cardId;
                $repayPlanDbInst->sort = $sort;
                $repayPlanDbInst->bill_month = $billMonth;
                $repayPlanDbInst->action = $action['type'];
                $repayPlanDbInst->amount = $action['amount'];
                $repayPlanDbInst->action_date = $dayList[$key];
                $repayPlanDbInst->save();
                $sort++;
            }
        }

        $data = ['plan' => $resultPlan];
        my_json_encode(0, '', $data);
    }

    /**
     * 获取信用卡可用计划日期接口
     * @param int card_id 信用卡ID
     */
    public function availablePlanDate(Request $request)
    {
        $cardId = $request->post('card_id');

        $card = Credit_cards::get($cardId);
        $billDueRes = $card->getThisBillDateAndDueDate();
        // $billDate = $billDueRes[0];
        $dueDate = $billDueRes[1];

        if (date('H') <= 16) {
            $nowDateTime = new \DateTime(date('Y-m-d 00:00:00'));
        } else {
            $nowDateTime = new \DateTime(date('Y-m-d 00:00:00', strtotime('+1 day')));
        }
        $dueDateTime = new \DateTime($dueDate . ' 00:00:00');
        $interval = $nowDateTime->diff($dueDateTime);
        $dayCount = $interval->format('%R%a');
        if ($dayCount < 1) {
            my_json_encode(1, '距离还款日不足1天，不能生成计划');
        }

        $dayList = [];
        $doDate = $nowDateTime;
        do {
            $dayList[] = $doDate->format('Y-m-d');
            $doDate->add(new \DateInterval('P1D'));
        } while ($doDate->format('Y-m-d') != $dueDateTime->format('Y-m-d'));
        my_json_encode(0, '', $dayList);
    }

    /**
     * 还款计划列表接口
     * @param string type 类型 "today" 今日计划; "future" 未来计划; "past" 过去计划
     */
    public function repayPlanList(Request $request)
    {
        $type = $request->post('type');
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $nowTime = strtotime('2018-09-01');
        if ($type == 'future') {
            //未来计划
            $plans = Repay_plans::where('user_id',$userId)
                ->where('action_date', '>', date('Y-m-d', $nowTime))
                ->order('credit_card_id desc,sort asc')
                ->select();

        } else if ($type == 'past') {
            //过去计划
            $plans = Repay_plans::where('user_id',$userId)
                ->where('action_date', '<', date('Y-m-d', $nowTime))
                ->order('credit_card_id desc,sort asc')
                ->select();

        } else {
            //今日计划
            $plans = Repay_plans::where('user_id',$userId)
                ->where('action_date', '=', date('Y-m-d', $nowTime))
                ->order('credit_card_id desc,sort asc')
                ->select();
        }
        $planResult = [];
        foreach($plans as $plan){
            $planResult[$plan->bill_month][$plan->credit_card_id][] = [
                'id' => $plan->id,
                'type'=>$plan->action,
                'amount'=>$plan->amount,
            ];
        }

        //改变响应格式
        $planData = [];
        foreach($planResult as $billMonth => $cardsPlan){
            $cardsPlanData = [];
            foreach($cardsPlan as $cardId => $planList){
                $cardInst = Credit_cards::get($cardId);
                $billInst = Bills::where('credit_card_id',$cardId)
                ->where('bill_type','DONE')
                ->order('bill_month desc')
                ->select();

                //获取本期账单金额
                $newBalance = $cardInst->credit_limit;
                if($billInst){
                    $newBalance = $billInst[0]->new_balance;
                }


                $cardsPlanData[] = [
                    'credit_card_id' => $cardId,
                    'bank_name' => $cardInst->bank_name,
                    'name_on_card' => $cardInst->name_on_card,
                    'card_no_last4' => $cardInst->card_no_last4,
                    'credit_limit' =>  $cardInst->credit_limit,
                    'new_balance' => $newBalance,
                    'plan' => $planList
                ];
            }
            $planData[] = [
                'bill_month' => $billMonth,
                'data' => $cardsPlanData
            ];
        }

        my_json_encode(0,'',$planData);
    }

    /**
     * 标记计划项已执行接口
     * @param int plan_id planID
     */
}
