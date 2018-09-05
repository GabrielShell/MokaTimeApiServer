<?php
namespace app\mkapi\controller;

use app\mkapi\common\RepayPlan\RepayPlan;
use app\mkapi\model\Bills;
use app\mkapi\model\Credit_cards;
use app\mkapi\model\Repay_plans;
use app\mkapi\model\Shopping_records;
use app\mkapi\model\Users;
use app\mkapi\model\Card_operate_status as CardStatus;
use think\Request;

/**
 * 还款计划API
 */
class Plan extends Common
{

    /**
     * 获取现有还款计划接口
     */
    public function getRepayPlan(Request $request)
    {
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $cardId = $request->post('card_id');
        if (empty($cardId)) {
            my_json_encode(2, 'card_id不能为空');
        }

        $card = Credit_cards::where('id', $cardId)->where('user_id', $userId)->select();
        if (!$card) {
            my_json_encode(3, '该用户没有指定ID的信用卡');
        }
        $card = $card[0];

        $billDueRes = $card->getThisBillDateAndDueDate();
        $billMonth = date('Ym', strtotime($billDueRes[0]));
        $repayPlanDbInstArr = Repay_plans::where('credit_card_id', $cardId)
        ->where('bill_month', $billMonth)
        ->order('sort asc')
        ->select();

        $planData = [];
        foreach ($repayPlanDbInstArr as $repayPlanDbInst) {
            $planData[$repayPlanDbInst->action_date][] = [
                'id' => $repayPlanDbInst->id,
                'type' => $repayPlanDbInst->action,
                'amount' => $repayPlanDbInst->amount,
                'finish_time' => $repayPlanDbInst->finish_time
            ];

        }

        $planResult = [];
        foreach($planData as $actionDate => $planList){
            $planResult[] = [
                'date' => $actionDate,
                'plan' => $planList
            ];
        }

        $newBalance = $card->credit_limit;
        $billInst = $card->getNewestBill();
        if($billInst !== false){
            $newBalance = $billInst->new_balance;
        }
        $billDueDateRes = $card->getThisBillDateAndDueDate();
        $status = CardStatus::getInst($userId,$cardId,$billMonth);

        //是否该卡在这个账单月份已完成还款
        $finish = 0;
        if($status->repaid >= $newBalance){
            $finish = 1;
        }
        $data = [
            'bank_name' => $card->bank_name,
            'name_on_card' => $card->name_on_card,
            'card_no_last4' => $card->card_no_last4,
            'bill_date' => $billDueDateRes[0],
            'due_date' => $billDueDateRes[1],
            'new_balance' => $newBalance,
            'repaid' => $status->repaid,
            'paid' => $status->paid,
            'finish' => $finish,
            'arg_daylist' => explode(',',$status->plan_day_list),
            'arg_repay_amount' => $status->plan_repay_amount,
            'arg_type' => $status->plan_type,
            'plan' => $planResult
        ];
        my_json_encode(0, '', $data);

    }

    /**
     * 获取新还款计划接口
     * @param int cardId 卡片ID
     * @param string dayList 日期列表,英文逗号分隔，如"2018-07-03,2018-03-04"
     * @param int repayAmount 还款金额，只接受整数
     * @param int planType 规划模式 1=资金不过夜：当日还，当日刷; 2=资金过夜：当日还，次日刷
     */
    public function getNewRepayPlan(Request $request)
    {
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $cardId = $request->post('card_id');
        if (empty($cardId)) {
            my_json_encode(2, 'card_id不能为空');
        }


        $dayListStr = $request->post('dayList');
        if (empty($dayListStr)) {
            my_json_encode(4, 'dayList不能为空');
        }

        $repayAmount = $request->post('repayAmount');
        if (empty($repayAmount)) {
            my_json_encode(6, 'repayAmount不能为空');
        }
        if($repayAmount < 1000){
            my_json_encode(11, '还款金额大于1000才能提供计划');
        }
        if (!is_numeric($repayAmount)) {
            my_json_encode(8, 'repayAmount格式不正确');
        } else if (strpos($repayAmount, '.')) {
            my_json_encode(9, 'repayAmount只接受整数');
        }

        $planType = $request->post('planType');
        if (empty($planType)) {
            my_json_encode(7, 'planType不能为空');
        }
        if ($planType != 1 && $planType != 2) {
            my_json_encode(10, 'planType格式错误');
        }

        self::genNewPlan($userSeries,$cardId,$dayListStr,$repayAmount,$planType);
        
    }

