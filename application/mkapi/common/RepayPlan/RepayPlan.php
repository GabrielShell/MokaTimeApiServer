<?php
namespace app\mkapi\common\RepayPlan;

use think\Controller;
use app\mkapi\model\Users;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Bills;
use app\mkapi\model\Repay_plans;

//TODO 正式环境将继承类改成Common
class RepayPlan extends Controller{
    public function getPlan($dayCount,$repayAmount){
        //TODO 根据计划日期得到还款序列

        //TODO 每一笔还款，随机增减后，可以切分成1-3笔刷卡

        //TODO 差额补上
        

    }

    /**
     * 红包算法，平均随机切分算法
     */
    private function randomFixSumArry($total,$div){
        if($div == 0)
            return [];
        $total = $total; //待划分的数字
        $div = $div; //分成的份数
        $area = 50; //各份数间允许的最大差值
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