<?php
namespace app\mkapi\controller;

use think\Controller;
use think\Request;
use app\mkapi\model\Users;
use app\mkapi\model\Repay_plans;

/**
 * 统计数据控制器
 */

class StatData extends Common{

    /**
     * 首页统计数据
     * 一.7天内应还金额
     */
    public function homePageStatData(Request $request){
        $userSeries = $request->post('series');
        if(empty($userSeries))
            return ['status'=>2,'msg'=>'series不能为空'];
        $userId = Users::where(['series'=>$userSeries])->value('id');

        $dayList = [date('Y-m-d')];
        for($i = 1;$i <= 6;$i++){
            $dayList[] = date('Y-m-d',strtotime('+' . $i.' days'));
        }

        $SevenSum = Repay_plans::where('user_id',$userId)->where('action','repay')->where('action_date','in',implode(',',$dayList))->sum('amount');
        my_json_encode(0,'',['SevenDaysRepay'=>$SevenSum]);
    }
    
}
