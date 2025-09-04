<?php


namespace app\web\controller;

use app\web\model\special\SpecialSubject;
use app\web\model\store\StoreOrder;
use app\web\model\store\StorePink;
use app\web\model\user\User;
use app\web\model\user\UserBill;
use service\GroupDataService;
use service\JsonService;
use service\SystemConfigService;
use service\UtilService;
use think\response\Json;
use think\Session;
use app\web\model\special\Special;
use think\Url;

/**推广控制器
 * Class Spread
 * @package app\wap\controller
 */
class Spread extends AuthController
{
    /**推广佣金
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function spread()
    {
        $data['income'] = UserBill::whereTime('add_time', 'today')->where('uid', $this->uid)->where('category', 'now_money')
            ->where('type', 'in', ['brokerage'])->sum('number');
        $return = UserBill::whereTime('add_time', 'today')->where('uid', $this->uid)->where('category', 'now_money')
            ->where('type', 'in', ['brokerage_return'])->sum('number');
        $data['income'] =bcsub($data['income'],$return,2);
        $uids=User::where('spread_uid', $this->uid)->column('uid');
        $orderIds = StoreOrder::whereTime('add_time', 'today')->where('uid','in',$uids)->where(['refund_status'=>0,'paid'=>1])->where('pay_price','>', 0)->field('order_id,pink_id')->select();
        $orderids = count($orderIds) ? $orderIds->toArray() : [];
        $order_count = 0;
        foreach ($orderids as $item) {
            if ($item['pink_id']) {
                $res = StorePink::where(['id' => $item['pink_id'], 'is_refund' => 0, 'status' => 3, 'order_id' => $item['order_id']])->count();
                if ($res) $order_count++;
            } else {
                $order_count++;
            }
        }
        $data['order_count'] = $order_count;
        $data['spread_count'] = User::whereTime('spread_time', 'today')->where('spread_uid', $this->uid)->count();
        return JsonService::successful('ok', $data);
    }

    /**
     * 提现页面
     * @return mixed
     */
    public function withdraw()
    {
        if (Session::has('form__token__')) Session::delete('form__token__');
        $token = md5(time() . $this->uid . $this->request->ip());
        Session::set('form__token__', $token);
        $now_moeny = User::where('uid', $this->uid)->value('now_money');
        $this->assign('number', floatval($now_moeny));
        $this->assign('token', $token);
        $this->assign('extract_min_money', (int)SystemConfigService::get('extract_min_money'));
        $this->assign('extract_bank', json_encode(GroupDataService::getData('extract_bank') ?: []));
        $this->assign('brokerage_price', $this->userInfo['brokerage_price']);
        $this->assign('extract_min_money', (float)SystemConfigService::get('extract_min_money'));
        return $this->fetch();
    }

    /**
     * 课程推广
     *
     * */
    public function special()
    {
        return $this->fetch();
    }

    /**
     * 获取年级列表
     *
     * */
    public function get_grade_list()
    {
        return JsonService::successful(SpecialSubject::wapSpecialCategoryAll(1));
    }

    /**
     * 获取每个年级下的课程并分页
     * */
    public function getSpecialSpread()
    {
        $where = UtilService::getMore([
            ['limit', 10],
            ['page', 1],
            ['grade_id', 0],
        ]);
        $is_member=isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        return JsonService::successful(Special::getSpecialSpread($where,$is_member, $this->uid));
    }

    /**
     * 课程推广二维码
     * */
    public function poster_special($special_id = 0)
    {
        if (!$special_id) $this->failed('缺少课程id无法查看海报', Url::build('spread/special'));
        $special = Special::getSpecialInfo($special_id);
        if ($special === false) $this->failed(Special::getErrorInfo(), Url::build('spread/special'));
        if (!$special['poster_image']) $this->failed('您查看的海报不存在', Url::build('spread/special'));
        if($special['is_light']){
            $url = SystemConfigService::get('site_url') . Url::build('/single-course') . '?id=' . $special['id'] . '&link_pay_uid=' . $this->uid . '&link_pay=1&spread_uid=' . $this->uid . '#link_pay';
        }else {
            $url = SystemConfigService::get('site_url') . Url::build('/view-course') . '?id=' . $special['id'] . '&link_pay_uid=' . $this->uid . '&link_pay=1&spread_uid=' . $this->uid . '#link_pay';
        }
        $this->assign([
            'url' => $url,
            'poster_image' => $special['poster_image']
        ]);
        return $this->fetch();
    }

