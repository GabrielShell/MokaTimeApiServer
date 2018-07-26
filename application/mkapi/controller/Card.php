<?php
namespace app\mkapi\controller;
use think\Request;
use think\Log;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Users;
use app\mkapi\model\Bills;

/**
 * 信用卡API
 */
class Card extends Common{
    public function userCardList(Request $request){
        $user_series = $request->post('series');
        $userId = Users::where(['series'=>$user_series])->value('id');
        $cards = Credit_cards::all(['user_id'=>$userId]);
        $resultData = [];
        foreach($cards as $card){
            //计算账单日还款日
            $bill_date = date('Y-m-'.$card->bill_date);
            $due_date_pure = substr($card->due_date,1,strlen($card->due_date) - 1);
            if(substr($card->due_date,0,1) == '-'){
                //还款日与账单日同月
                $due_date = date('Y-m-'.$due_date_pure);
            }else{
                //还款日与账单日不同月
                $due_date = date('Y-m-d',strtotime('+1 month',strtotime('Y-m-'.$due_date_pure)));
            }
            $billsDbResult = Bills::all(['credit_card_id'=>$card->id]);
            $billsResult = [];
            foreach($billsDbResult as $billDbResult){
                $billsResult [] = [
                    'origin_type' => $billDbResult->origin_type,
                    'bill_type' => $billDbResult->bill_type,
                    'repay_amount' => $billDbResult->repay_amount,
                    'bill_start_date' => $billDbResult->bill_start_date,
                    'bill_date' => $billDbResult->bill_date,
                    'payment_due_date' => $billDbResult->payment_due_date,
                    'new_balance' => $billDbResult->new_balance,
                    'min_payment' => $billDbResult->min_payment,
                    'point' => $billDbResult->point,
                    'create_time' => $billDbResult->create_time
                ];

            }

            $resultData[] = [
                'bank_name' => $card->bank_name,
                'name_on_card' => $card->name_on_card,
                'card_on_last4' => $card->card_no_last4,
                'card_no' => $card->card_no,
                'bill_date' => $bill_date,
                'due_date' => $due_date,
                'credit_limit' => $card->credit_limit,
                'balance' => $card->balance,
                'point' => $card->point,
                'bills' => $billsResult
            ];
        }
        $result = [
            'status' => 0,
            'msg' => '',
            'data' => $resultData
        ];
        return json_encode($result,JSON_UNESCAPED_UNICODE);

        
    }
}