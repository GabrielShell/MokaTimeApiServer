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
     * 获取当前月账单日和还款日
     * @return array [string BillDate,string DueDate] 如 ["2018-08-07","2018-08-25"]
     */
    public function getThisBillDateAndDueDate(){

        $due_date_pure = $this->getPureDueDate();
        if ($this->isBillDueSameMonth()) {
            //还款日与账单日同月
            if(date('d') >= $due_date_pure){
                $bill_date = date('Y-m-' . $this->bill_date,strtotime('+1 month'));
                $due_date = date('Y-m-' . $due_date_pure,strtotime('+1 month'));
            }else{
                $bill_date = date('Y-m-' . $this->bill_date);
                $due_date = date('Y-m-' . $due_date_pure);
            }
        } else {
            //还款日与账单日不同月
            $_bill_date = date('Y-m-' . $this->bill_date);
            $_due_date = date('Y-m-' . $due_date_pure);
            if(time() >= strtotime($_due_date)){
                $bill_date = $_bill_date;
                $due_date = date('Y-m-d',strtotime('+1 month',strtotime($_due_date)));
            }else{
                $bill_date = date('Y-m-d',strtotime('-1 month',strtotime($_bill_date)));
                $due_date = $_due_date;
            }
        }
        return [$bill_date,$due_date];
    }
}