    /**
     * 生成新还款计划方法(单独分离方便重用)
     */
    public static function genNewPlan($userSeries,$cardId,$dayListStr,$repayAmount,$planType,$remainDate = null){

        $userId = Users::where(['series' => $userSeries])->value('id');

        $card = Credit_cards::where('id', $cardId)->where('user_id', $userId)->select();
        if (!$card) {
            my_json_encode(3, '该用户没有指定ID的信用卡');
        }
        $card = $card[0];


        $dayList = explode(',', $dayListStr);
        if (!is_array($dayList) || count($dayList) < 1) {
            my_json_encode(5, 'dayList格式不正确');
        }
        // //得到本期账单
        // $bill = Bills::where('credit_card_id', $cardId)->where('bill_type', 'DONE')->order('bill_month', 'desc')->limit(1)->select();
        // if (!$bill) {
        //     my_json_encode(4, '请导入账单');
        // }
        // //验证是否本期账单
        // $bill_date = $bill[0]->bill_date;
        // $payment_due_date = $bill[0]->payment_due_date;

        // //TODO 此处需要换成time();
        // $nowTime = time();
        // // $nowTime = strtotime('2018-07-25 00:00:00');
        // if (!($nowTime >= strtotime($bill_date) && $nowTime < strtotime($payment_due_date))) {
        //     //如果不是本期账单
        //     my_json_encode(4, '未找到本期账单，请更新账单或重新导入账单');
        // }
        $billDueRes = $card->getThisBillDateAndDueDate();
        $billMonth = date('Ym', strtotime($billDueRes[0]));

        //验证是否该卡在这个账单月份已完成还款
        $status = CardStatus::getInst($userId,$cardId,$billMonth);
        $billInst = $card->getNewestBill();
        if($billInst && $status->repaid >= $billInst->new_balance){
            my_json_encode(12,'该卡已完成还款，不需要生成计划');
        }

        //根据计划天数得到计划日期列表
        $dayCount = count($dayList);

        $repayPlan = new RepayPlan;

        //如果是保留第一天的计划
        $firstDayPlan = null;
        if($remainDate != null){
            $plans = Repay_plans::where('credit_card_id',$cardId)
            ->where('action_date',$remainDate)
            ->order('sort asc')
            ->select();
            foreach($plans as $planItem){
                $firstDayPlan[] = [
                    'type' => $planItem->action,
                    'amount' => $planItem->amount
                ];
            }
        }

        //得到计划
        $plan = $repayPlan->getPlan($dayCount, $repayAmount, $planType,$firstDayPlan);

        //删除数据库中已存在的计划(只删除今天和未来的计划)
        // $bill_month = $bill[0]->bill_month;
        Repay_plans::where('credit_card_id', $cardId)
        // ->where('action_date','>=',date('Y-m-d'))
        ->where('bill_month', $billMonth)
        // ->where('action_date','<>',$remainDate)
        ->delete();
        //将计划与卡片还款日期关联,同时储存到数据库
        $resultPlan = [];
        $sort = 0;
        foreach ($plan as $key => $dailyPlan) {
            foreach ($dailyPlan as &$action) {
                $repayPlanDbInst = new Repay_plans;
                $repayPlanDbInst->user_id = $userId;
                $repayPlanDbInst->credit_card_id = $cardId;
                $repayPlanDbInst->sort = $sort;
                $repayPlanDbInst->bill_month = $billMonth;
                $repayPlanDbInst->action = $action['type'];
                $repayPlanDbInst->amount = $action['amount'];
                $repayPlanDbInst->action_date = $dayList[$key];
                $repayPlanDbInst->save();
                $sort++;
                $action['id'] = $repayPlanDbInst->id;
                $action['finish_time'] = null;
            }
            $resultPlan[$key] = [
                'date' => $dayList[$key],
                'plan' => $dailyPlan,
            ];
        }

        //储存还款计划生成信息
        $status = CardStatus::getInst($userId,$cardId,$billMonth);
        $status->plan_day_list = $dayListStr;
        $status->plan_repay_amount = $repayAmount;
        $status->plan_type = $planType;
        $status->save();

        // $repayPlanDbInstArr = Repay_plans::where('credit_card_id', $cardId)
        // ->where('bill_month', $billMonth)
        // ->order('sort asc')
        // ->select();

        // $planData = [];
        // foreach ($repayPlanDbInstArr as $repayPlanDbInst) {
        //     $planData[$repayPlanDbInst->action_date][] = [
        //         'id' => $repayPlanDbInst->id,
        //         'type' => $repayPlanDbInst->action,
        //         'amount' => $repayPlanDbInst->amount,
        //         'finish_time' => $repayPlanDbInst->finish_time
        //     ];

        // }

        // $planResult = [];
        // foreach($planData as $actionDate => $planList){
        //     $planResult[] = [
        //         'date' => $actionDate,
        //         'plan' => $planList
        //     ];
        // }

        $newBalance = $card->credit_limit;
        $billInst = $card->getNewestBill();
        if($billInst !== false){
            $newBalance = $billInst->new_balance;
        }
        $billDueDateRes = $card->getThisBillDateAndDueDate();

        //是否该卡在这个账单月份已完成还款
        $finish = 0;
        if($status->repaid >= $newBalance){
            $finish = 1;
        }
        $data = [
            'bank_name' => $card->bank_name,
            'name_on_card' => $card->name_on_card,
            'card_no_last4' => $card->card_no_last4,
            'bill_date' => $billDueDateRes[0],
            'due_date' => $billDueDateRes[1],
            'new_balance' => $newBalance,
            'repaid' => $status->repaid,
            'paid' => $status->paid,
            'finish' => $finish,
            // 'plan' => $planResult
            'plan' => $resultPlan
        ];

        // $data = ['plan' => $resultPlan];
        my_json_encode(0, '', $data);
    }

