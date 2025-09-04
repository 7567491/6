<?php



namespace app\wap\model\user;


use app\admin\model\wechat\WechatQrcode;
use basic\BaseCheck;
use basic\ModelBasic;
use service\SystemConfigService;
use think\Cookie;
use think\Request;
use think\response\Redirect;
use think\Session;
use think\Url;
use traits\ModelTrait;
use app\wap\model\user\WechatUser;
use service\WechatTemplateService;
use app\wap\model\routine\RoutineTemplate;
use app\wap\model\special\Special;
use app\wap\model\store\StoreProduct;
use app\wap\model\user\MemberShip;
/**用户表
 * Class User
 * @package app\wap\model\user
 */
class User extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time', 'add_ip', 'last_time', 'last_ip'];

    protected function setAddTimeAttr($value)
    {
        return time();
    }

    protected function setAddIpAttr($value)
    {
        return Request::instance()->ip();
    }

    protected function setLastTimeAttr($value)
    {
        return time();
    }

    protected function setLastIpAttr($value)
    {
        return Request::instance()->ip();
    }

    public static function ResetSpread($openid)
    {
        $uid = WechatUser::openidToUid($openid);
        if (self::be(['uid' => $uid, 'is_promoter' => 0])) self::where('uid', $uid)->update(['spread_uid' => 0]);
    }

    /**
     * 绑定用户手机号码修改手机号码用户购买的课程和其他数据
     * @param $bindingPhone 绑定手机号码
     * @param $uid 当前用户id
     * @param $newUid 切换用户id
     * @param bool $isDel 是否删除
     * @param int $qcodeId 扫码id
     * @return bool
     * @throws \think\exception\PDOException
     */
    public static function setUserRelationInfos($uid, $newUid, $qcodeId = 0)
    {
        self::startTrans();
        try {
            //修改下级推广人关系
            self::where('spread_uid', $uid)->update(['spread_uid' => $newUid]);
            // 查询老账户的余额和金币数量
            // 把余额和金币加到当前账户
            $user = self::where('uid', $uid)->find();
            $now_user = self::where('uid', $newUid)->find();
            // 删掉phoneuser里的老用户
            self::getDb('phone_user')->where(['phone' => $now_user['phone'], 'uid' => $now_user['uid']])->delete();
            //修改用户金额变动记录表
            self::getDb('user_bill')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改签到记录表
            self::getDb('user_sign')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改虚拟币充值记录表
            self::getDb('user_recharge')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改收货地址表
            self::getDb('user_address')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改提现记录用户
            self::getDb('user_extract')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改课程购买记录表
            self::getDb('special_buy')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改购物车记录表
            self::getDb('store_cart')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改用户订单记录
            self::getDb('store_order')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改拼团用户记录
            self::getDb('store_pink')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改手机用户表记录
            self::getDb('phone_user')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改会员记录表记录
            self::getDb('member_record')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改搜索记录表记录
            self::getDb('search_history')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改用户报名表记录
            self::getDb('event_sign_up')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改资料订单表记录
            self::getDb('data_download_buy')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改考试相关表记录
            self::getDb('examination_record')->where('uid', $uid)->update(['uid' => $newUid]);
            self::getDb('examination_test_record')->where('uid', $uid)->update(['uid' => $newUid]);
            self::getDb('examination_wrong_bank')->where('uid', $uid)->update(['uid' => $newUid]);
            self::getDb('test_paper_obtain')->where('uid', $uid)->update(['uid' => $newUid]);
            self::getDb('test_paper_order')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改上级推广关系和绑定手机号码
            // 最大化合并会员信息
            self::where('uid', $newUid)->update([
                'now_money' => doubleval($user['now_money']) + doubleval($now_user['now_money']),
                'gold_num' => doubleval($user['gold_num']) + doubleval($now_user['gold_num']),
//                'spread_uid' => $user['spread_uid'],
//                'valid_time' => $user['valid_time'],
                'brokerage_price' => doubleval($user['brokerage_price']) + doubleval($now_user['brokerage_price']),
                'is_permanent' => intval($user['is_permanent']) + intval($now_user['brokerage_price']) > 0 ? 1 : 0,
//                'member_time' => $user['member_time'],
                'overdue_time' => $user['overdue_time'] > $now_user['overdue_time'] ? $user['overdue_time'] : $now_user['overdue_time'],
                'level' => $user['level'] > $now_user['level'] ? $user['level'] : $now_user['level']
            ]);
            if ($qcodeId) WechatQrcode::where('id', $qcodeId)->update(['scan_id' => $newUid]);
            self::commit();
            \think\Session::clear('wap');
            \think\Session::set('loginUid', $newUid, 'wap');
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return self::setErrorInfo($e->getMessage());
        }
    }

    /**
     * 保存微信用户信息
     * @param $wechatUser 用户信息
     * @param int $spread_uid 上级用户uid
     * @return mixed
     */
    public static function setWechatUser($wechatUser, $spread_uid = 0)
    {
        if (isset($wechatUser['uid']) && $wechatUser['uid'] == $spread_uid) $spread_uid = 0;
        // 生成账号
        $account = 'wx' . date('YmdHis') . self::generalString(4);
        $data = [
            'account' => $account,
            'pwd' => md5(123456),
            'nickname' => $wechatUser['nickname'] ?: $account,
            'avatar' => $wechatUser['headimgurl'] ?: '',
            'user_type' => 'wechat',
            // 是否分享过
            'has_shared' => 0,
            'openid' => $wechatUser['openid'],
        ];
        //处理推广关系
        if ($spread_uid){
            $spreadUserInfo = self::getUserInfo($spread_uid);
            $data = self::manageSpread($spread_uid,$data,$spreadUserInfo['is_promoter']);
        }
        $res = self::set($data);
        if ($res) $wechatUser['uid'] = (int)$res['uid'];
        return $wechatUser;
    }

    public static function generalString($length)
    {
        //字符组合
        $str = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $len = strlen($str)-1;
        $randstr = '';
        for ($i=0;$i<$length;$i++) {
            $num=mt_rand(0,$len);
            $randstr .= $str[$num];
        }
        return $randstr;
    }

    /**
     * 设置上下级推广人关系
     * 普通推广人星级关系由字段 is_promoter 区分， is_promoter = 1 为 0 星， is_promoter = 2 为 1 星，依次类推
     * @param $spread_uid 上级推广人
     * @param array $data 更新字段
     * @param bool $isForever
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function manageSpread($spread_uid, $data = [],$is_promoter)
    {
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if($is_promoter) {
                $data['spread_uid'] = $spread_uid;
            }else{
                $data['spread_uid'] = 0;
            }
        }else{
            $data['spread_uid'] = $spread_uid;
        }
        $data['spread_time'] = time();
        return $data;
    }

    /**
     * 更新用户数据并绑定上下级关系
     * @param $wechatUser
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function updateWechatUser($wechatUser, $uid)
    {
        $name = '__login_phone_num' . $uid;
        $userinfo = self::where('uid', $uid)->find();
        //检查是否有此字段
        $spread_uid = isset($wechatUser['spread_uid']) ? $wechatUser['spread_uid'] : 0;
        //自己不能成为自己的下线
        $spread_uid = $spread_uid == $userinfo->uid ? 0 : $spread_uid;
        //手机号码存在直接登陆
        if ($userinfo['phone']) {
            Cookie::set('__login_phone', 1);
            Session::set($name, $userinfo['phone'], 'wap');
            Session::set('__login_phone_number', $userinfo['phone'], 'wap');
        }
        //有推广人直接更新
        $editData = [];
        //不是推广人，并且有上级id绑定关系
        if (!$userinfo->is_promoter && $spread_uid && !$userinfo->spread_uid && $spread_uid != $uid){
            $spreadUserInfo = self::getUserInfo($spread_uid);
            $editData = self::manageSpread($spread_uid, $editData,$spreadUserInfo['is_promoter']);
        }

        $pattern = '/^wx\d{14}[a-zA-Z0-9]{4}$/';
        // 判断是不是没获取到微信昵称和头像
        if(preg_match($pattern, $userinfo['nickname']) || $userinfo['avatar']=='/system/images/user_log.png'){
            $editData['nickname']=$wechatUser['nickname'];
            $editData['avatar']=$wechatUser['headimgurl'];
        }
        return self::edit($editData, $uid, 'uid');
    }

    public static function setSpreadUid($uid, $spreadUid)
    {
        return self::where('uid', $uid)->update(['spread_uid' => $spreadUid]);
    }


    public static function getUserInfo($uid)
    {
        $userInfo = self::where('uid', $uid)->find();
//        if (!Session::has('__login_phone_num' . $uid) && $userInfo['phone']) {
//            Cookie::set('__login_phone', 1);
//            Session::set('__login_phone_num' . $uid, $userInfo['phone'], 'web');
//        }
        unset($userInfo['pwd']);
        if (!$userInfo) {
            throw new \Exception('未查询到此用户');
        }
        return $userInfo->toArray();
    }

    public static function getUserInfoCerti($uid)
    {
        $userInfo = self::where('uid', $uid)->field('full_name')->find();
        return $userInfo->toArray();
    }

    /**
     * 获得当前登陆用户UID
     * @return int $uid
     */
    public static function getActiveUid()
    {
//        $uid = null;
//        if (!Cookie::get('is_login')) exit(exception('请登陆!'));
//        if (Session::has('loginUid', 'wap')) $uid = Session::get('loginUid', 'wap');
//        if (!$uid && Session::has('loginOpenid', 'wap') && ($openid = Session::get('loginOpenid', 'wap')))
//            $uid = WechatUser::openidToUid($openid);
//        if (!$uid) exit(exception('请登陆!'));
//        return $uid;

        $uid = null;
        $data = BaseCheck::auth();
        if ($data['status']) $uid = $data['payload']['data']['loginUid'];
        if (!$uid) exit(exception('请登陆!'));
        return $uid;
    }
    /**
     * 获取登陆的手机号码
     * @param int $uid 用户id
     * @param string $phone 用户号码
     * @return string
     * */
    public static function getLogPhone($uid, $phone = null)
    {
        try {
            $data = BaseCheck::auth();
            if ($data['status']) $phone = $data['payload']['data']['__login_phone_number'];
            return $phone;
        } catch (\Exception $e) {
            return null;
        }
    }
    /**
     * 一级推广 课程
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerage($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        if (!$userInfo || !$userInfo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $data=Special::getIndividualDistributionSettings($orderInfo['cart_id']);
        if(isset($data['is_alone']) && $data['is_alone']){
            if(!isset($data['brokerage_ratio']) || !$data['brokerage_ratio']) return true;
            $brokerageRatio = bcdiv($data['brokerage_ratio'],100,2);
        }else{
            $course_distribution_switch = SystemConfigService::get('course_distribution_switch');//课程分销开关
            if($course_distribution_switch==0) return true;
            $brokerageRatio = bcdiv(SystemConfigService::get('store_brokerage_ratio'),100,2);
        }
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '一级推广人' .$userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买课程,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买课程返佣', $userInfo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfo['spread_uid'])->value('openid')) {
            $wechat_notification_message = SystemConfigService::get('wechat_notification_message');
            if($wechat_notification_message==1){
                WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                    'first' => '叮！您收到一笔课程返佣，真是太优秀了！',
                    'keyword1' => '返佣金额',
                    'keyword2' => $brokeragePrice,
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $User['brokerage_price'],
                    'remark' => '点击查看详情'
                ], Url::build('wap/spread/commission', [], true, true));
            }else{
                $dat['thing8']['value'] =  '返佣金额';
                $dat['date4']['value'] =  date('Y-m-d H:i:s',time());
                $dat['amount1']['value'] =  $brokeragePrice;
                $dat['amount2']['value'] =  $User['brokerage_price'];
                $dat['thing5']['value'] =  '您收到一笔课程返佣!';
                RoutineTemplate::sendAccountChanges($dat,$userInfo['spread_uid'],Url::build('wap/spread/commission', [],true, true));
            }
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        if ($res) self::backOrderBrokerageTwo($orderInfo);
        return $res;
    }

    /**
     * 二级推广 课程
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageTwo($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        $userInfoTwo = User::getUserInfo($userInfo['spread_uid']);
        if (!$userInfoTwo || !$userInfoTwo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfoTwo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $data=Special::getIndividualDistributionSettings($orderInfo['cart_id']);
        if(isset($data['is_alone']) && $data['is_alone']){
            if(!isset($data['brokerage_two']) || !$data['brokerage_two']) return true;
            $brokerageRatio = bcdiv($data['brokerage_two'],100,2);
        }else {
            $course_distribution_switch = SystemConfigService::get('course_distribution_switch');//课程分销开关
            if ($course_distribution_switch == 0) return true;
            $brokerageRatio = bcdiv(SystemConfigService::get('store_brokerage_two'), 100, 2);
        }
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '二级推广人' . $userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买课程,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买课程返佣', $userInfoTwo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfoTwo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfoTwo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfoTwo['spread_uid'])->value('openid')) {
            $wechat_notification_message = SystemConfigService::get('wechat_notification_message');
            if($wechat_notification_message==1){
                WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                    'first' => '叮！您收到一笔课程返佣，真是太优秀了！',
                    'keyword1' => '返佣金额',
                    'keyword2' => $brokeragePrice,
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $User['brokerage_price'],
                    'remark' => '点击查看详情'
                ], Url::build('wap/spread/commission', [], true, true));
            }else{
                $dat['thing8']['value'] =  '返佣金额';
                $dat['date4']['value'] =  date('Y-m-d H:i:s',time());
                $dat['amount1']['value'] =  $brokeragePrice;
                $dat['amount2']['value'] =  $User['brokerage_price'];
                $dat['thing5']['value'] =  '您收到一笔课程返佣!';
                RoutineTemplate::sendAccountChanges($dat,$userInfoTwo['spread_uid'],Url::build('wap/spread/commission', [],true, true));
            }
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }
    /**
     * 一级推广 商品
     * @param $orderInfo
     * @return bool
     */
    public static function backGoodsOrderBrokerage($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        if (!$userInfo || !$userInfo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $data=StoreProduct::getIndividualDistributionSettings($orderInfo['id']);
        if(isset($data['is_alone']) && $data['is_alone']){
            if(!isset($data['brokerage_ratio']) || !$data['brokerage_ratio']) return true;
            $brokerageRatio = bcdiv($data['brokerage_ratio'],100,2);
        }else {
            $course_distribution_switch = SystemConfigService::get('goods_distribution_switch');//商品分销开关
            if ($course_distribution_switch == 0) return true;
            $brokerageRatio = bcdiv(SystemConfigService::get('goods_brokerage_ratio'), 100, 2);
        }
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '一级推广人' .$userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买商品,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买商品返佣', $userInfo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfo['spread_uid'])->value('openid')) {
            $wechat_notification_message = SystemConfigService::get('wechat_notification_message');
            if($wechat_notification_message==1){
                WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                    'first' => '叮！您收到一笔商品返佣，真是太优秀了！',
                    'keyword1' => '返佣金额',
                    'keyword2' => $brokeragePrice,
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $User['brokerage_price'],
                    'remark' => '点击查看详情'
                ], Url::build('wap/spread/commission', [], true, true));
            }else{
                $dat['thing8']['value'] =  '返佣金额';
                $dat['date4']['value'] =  date('Y-m-d H:i:s',time());
                $dat['amount1']['value'] =  $brokeragePrice;
                $dat['amount2']['value'] =  $User['brokerage_price'];
                $dat['thing5']['value'] =  '您收到一笔商品返佣!';
                RoutineTemplate::sendAccountChanges($dat,$userInfo['spread_uid'],Url::build('wap/spread/commission', [],true, true));
            }
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        if ($res) self::backGoodsOrderBrokerageTwo($orderInfo);
        return $res;
    }

    /**
     * 二级推广 商品
     * @param $orderInfo
     * @return bool
     */
    public static function backGoodsOrderBrokerageTwo($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        $userInfoTwo = User::getUserInfo($userInfo['spread_uid']);
        if (!$userInfoTwo || !$userInfoTwo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfoTwo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $data=StoreProduct::getIndividualDistributionSettings($orderInfo['id']);
        if(isset($data['is_alone']) && $data['is_alone']){
            if(!isset($data['brokerage_two']) || !$data['brokerage_two']) return true;
            $brokerageRatio = bcdiv($data['brokerage_two'],100,2);
        }else {
            $course_distribution_switch = SystemConfigService::get('goods_distribution_switch');//商品分销开关
            if ($course_distribution_switch == 0) return true;
            $brokerageRatio = bcdiv(SystemConfigService::get('goods_brokerage_two'), 100, 2);
        }
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '二级推广人' . $userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买商品,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买商品返佣', $userInfoTwo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfoTwo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfoTwo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfoTwo['spread_uid'])->value('openid')) {
            $wechat_notification_message = SystemConfigService::get('wechat_notification_message');
            if($wechat_notification_message==1){
                WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                    'first' => '叮！您收到一笔课程商品，真是太优秀了！',
                    'keyword1' => '返佣金额',
                    'keyword2' => $brokeragePrice,
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $User['brokerage_price'],
                    'remark' => '点击查看详情'
                ], Url::build('wap/spread/commission', [], true, true));
            }else{
                $dat['thing8']['value'] =  '返佣金额';
                $dat['date4']['value'] =  date('Y-m-d H:i:s',time());
                $dat['amount1']['value'] =  $brokeragePrice;
                $dat['amount2']['value'] =  $User['brokerage_price'];
                $dat['thing5']['value'] =  '您收到一笔商品返佣!';
                RoutineTemplate::sendAccountChanges($dat,$userInfoTwo['spread_uid'],Url::build('wap/spread/commission', [],true, true));
            }
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }
    /**
     * 一级推广 会员
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageMember($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        if (!$userInfo || !$userInfo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $data=MemberShip::getIndividualDistributionSettings($orderInfo['member_id']);
        if(isset($data['is_alone']) && $data['is_alone']){
            if(!isset($data['brokerage_ratio']) || !$data['brokerage_ratio']) return true;
            $brokerageRatio = bcdiv($data['brokerage_ratio'],100,2);
        }else {
            $member_distribution_switch = SystemConfigService::get('member_distribution_switch');//会员分销开关
            if ($member_distribution_switch == 0) return true;
            $brokerageRatio = bcdiv(SystemConfigService::get('member_brokerage_ratio'), 100, 2);
        }
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '一级推广人' .$userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买会员,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买会员返佣', $userInfo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfo['spread_uid'])->value('openid')) {
            $wechat_notification_message = SystemConfigService::get('wechat_notification_message');
            if($wechat_notification_message==1){
                WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                    'first' => '叮！您收到一笔会员返佣，真是太优秀了！',
                    'keyword1' => '返佣金额',
                    'keyword2' => $brokeragePrice,
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $User['brokerage_price'],
                    'remark' => '点击查看详情'
                ], Url::build('wap/spread/commission', [], true, true));
            }else{
                $dat['thing8']['value'] =  '返佣金额';
                $dat['date4']['value'] =  date('Y-m-d H:i:s',time());
                $dat['amount1']['value'] =  $brokeragePrice;
                $dat['amount2']['value'] =  $User['brokerage_price'];
                $dat['thing5']['value'] =  '您收到一笔会员返佣!';
                RoutineTemplate::sendAccountChanges($dat,$userInfo['spread_uid'],Url::build('wap/spread/commission', [],true, true));
            }
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        if ($res) self::backOrderBrokerageTwoMember($orderInfo);
        return $res;
    }

    /**
     * 二级推广 会员
     * @param $orderInfo
     * @return bool
     */
    public static function backOrderBrokerageTwoMember($orderInfo)
    {
        $userInfo = User::getUserInfo($orderInfo['uid']);
        $userInfoTwo = User::getUserInfo($userInfo['spread_uid']);
        if (!$userInfoTwo || !$userInfoTwo['spread_uid']) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfoTwo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $data=MemberShip::getIndividualDistributionSettings($orderInfo['member_id']);
        if(isset($data['is_alone']) && $data['is_alone']){
            if(!isset($data['brokerage_two']) || !$data['brokerage_two']) return true;
            $brokerageRatio = bcdiv($data['brokerage_two'],100,2);
        }else {
            $member_distribution_switch = SystemConfigService::get('member_distribution_switch');//会员分销开关
            if($member_distribution_switch==0) return true;
            $brokerageRatio = bcdiv(SystemConfigService::get('member_brokerage_two'),100,2);
        }
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '二级推广人' . $userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买会员,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买会员返佣', $userInfoTwo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfoTwo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfoTwo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfoTwo['spread_uid'])->value('openid')) {
            $wechat_notification_message = SystemConfigService::get('wechat_notification_message');
            if($wechat_notification_message==1){
                WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                    'first' => '叮！您收到一笔会员返佣，真是太优秀了！',
                    'keyword1' => '返佣金额',
                    'keyword2' => $brokeragePrice,
                    'keyword3' => date('Y-m-d H:i:s', time()),
                    'keyword4' => $User['brokerage_price'],
                    'remark' => '点击查看详情'
                ], Url::build('wap/spread/commission', [], true, true));
            }else{
                $dat['thing8']['value'] =  '返佣金额';
                $dat['date4']['value'] =  date('Y-m-d H:i:s',time());
                $dat['amount1']['value'] =  $brokeragePrice;
                $dat['amount2']['value'] =  $User['brokerage_price'];
                $dat['thing5']['value'] =  '您收到一笔会员返佣！';
                RoutineTemplate::sendAccountChanges($dat,$userInfoTwo['spread_uid'],Url::build('wap/spread/commission', [],true, true));
            }
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
    }


    /**
     * 获取推广人列表
     * @param $where array 查询条件
     * @param $uid int 用户uid
     * @return array
     * */
    public static function getSpreadList($where, $uid)
    {
        $uids = self::getSpeadUids($uid, true);
        if (!count($uids)) return ['list' => [], 'page' => 2];
        $model = self::where('uid', 'in', $uids)->field(['nickname', 'avatar', 'phone', 'uid']);
        if ($where['search']) $model = $model->where('nickname|uid|phone', 'like', "%$where[search]%");
        $list = $model->page((int)$where['page'], (int)$where['limit'])->select();
        $list = count($list) ? $list->toArray() : [];
        $page = $where['page'] + 1;
        foreach ($list as $key => &$item) {
            $item['sellout_count'] = UserBill::where(['a.paid' => 1,'a.is_del' => 0,'u.category'=>'now_money','u.type'=>'brokerage','u.uid'=>$item['uid']])->alias('u')->join('__STORE_ORDER__ a', 'a.id=u.link_id')
                ->count();
            $item['sellout_money'] = UserBill::where(['a.paid' => 1,'a.is_del' => 0,'u.category'=>'now_money','u.type'=>'brokerage','u.uid'=>$item['uid']])->alias('u')->join('__STORE_ORDER__ a', 'a.id=u.link_id')
                ->sum('a.total_price');
        }
        return compact('list', 'page');
    }

    /**
     * 获取当前用户的下两级
     * @param int $uid 用户uid
     * @return array
     * */
    public static function getSpeadUids($uid, $isOne = false)
    {
        $uids = User::where('spread_uid', $uid)->column('uid');
        if ($isOne) return $uids;
        $two_uids = count($uids) ? User::where('spread_uid', 'in', $uids)->column('uid') : [];
        return array_merge($uids, $two_uids);
    }
    /**手机号登录、注册
     * @param $phone
     * @param $request
     * @return array|bool
     */
    public static function UserLogIn($phone, $request)
    {
        self::startTrans();
        try {
            // 判断手机号是否存在，如果存在则更新
            if (self::be(['phone' => $phone])) {
                $user = self::where('phone', $phone)->find();
                if (!$user->status) return self::setErrorInfo('账户已被禁止登录');
                $user->last_ip = $request->ip();
                $user->last_time = time();
                $userinfo = $user;
                $user->save();
            } else {
                // 手机号不存在则创建
                $userinfo = self::set([
                    'nickname' => $phone,
                    'pwd' => md5(123456),
                    'avatar' => '/system/images/user_log.png',
                    'account' => $phone,
                    'phone' => $phone,
                    'is_h5user' => 2,
                ]);
                if (!$userinfo) return self::setErrorInfo('用户信息写入失败', true);
            }
            unset($userinfo['pwd']);

            Session::delete('login_error_info', 'web');
            $data = [
                'loginUid' => $userinfo['uid'],
                '__login_phone_number' => $userinfo['phone'],
                '__login_phone_num' . $userinfo['uid'] => $userinfo['phone'],
            ];
            $token = BaseCheck::getJWTToken($data);
            Cookie::set('wy-auth', $token, 86400 * 30);
            $userinfo['token'] = $token;

            self::commit();
            return ['userinfo' => $userinfo];
        } catch (\Exception $e) {
            return self::setErrorInfo($e->getMessage());
        }
    }
    /**用户注册账号、找回密码
     * @param $account
     * @param $pwd
     * @param $type
     * @param $request
     * @return bool
     */
    public static function userRegister($account,$pwd,$type,$request)
    {
        self::beginTrans();
        try {
            if ($type==2) {
                // 修改密码
                $user = self::where('phone', $account)->find();
                if (!$user->status) return self::setErrorInfo('账户已被禁止登录');
                $userinfo = User::where(['phone' => $account])->find();
                if (!$userinfo) return self::setErrorInfo('您要找回的账号不存在', true);
                if($user['pwd']==$pwd || $userinfo['pwd']==$pwd) return self::setErrorInfo('新密码和旧密码重复', true);
                $res1 = self::where(['phone' => $account])->update(['pwd' => $pwd]);
                $res = $res1;
                self::checkTrans($res);
                if ($res) {
                    return true;
                } else {
                    return false;
                }
            } else if($type==1) {
                // 注册账号
                $userinfo = self::where(['phone' => $account])->find();
                if($userinfo) return self::setErrorInfo('账号已存在', true);
                $userinfo = self::set([
                    'nickname' => $account,
                    'pwd' => $pwd,
                    'avatar' => '/system/images/user_log.png',
                    'account' => $account,
                    'phone' => $account,
                    'is_h5user' => 2,
                ]);
                if (!$userinfo) return self::setErrorInfo('用户信息写入失败', true);
                self::commitTrans();
                return true;
            }
        } catch (\Exception $e) {
            return self::setErrorInfo($e->getMessage());
        }
    }
}
