<?php
namespace app\mkapi\controller;

use think\Controller;
use think\Request;
use app\mkapi\model\Users;

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

        my_json_encode(0,'',['7DaysRepay'=>1000]);
    }
    
}