    /**
     * 获取信用卡可用计划日期接口
     * @param int card_id 信用卡ID
     */
    public function availablePlanDate(Request $request)
    {
        $cardId = $request->post('card_id');

        $card = Credit_cards::get($cardId);
        $billDueRes = $card->getThisBillDateAndDueDate();
        $billDate = $billDueRes[0];
        $dueDate = $billDueRes[1];

        if (date('H') <= 16) {
            $nowDateTime = new \DateTime(date('Y-m-d 00:00:00'));
        } else {
            $nowDateTime = new \DateTime(date('Y-m-d 00:00:00', strtotime('+1 day')));
        }
        $billDateTime = new \DateTime($billDate . ' 23:59:59');
        $dueDateTime = new \DateTime($dueDate . ' 00:00:00');
        $interval = $nowDateTime->diff($dueDateTime);
        $dayCount = $interval->format('%R%a');
        if ($dayCount < 1) {
            my_json_encode(1, '距离还款日不足1天，不能生成计划');
        }

        $dayList = [];

        $billDateTimestamp = $billDateTime->getTimestamp();
        $nowDateTimestamp = $nowDateTime->getTimestamp();
        if($nowDateTimestamp > $billDateTimestamp){
            $doDate = $nowDateTime;
        }else{
            $doDate = $billDateTime;
        }
        do {
            $dayList[] = $doDate->format('Y-m-d');
            $doDate->add(new \DateInterval('P1D'));
        } while ($doDate->format('Y-m-d') != $dueDateTime->format('Y-m-d'));
        my_json_encode(0, '', $dayList);
    }

