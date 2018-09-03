<?php
namespace app\mkapi\model;
use think\Model;

class Card_operate_status extends Model{

    //获取实例
    public static function getInst($cardId,$billMonth){
        $status = self::where('credit_card_id',$cardId)
        ->where('bill_month',$billMonth)
        ->select();
        if(!$status){
            //如果没有状态，则创建状态
            $status = new CardStatus;
            $status->user_id = $userId;
            $status->credit_card_id = $cardId;
            $status->bill_month = $billMonth;
            $status->repaid = 0;
            $status->paid = 0;
        }else{
            $status = $status[0];
        }
        return $status;
    }
}