    /**
     * 我的推广人
     *
     * */
    public function my_promoter()
    {
        $uids = User::where('spread_uid', $this->uid)->group('uid')->column('uid');
        $data['one_spread_count'] = count($uids);
        if ($data['one_spread_count']) {
            $data['order_count'] = UserBill::where(['u.paid' => 1, 'u.is_del' => 0])->where('a.category', 'now_money')->where('a.type', 'in', ['brokerage'])->group('u.id')->where('a.uid', 'in', $uids)->join("__STORE_ORDER__ u", 'u.id=a.link_id')->alias('a')->count();
        } else {
            $data['order_count'] = 0;
        }
        $isPromoter=0;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;
        if($storeBrokerageStatu==1){
            if($this->userInfo['is_promoter']){
                $isPromoter=1;
            }
        }else{
            $isPromoter=1;
        }
        $this->assign('isPromoter', $isPromoter);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 佣金明细
     * @return mixed
     */
    public function commission()
    {
        $uids = User::where('spread_uid', $this->uid)->column('uid');
        $data['spread_two'] = 0;
        $data['spread_one'] = 0;
        if ($uids) {
            $data['spread_one'] = UserBill::where(['a.uid' => $this->uid, 'a.type' => 'brokerage', 'a.category' => 'now_money'])->alias('a')
                ->join('store_order o', 'o.id = a.link_id')
                ->whereIn('o.uid', $uids)->where('o.refund_status', 'eq', 0)
                ->where('a.link_id', 'neq', 0)->sum('a.number');
            $uids1 = User::where('spread_uid', 'in', $uids)->group('uid')->column('uid');
            if ($uids1) {
            $data['spread_two'] = UserBill::where(['a.uid' => $this->uid, 'a.type' => 'brokerage', 'a.category' => 'now_money'])->alias('a')
                ->join('store_order o', 'o.id = a.link_id')
                ->whereIn('o.uid', $uids1)->where('o.refund_status', 'eq', 0)
                ->where('a.link_id', 'neq', 0)->sum('a.number');
          }
        }
        $data['sum_spread'] = bcadd($data['spread_one'], $data['spread_two'], 2);
        $this->assign('data', $data);
        return $this->fetch();
    }

    /**
     * 推广明细
     *  @param $type int 明细类型
     *  @return html
     * */
    public function spread_detail($type = 0)
    {
        $this->assign('type', $type);
        return $this->fetch();
    }

    public function get_spread_list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 10],
            ['type', 0]
        ]);
        return JsonService::successful(UserBill::getSpreadList($where, $this->uid));
    }

    public function get_withdrawal_list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 10]
        ]);
        return JsonService::successful(UserBill::get_user_withdrawal_list($where, $this->uid));
    }

    /**
     * 推广海报
     *
     * */
    public function poster_spread()
    {
        $spread_poster_url = SystemConfigService::get('spread_poster_url');
        if (!$spread_poster_url) return $this->failed('海报不存在', Url::build('spread/special'));
        $url = SystemConfigService::get('site_url') . Url::build('Spread/become_promoter', ['spread_uid' => $this->uid]);
        $this->assign([
            'url' => $url,
            'spread_poster_url' => $spread_poster_url
        ]);
        return $this->fetch();
    }

    /**
     * 新增推广人注册
     *
     * */
    public function become_promoter($spread_uid = 0)
    {
        if (!$spread_uid) $this->failed('缺少推广人uid');
        $this->assign('spread_uid', $spread_uid);
        $this->assign('promoter_content', SystemConfigService::get('promoter_content'));
        $this->assign('home_logo', SystemConfigService::get('home_logo'));
        return $this->fetch();
    }

    /**
     * 推广人列表获取
     *
     * */
    public function spread_list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 10],
            ['search', '']
        ]);
        return JsonService::successful(User::getSpreadList($where, $this->uid));
    }

    /**
     * 移除当前用下的推广人
     * @param int $uid 需要移除的用户id
     * */
    public function remove_spread($uid = 0)
    {
        if (!$uid) return JsonService::fail('缺少用户id');
        $res = User::where('uid', $uid)->update(['spread_uid' => 0, 'valid_time' => 0]);
        if ($res)
            return JsonService::successful('移除成功');
        else
            return JsonService::fail('移除失败');
    }
}
