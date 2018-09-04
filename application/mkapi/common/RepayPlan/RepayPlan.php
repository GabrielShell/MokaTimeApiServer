<?php
namespace app\mkapi\common\RepayPlan;

use think\Controller;
use app\mkapi\model\Users;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Bills;
use app\mkapi\model\Repay_plans;

//TODO 正式环境将继承类改成Common
class RepayPlan extends Controller{
    public function getPlan($dayCount,$repayAmount,$planType,$firstDayPlan){
        if($planType == 2){
            //资金过夜：当日还，次日刷
            $dayCount = $dayCount - 1;
        }
        //根据计划天数和还款金额得到还款序列
        $repaySequence = $this->randomFixSumArry($repayAmount,$dayCount,$repayAmount * 0.2);

        //还款金额取整
        foreach ($repaySequence as &$value) {
            if($value > 1000){
                $value = floor($value / 1000) * 1000;
            }
        }
        //将取整后的差额随机补在最后一笔还款
        $repaySequence[count($repaySequence) - 1] += ceil($repayAmount - array_sum($repaySequence));
        

        //每一笔还款，随机增减后，可以切分成1-3笔刷卡
        //TODO 随机增减还款金额
        $planSequence = [];
        foreach($repaySequence as $key => $repay){
            $paySequence = [];
            $randNum = mt_rand(0,100);
            if($randNum < 70){
            //     //生成1笔刷卡
            //     $paySequence = [$repay];
            // }elseif($randNum >= 30 && $randNum < 80){
                //生成2笔刷卡
                $paySequence = $this->randomFixSumArry($repay,2,$repay * 0.5);
            }else{
                //生成3笔刷卡
                $paySequence = $this->randomFixSumArry($repay,3,$repay * 0.5);
            }

            $planSequence[$key][] = ['type' => 'repay' , 'amount' =>ceil($repay)];
            foreach($paySequence as $pay){
                if($planType == 2){
                    $planKey = $key + 1;
                }else{
                    $planKey = $key;
                }
                $planSequence[$planKey][] = [
                    'type' => 'pay',
                    'amount' => ceil($pay)
                ];
            }
            

            
        }

        //如果需要替换第一天计划，统计提供的第一天计划的还刷总额和现在生成的还刷总额的差额
        if($firstDayPlan != null){
            $_repayTotal = 0; //替换计划还款总额
            $_payTotal = 0;
            $repayTotal = 0;  //现在生产计划还款总额
            $payTotal = 0;
            foreach($firstDayPlan as $planItem){
                if($planItem['type'] == 'repay'){
                    $_repayTotal += $planItem['amount'];
                }else{
                    $_payTotal += $planItem['amount'];
                }
            }
            foreach($planSequence[0] as $planItem){
                if($planItem['type'] == 'repay'){
                    $repayTotal += $planItem['amount'];
                }else{
                    $payTotal += $planItem['amount'];
                }
            }


            $repayDiff = $_repayTotal - $repayTotal;
            $payDiff = $_payTotal - $payTotal;

            //第二天的计划的还款和刷卡加上差
            $repayCount = 0;
            $payCount = 0;
            
            //统计第二天还款、刷卡笔数
            foreach($planSequence[1] as $planItem){
                if($planItem['type'] == 'repay'){
                    $repayCount++;
                }else{
                    $payCount++;
                }
            }

            if(($repayDiff != 0 && $repayCount == 0) || ($payDiff != 0 && $payCount == 0)){
                my_json_encode(1011,'修改计划失败');
            }

            if($repayDiff != 0){ //将还款差额应用到第二天的还款计划中
                foreach($planSequence[1] as &$planItem){
                    if($planItem['type'] == 'repay'){
                        $planItem['amount'] = $planItem['amount'] - $repayDiff;
                        break;
                    }
                }
            }
            if($payDiff != 0){
                foreach($planSequence[1] as &$planItem){
                    if($planItem['type'] == 'pay'){
                        $planItem['amount'] = $planItem['amount'] - $payDiff;
                        break;
                    }
                }
            }

            $planSequence[0] = $firstDayPlan;


        }

        //TODO 差额补上
        
        return $planSequence;

    }

    /**
     * 红包算法，平均随机切分算法
     * 
     * @param int $total 待划分的数字
     * @param int $div 分成的份数
     * @param int $area 各份数间允许的最大差值
     * @return array
     */
    private function randomFixSumArry($total,$div,$area = 50){
        if($div == 0)
            return [];
        $average = floor($total / $div);
        $_floor_total = $average * $div;
        $offset = $total - $_floor_total;
        $sum = 0;
        $result = array_fill( 1, $div, 0 );

        for( $i = 1; $i < $div; $i++ ){
            //根据已产生的随机数情况，调整新随机数范围，以保证各份间差值在指定范围内
            if( $sum > 0 ){
                $max = 0;
                $min = 0 - round( $area / 2 );
            }elseif( $sum < 0 ){
                $min = 0;
                $max = round( $area / 2 );
            }else{
                $max = round( $area / 2 );
                $min = 0 - round( $area / 2 );
            }

            //产生各份的份额
            $random = rand( $min, $max );
            $sum += $random;
            $result[$i] = $average + $random;
        }

        //最后一份的份额由前面的结果决定，以保证各份的总和为指定值
        $result[$div] = $average - $sum + $offset;
        foreach( $result as $temp ){
            $data[]=$temp;
        }
//        var_dump($data);exit;
        return $data;
    }

}
//FIXME dfa