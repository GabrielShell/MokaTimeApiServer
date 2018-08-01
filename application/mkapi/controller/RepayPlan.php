<?php
namespace app\mkapi\controller;

use think\Controller;
use app\mkapi\model\Users;
use app\mkapi\model\Repay_plans;

//TODO 正式环境将继承类改成Common
class RepayPlan extends Controller{
    public function getPlan(Request $request){
        $userSeries = $request->post('series');
        $userId = Users::where(['series'=>$user_series])->value('id');

    }
}
//FIXME dfa