    /**
     * 还款计划列表接口
     * @param string type 类型 "today" 今日计划; "future" 未来计划; "past" 过去计划
     */
    public function repayPlanList(Request $request)
    {
        $type = $request->post('type');
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(2, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $nowTime = time();
        if ($type == 'future') {
            //未来计划
            $plans = Repay_plans::where('user_id',$userId)
                ->where('action_date', '>', date('Y-m-d', $nowTime))
                ->order('credit_card_id desc,sort asc')
                ->select();

        } else if ($type == 'past') {
            //过去计划
            $plans = Repay_plans::where('user_id',$userId)
                ->where('action_date', '<', date('Y-m-d', $nowTime))
                ->order('credit_card_id desc,sort asc')
                ->select();

        } else {
            //今日计划
            $plans = Repay_plans::where('user_id',$userId)
                ->where('action_date', '=', date('Y-m-d', $nowTime))
                ->order('credit_card_id desc,sort asc')
                ->select();
        }
        $planResult = [];
        foreach($plans as $plan){
            $planResult[$plan->action_date][$plan->credit_card_id][] = [
                'id' => $plan->id,
                'type'=>$plan->action,
                'amount'=>$plan->amount,
                'action_date'=>$plan->action_date,
                'finish_time'=>$plan->finish_time,
                'bill_month'=>$plan->bill_month
            ];
            // $planResult[$plan->bill_month][$plan->credit_card_id][] = [
            //     'id' => $plan->id,
            //     'type'=>$plan->action,
            //     'amount'=>$plan->amount,
            //     'action_date'=>$plan->action_date,
            //     'finish_time'=>$plan->finish_time
            // ];
        }

        //改变响应格式
        $planData = [];
        foreach($planResult as $actionDate => $cardPlan){
            $cardsPlanData = [];
            foreach($cardPlan as $cardId => $planList){

                $cardInst = Credit_cards::get($cardId);

                $billInst = $cardInst->getNewestBill();
                //获取本期账单金额
                $newBalance = $cardInst->credit_limit;
                if($billInst){
                    $newBalance = $billInst->new_balance;
                }

                // $status = CardStatus::getInst($userId,$cardId,$planList['bill_month']); 

                $cardsPlanData[] = [
                    'credit_card_id' => $cardId,
                    'bank_name' => $cardInst->bank_name,
                    'name_on_card' => $cardInst->name_on_card,
                    'card_no_last4' => $cardInst->card_no_last4,
                    'credit_limit' =>  $cardInst->credit_limit,
                    'new_balance' => $newBalance,
                    'plan' => $planList
                ];
            }
            $planData[] = [
                'action_date' => $actionDate,
                'data' => $cardsPlanData
            ];
        }
        //改变响应格式
        // $planData = [];
        // foreach($planResult as $billMonth => $cardsPlan){
        //     $cardsPlanData = [];
        //     foreach($cardsPlan as $cardId => $planList){
        //         $planDateList = [];
        //         foreach($planList as $planItem){
        //             $planDateList[$planItem['action_date']] = [
        //                 'id' => $planItem['id'],
        //                 'type'=>$planItem['type'],
        //                 'amount'=>$planItem['amount'],
        //                 'finish_time'=>$planItem['finish_time']
        //             ];
        //         }

        //         $planResDateList = [];
        //         foreach($planDateList as $actionDate => $planItemList){
        //             $planResDateList[]= [
        //                 'date' => $actionDate,
        //                 'plan' => $planItemList
        //             ];
        //         }
        //         $cardInst = Credit_cards::get($cardId);

        //         $billInst = $cardInst->getNewestBill();
        //         //获取本期账单金额
        //         $newBalance = $cardInst->credit_limit;
        //         if($billInst){
        //             $newBalance = $billInst->new_balance;
        //         }

        //         $status = CardStatus::getInst($userId,$cardId,$billMonth); 

        //         $cardsPlanData[] = [
        //             'credit_card_id' => $cardId,
        //             'bank_name' => $cardInst->bank_name,
        //             'name_on_card' => $cardInst->name_on_card,
        //             'card_no_last4' => $cardInst->card_no_last4,
        //             'credit_limit' =>  $cardInst->credit_limit,
        //             'new_balance' => $newBalance,
        //             'repaid' => $status->repaid,
        //             'paid' => $status->paid,
        //             'data' => $planResDateList
        //         ];
        //     }
        //     $planData[] = [
        //         'bill_month' => $billMonth,
        //         'data' => $cardsPlanData
        //     ];
        // }

        my_json_encode(0,'',$planData);
    }

    /**
     * 标记计划项执行接口
     * @param int plan_id 计划ID
     * @param string action 执行动作 "mark"标记已完成;"unmark"取消标记
     */
    public function markPlanExec(Request $request){
        $action = $request->post('action');
        if(!$action){
            my_json_encode(1,'请输入正确的参数action');
        }
        $planId = $request->post('plan_id');
        if(!$planId || !is_numeric($planId)){
            my_json_encode(2,'请输入正确的参数plan_id');
        }

        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(3, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');
        $planInst = Repay_plans::get($planId);
        if($planInst->user_id != $userId){
            my_json_encode(4, '该计划不属于该用户');
        }


        $status = CardStatus::getInst($userId,$planInst->credit_card_id,$planInst->bill_month);
        if($action == 'mark'){
            if($planInst->finish_time){
                my_json_encode(6,'该计划已标记执行，不能重复标记');
            }
            $planInst->finish_time = time();
            if($planInst->action == "repay"){
                $status->repaid += $planInst->amount;
            }else{
                $status->paid += $planInst->amount;
            }
        }else if($action == 'unmark'){
            if(!$planInst->finish_time){
                my_json_encode(7,'该计划已取消标记，不能重复操作');
            }
            $planInst->finish_time = null;
            if($planInst->action == "repay"){
                $status->repaid -= $planInst->amount;
            }else{
                $status->paid -= $planInst->amount;
            }
        }else{
            my_json_encode(5, '请输入正确的参数action');
        }

        $planInst->save();
        $status->save();
        $data = [
            'repaid' => $status->repaid,
            'paid' => $status->paid
        ];
        my_json_encode(0,'',$data);

        
    }

    /**
     * 手动修改计划金额接口
     * @param int plan_id 计划ID
     * @param int modifyAmount 修改后的金额
     * @param string dayList 日期列表,英文逗号分隔，如"2018-07-03,2018-03-04"
     * @param int repayAmount 还款金额，只接受整数
     * @param int planType 规划模式 1=资金不过夜：当日还，当日刷; 2=资金过夜：当日还，次日刷
     */
    public function modifyPlanAmount(Request $request){
        $userSeries = $request->post('series');
        if (empty($userSeries)) {
            my_json_encode(1, 'series不能为空');
        }

        $userId = Users::where(['series' => $userSeries])->value('id');

        $planId = $request->post('plan_id');
        if (empty($planId)) {
            my_json_encode(2, 'plan_id不能为空');
        }

        $modifyAmount = $request->post('modify_amount');
        if (empty($modifyAmount)) {
            my_json_encode(3, 'modify_amount不能为空');
        }


        $dayListStr = $request->post('day_list');
        if (empty($dayListStr)) {
            my_json_encode(4, 'day_list不能为空');
        }

        $repayAmount = $request->post('repay_amount');
        if (empty($repayAmount)) {
            my_json_encode(6, 'repay_amount不能为空');
        }
        if($repayAmount < 1000){
            my_json_encode(11, '还款金额大于1000才能提供计划');
        }
        if (!is_numeric($repayAmount)) {
            my_json_encode(8, 'repay_amount格式不正确');
        } else if (strpos($repayAmount, '.')) {
            my_json_encode(9, 'repay_amount只接受整数');
        }

        $planType = $request->post('plan_type');
        if (empty($planType)) {
            my_json_encode(7, 'plan_type不能为空');
        }
        if ($planType != 1 && $planType != 2) {
            my_json_encode(10, 'plan_type格式错误');
        }


        $planInst = Repay_plans::get($planId);
        $planActionDate = $planInst->action_date;
        $timeAt = '';

        if(strtotime($planActionDate) == strtotime(date('Y-m-d'))){
            //修改的是今天的金额
            $timeAt = 'today';
        }else if(strtotime($planActionDate) < strtotime(date('Y-m-d'))){
            //修改的是过去的金额
            my_json_encode(4,'不能修改已经过去的计划的金额');
        }else{
            //修改的是未来的金额
            $timeAt = 'future';
        }

        //获取整个周期的计划，得知所修改的计划在整个周期计划的位置
        $periodPlan = Repay_plans::where('credit_card_id',$planInst->credit_card_id)
        ->where('bill_month',$planInst->bill_month)
        ->order('action_date asc')
        ->select();

        // TODO future限制
        // if($timeAt == 'future' && $planActionDate != $periodPlan[0]->action_date){
        //     my_json_encode(5,'只能修改第一天或当天的计划');
        // }
        $planItemCount = count($periodPlan);
        if($timeAt == 'today' && $planActionDate == $periodPlan[$planItemCount - 1]->action_date){
            my_json_encode(6,'最后一天不能修改计划');
        }

        $planInst->amount = $modifyAmount;
        $planInst->save();

        //统计从修改的那一天直到周期计划结束的还款总额、刷卡总额,以及修改当天的还款总额、刷卡总额
        // $repayTotal = 0;
        // $payTotal = 0;
        // $_dayRepay = 0; //所修改的当天的还款总额
        // $_dayPay = 0; //所修改的当天的刷卡总额
        // if($timeAt == 'today'){
        //     $startDate = date('Y-m-d 00:00:00');
        //     foreach($periodPlan as $planItem){
        //         if(strtotime($planItem->action_date.' 00:00:00') >= strtotime($startDate)){
        //             if($planItem->action == 'repay'){
        //                 $repayTotal += $planItem->amount;
        //             }else{
        //                 $payTotal += $planItem->amount;
        //             }
        //         }
        //         if(strtotime($planItem->action_date.' 00:00:00') == strtotime($startDate)){
        //             if($planItem->action == 'repay'){
        //                 $_dayRepay += $planItem->amount;
        //             }else{
        //                 $_dayPay += $planItem->amount;
        //             }
        //         }
        //     }
        // }else{
        //     $startDate = $periodPlan[0]->action_date.' 00:00:00';
        //     foreach($periodPlan as $planItem){
        //         if($planItem->action == 'repay'){
        //             $repayTotal += $planItem->amount;
        //         }else{
        //             $payTotal += $planItem->amount;
        //         }
        //         if(strtotime($planItem->action_date.' 00:00:00') == strtotime($startDate)){
        //             if($planItem->action == 'repay'){
        //                 $_dayRepay += $planItem->amount;
        //             }else{
        //                 $_dayPay += $planItem->amount;
        //             }
        //         }
        //     }
        // }

        // //修改的差额
        // $amountDiff = $amount - $planInst->amount;

        // //所修改的那一天之后的还款总额（修改前）（不包含所修改的那一天）
        // $_afterDayRepayTotal = $repayTotal - $_dayRepay;
        // //所修改的那一天之后的刷卡总额（修改前）（不包含所修改的那一天）
        // $_afterDayPayTotal = $PayTotal - $_dayPay;

        // $afterDayRepayTotal = $_afterDayRepayTotal;
        // $afterDayPayTotal = $_afterDayPayTotal;
        // if($planInst->action == 'repay'){
        //     //所修改的那一天之后的还款总额（修改后）（不包含所修改的那一天）
        //     $afterDayRepayTotal = $_afterDayRepayTotal - $amountDiff;
        // }else{
        //     //所修改的那一天之后的刷卡总额（修改后）（不包含所修改的那一天）
        //     $afterDayPayTotal = $_afterDayPayTotal - $amountDiff;
        // }

        // //获取还款计划参数记录
        // $status = CardStatus::getInst($userId,$planInst->credit_card_id,$planInst->bill_month);

        //重新生成计划
        self::genNewPlan($userSeries,$planInst->credit_card_id,$dayListStr,$repayAmount,$planType,$planInst->action_date);
        

        
    }
}