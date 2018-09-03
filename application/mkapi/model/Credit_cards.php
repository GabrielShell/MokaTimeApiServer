<?php
namespace app\mkapi\model;
use think\Model;

class Credit_cards extends Model{

    /**
     * 还款日和账单日是否同月
     */
    public function isBillDueSameMonth(){

        if (substr($this->due_date, 0, 1) == '-') {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 还款日是一月中的第几天
     */
    public function getPureDueDate(){
        return substr($this->due_date, 1, strlen($this->due_date) - 1);
    }

    /**
     * 增减月份逻辑（考虑到某月31日时strtotime('+1 month') 会获取到下下个月的情况）
     */
    public static function monthChange($nowTime,$plus=1){
	$plusOneMonth = date('Y-m',strtotime(date('Y-',$nowTime).(date('m',$nowTime) + $plus)));
	if(date('m',$nowTime) + $plus > 12){
		$plusOneMonth = (date('Y',$nowTime) + 1).'-'.(date('m',$nowTime) + $plus - 12);
	}else if(date('m',$nowTime) + $plus < 1){
		$plusOneMonth = (date('Y',$nowTime) - 1).'-'.(date('m',$nowTime) + $plus + 12);
	}
	return $plusOneMonth;
    }

    /**
     * 获取某月还款日逻辑（考虑到还款日可能大于大约最大日期的情况）
     */
    public static function getThisMonthDueDate($due_date_pure,$nowTime){
	if($due_date_pure > date('t',$nowTime)){
		//如果还款日大于此月最大日期
		$due_date =  date('Y-m-t',$nowTime);
	}else{
		$due_date =  date('Y-m-',$nowTime). $due_date_pure;
	}
	return $due_date;
    }

    /**
     * 获取当前月账单日和还款日
     * @return array [string BillDate,string DueDate] 如 ["2018-08-07","2018-08-25"]
     */
    public function getThisBillDateAndDueDate(){
        $due_date_pure = $this->getPureDueDate();
	$plusOneMonth = self::monthChange(time());
        if ($this->isBillDueSameMonth()) {
            //还款日与账单日同月
            if(date('d') >= $due_date_pure){
                $bill_date = $plusOneMonth.'-'. $this->bill_date;
		$due_date = self::getThisMonthDueDate($due_date_pure,strtotime($plusOneMonth));
            }else{
                $bill_date = date('Y-m-' . $this->bill_date);
                $due_date = self::getThisMonthDueDate($due_date_pure,time());
            }
        } else {
            //还款日与账单日不同月
            $_bill_date = date('Y-m-' . $this->bill_date);
	    $_due_date =  self::getThisMonthDueDate($due_date_pure,time());
            if(time() >= strtotime($_due_date)){
                $bill_date = $_bill_date;
		$due_date =  self::getThisMonthDueDate($due_date_pure,strtotime($plusOneMonth));
            }else{
                $bill_date = date('Y-m-d',strtotime('-1 month',strtotime($_bill_date)));
                $due_date = $_due_date;
            }
        }
        return [$bill_date,$due_date];
    }

    /**
     * 获取最新账单实例
     */
    public function getNewestBill(){
                $billInst = Bills::where('credit_card_id',$this->id)
                ->where('bill_type','DONE')
                ->order('bill_month desc')
                ->select();
                if(!$billInst){
                    return false;
                }
                return $billInst[0];
    }
}
