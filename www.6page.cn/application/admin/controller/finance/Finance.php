<?php



namespace app\admin\controller\finance;

use app\admin\controller\AuthController;
use app\admin\model\user\UserBill;
use service\JsonService as Json;
use app\admin\model\finance\FinanceModel;
use service\SystemConfigService;
use service\FormBuilder as Form;
use service\HookService;
use think\Url;
use app\admin\model\user\User;
use app\admin\model\user\UserExtract;

/**
 * 微信充值记录
 * Class Finance
 */
class Finance extends AuthController
{

    /**
     * 显示资金记录
     */
    public function bill()
    {
        $category = $this->request->param('category','now_money');
        $bill_where_op = FinanceModel::bill_where_op($category);
        $list = UserBill::where('type', $bill_where_op['type']['op'], $bill_where_op['type']['condition'])
            ->where('category', $bill_where_op['category']['op'], $bill_where_op['category']['condition'])
            ->field(['title', 'type'])
            ->group('type')
            ->distinct(true)
            ->select()
            ->toArray();
        $this->assign([
            'selectList'=>$list,
            'category'=>$category,
            'gold_name'=>$category == "gold_num" ? SystemConfigService::get("gold_name") : '金额'
        ]);
        return $this->fetch();
    }

    /**
     * 显示资金记录ajax列表
     */
    public function billlist()
    {
        $where = parent::getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['limit', 20],
            ['page', 1],
            ['type', ''],
            ['category', 'now_money']
        ]);
        return Json::successlayui(FinanceModel::getBillList($where));
    }

    /**
     *保存资金监控的excel表格
     */
    public function save_bell_export()
    {
        $where = parent::getMore([
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', ''],
            ['type', ''],
            ['category', 'now_money'],
        ]);
        FinanceModel::SaveExport($where);
    }

    /**
     * 显示佣金记录
     */
    public function commission_list()
    {
        $this->assign('is_layui', true);
        return $this->fetch();
    }

    /**
     * 佣金记录异步获取
     */
    public function get_commission_list()
    {
        $get = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['nickname', ''],
            ['price_max', ''],
            ['price_min', ''],
            ['order', '']
        ]);
        return Json::successlayui(User::getCommissionList($get));
    }


    /**
     * 佣金详情
     */
    public function content_info($uid = '')
    {
        if ($uid == '') return $this->failed('缺少参数');
        $this->assign('userinfo', User::getUserinfo($uid));
        $this->assign('uid', $uid);
        return $this->fetch();
    }

    /**
     * 佣金提现记录个人列表
     */
    public function get_extract_list($uid = '')
    {
        if ($uid == '') return Json::fail('缺少参数');
        $where = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['start_time', ''],
            ['end_time', ''],
            ['nickname', '']
        ]);
        return Json::successlayui(UserBill::getExtrctOneList($where, $uid));
    }

}

