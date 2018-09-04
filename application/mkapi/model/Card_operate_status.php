<?php
namespace app\mkapi\model;
use think\Model;

class Card_operate_status extends Model{

    //获取实例
    public static function getInst($userId,$cardId,$billMonth){
        $status = self::where('credit_card_id',$cardId)
        ->where('bill_month',$billMonth)
        ->select();
        if(!$status){
            //如果没有状态，则创建状态
            $status = new self;
            $status->user_id = $userId;
            $status->credit_card_id = $cardId;
            $status->bill_month = $billMonth;
            $status->repaid = 0;
            $status->paid = 0;
            $status->plan_day_list = "";
            $status->plan_repay_amount = 0;
            $status->plan_type = 1;
        }else{
            $status = $status[0];
        }
        return $status;
    }
}
