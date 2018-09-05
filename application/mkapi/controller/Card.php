<?php
namespace app\mkapi\controller;

use app\mkapi\common\RepayPlan\RepayPlan;
use app\mkapi\model\Bills;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Repay_plans;
use app\mkapi\model\Shopping_records;
use app\mkapi\model\Users;
use app\mkapi\model\Card_operate_status as CardStatus;
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
	        $status = CardStatus::getInst($userId,$billDbResult->credit_card_id,$billDbResult->bill_month);
                $repaid = $status->repaid >= $billDbResult->new_balance ? 1 : 0;
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
                    'repaid' => $repaid,
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

        $status = CardStatus::getInst($userId,$bill->credit_card_id,$bill->bill_month);
        $status->repaid = $bill->new_balance;
        $status->save();

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

}
