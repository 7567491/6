<?php



namespace app\web\model\user;


use app\admin\model\wechat\WechatQrcode;
use app\wap\model\user\WechatUser;
use basic\BaseCheck;
use service\WechatTemplateService;
use basic\ModelBasic;
use service\SystemConfigService;
use think\Cookie;
use think\Request;
use think\response\Redirect;
use think\Session;
use think\Url;
use traits\ModelTrait;

/**用户信息表
 * Class User
 * @package app\web\model\user
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
            //修改提现记录用户
            self::getDb('user_extract')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改签到记录表
            self::getDb('user_sign')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改虚拟币充值记录表
            self::getDb('user_recharge')->where('uid', $uid)->update(['uid' => $newUid]);
            //修改收货地址表
            self::getDb('user_address')->where('uid', $uid)->update(['uid' => $newUid]);
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
            \think\Session::clear('web');
            \think\Session::set('loginUid', $newUid, 'web');
            return true;
        } catch (\Exception $e) {
            self::rollback();
            return self::setErrorInfo($e->getMessage());
        }
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

    /**
     * 获得当前登陆用户UID
     * @return int $uid
     */
    public static function getActiveUid()
    {
        $uid = null;
        $data = BaseCheck::auth();
        if ($data['status']) $uid = $data['payload']['data']['loginUid'];
        if (!$uid) exit(exception('请登陆!'));
        return $uid;
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
        $course_distribution_switch = SystemConfigService::get('course_distribution_switch');//课程分销开关
        if($course_distribution_switch==0) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $brokerageRatio = bcdiv(SystemConfigService::get('store_brokerage_ratio'),100,2);
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '一级推广人' .$userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买课程,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买课程返佣', $userInfo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfo['spread_uid'])->value('openid')) {
            WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                'first' => '叮！您收到一笔课程返佣，真是太优秀了！',
                'keyword1' => '返佣金额',
                'keyword2' => $brokeragePrice,
                'keyword3' => date('Y-m-d H:i:s', time()),
                'keyword4' => $User['brokerage_price'],
                'remark' => '点击查看详情'
            ], Url::build('wap/spread/commission', [], true, true));
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
        $course_distribution_switch = SystemConfigService::get('course_distribution_switch');//课程分销开关
        if($course_distribution_switch==0) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfoTwo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $brokerageRatio = bcdiv(SystemConfigService::get('store_brokerage_two'),100,2);
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '二级推广人' . $userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买课程,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买课程返佣', $userInfoTwo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfoTwo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfoTwo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfoTwo['spread_uid'])->value('openid')) {
            WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                'first' => '叮！您收到一笔课程返佣，真是太优秀了！',
                'keyword1' => '返佣金额',
                'keyword2' => $brokeragePrice,
                'keyword3' => date('Y-m-d H:i:s', time()),
                'keyword4' => $User['brokerage_price'],
                'remark' => '点击查看详情'
            ], Url::build('wap/spread/commission', [], true, true));
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
        $member_distribution_switch = SystemConfigService::get('member_distribution_switch');//会员分销开关
        if($member_distribution_switch==0) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $brokerageRatio = bcdiv(SystemConfigService::get('member_brokerage_ratio'),100,2);
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '一级推广人' .$userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买会员,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买会员返佣', $userInfo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfo['spread_uid'])->value('openid')) {
            WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                'first' => '叮！您收到一笔会员返佣，真是太优秀了！',
                'keyword1' => '返佣金额',
                'keyword2' => $brokeragePrice,
                'keyword3' => date('Y-m-d H:i:s', time()),
                'keyword4' => $User['brokerage_price'],
                'remark' => '点击查看详情'
            ], Url::build('wap/spread/commission', [], true, true));
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
        $member_distribution_switch = SystemConfigService::get('member_distribution_switch');//会员分销开关
        if($member_distribution_switch==0) return true;
        $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
        if ($storeBrokerageStatu == 1) {
            if (!User::be(['uid' => $userInfoTwo['spread_uid'], 'is_promoter' => 1])) return true;
        }
        $brokerageRatio = bcdiv(SystemConfigService::get('member_brokerage_two'),100,2);
        if ($brokerageRatio <= 0) return true;
        $brokeragePrice = bcmul($orderInfo['pay_price'], $brokerageRatio, 2);
        if ($brokeragePrice <= 0) return true;
        $mark = '二级推广人' . $userInfo['nickname'] . '消费' . floatval($orderInfo['pay_price']) . '元购买会员,奖励推广佣金' . floatval($brokeragePrice);
        self::beginTrans();
        $res1 = UserBill::income('购买会员返佣', $userInfoTwo['spread_uid'], 'now_money', 'brokerage', $brokeragePrice, $orderInfo['id'], 0, $mark);
        $res2 = self::bcInc($userInfoTwo['spread_uid'], 'brokerage_price', $brokeragePrice, 'uid');
        $User = User::getUserInfo($userInfoTwo['spread_uid']);
        if ($openid = WechatUser::where('uid', $userInfoTwo['spread_uid'])->value('openid')) {
            WechatTemplateService::sendTemplate($openid, WechatTemplateService::USER_BALANCE_CHANGE, [
                'first' => '叮！您收到一笔会员返佣，真是太优秀了！',
                'keyword1' => '返佣金额',
                'keyword2' => $brokeragePrice,
                'keyword3' => date('Y-m-d H:i:s', time()),
                'keyword4' => $User['brokerage_price'],
                'remark' => '点击查看详情'
            ], Url::build('wap/spread/commission', [], true, true));
        }
        $res = $res1 && $res2;
        self::checkTrans($res);
        return $res;
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
     * 获取登陆的openid
     * @param int $uid 用户id
     * @param string $phone 用户号码
     * @return string
     * */
    public static function getLogOpenid($uid, $openid = null)
    {

        try {
            $data = BaseCheck::auth();
            if ($data['status']) $openid = $data['payload']['data']['__login_openid_number'];
            return $openid;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**获取手机号
     * @param $uid
     * @return mixed
     */
    public static function UidToPhone($uid)
    {
        return self::where('uid', $uid)->value('phone');
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
