<?php


namespace app\wap\controller;


use Api\Express;
use app\admin\model\system\SystemAdmin;
use app\admin\model\system\SystemConfig;
use app\wap\model\activity\EventSignUp;
use app\wap\model\special\SpecialRecord;
use app\wap\model\special\SpecialRelation;
use app\wap\model\user\SmsCode;
use app\wap\model\special\Grade;
use app\wap\model\store\StoreOrderCartInfo;
use app\wap\model\store\StorePink;
use app\wap\model\store\StoreProduct;
use app\wap\model\store\StoreProductRelation;
use app\wap\model\store\StoreOrder;
use app\wap\model\user\WechatUser;
use app\wap\model\user\User;
use app\wap\model\user\UserBill;
use app\wap\model\user\UserExtract;
use app\wap\model\user\UserAddress;
use app\wap\model\user\UserSign;
use service\CacheService;
use service\GroupDataService;
use service\JsonService;
use service\SystemConfigService;
use service\UtilService;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;
use app\wap\model\recommend\Recommend;

/**my 控制器
 * Class My
 * @package app\wap\controller
 */
class My extends AuthController
{

    /*
     * 白名单
     * */
    public static function WhiteList()
    {
        return [
            'index',
            'about_us',
            'getPersonalCenterMenu',
            'questionModule'
        ];
    }

    /**
     * 退出手机号码登录
     */
    public function logout()
    {
        Session::clear('wap');
        Cookie::delete('wy-auth');
        return JsonService::successful('已退出登录');
    }
    /**
     * 获取获取个人中心菜单
     */
    public function getPersonalCenterMenu()
    {
        $store_brokerage_statu=SystemConfigService::get('store_brokerage_statu');
        if($store_brokerage_statu==1){
            if(isset($this->userInfo['is_promoter'])) $is_statu=$this->userInfo['is_promoter'] >0 ? 1 : 0;
            else $is_statu=0;
        }else if($store_brokerage_statu==2){
            $is_statu=1;
        }
        $is_write_off=isset($this->userInfo['is_write_off']) ? $this->userInfo['is_write_off'] : 0;
        return JsonService::successful(Recommend::getPersonalCenterMenuList($is_statu,$is_write_off,$this->uid));
    }

    /**
     * 题库模块
     */
    public function questionModule()
    {
        $question=GroupDataService::getData('question_bank_module',2);
        return JsonService::successful($question);
    }

    /**我的赠送
     * @return mixed
     */
    public function my_gift()
    {
        return $this->fetch();
    }

    /**我的报名
     * @return mixed
     */
    public function sign_list()
    {

        return $this->fetch();
    }

    /**获取核销订单信息
     * @param int $type
     * @param string $order_id
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sign_order($type=2,$order_id='')
    {
        if($type==2 && !$this->userInfo['is_write_off'])return $this->failed('您没有权限!');
        $this->assign(['type'=>$type,'order_id'=>$order_id]);
        return $this->fetch();
    }

    /**报名详情
     * @param string $order_id
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function sign_my_order($order_id='')
    {
        $this->assign(['order_id'=>$order_id]);
        return $this->fetch('order_verify');
    }

    /**我的信息
     * @return mixed
     */
    public function user_info()
    {

        return $this->fetch();
    }

    public function verify_activity()
    {

        return $this->fetch();
    }

    /**
     * 手机号验证
     */
    public function validate_code()
    {
        list($phone, $code,) = UtilService::getMore([
            ['phone', ''],
            ['code', ''],
        ], $this->request, true);
        if (!$phone) return JsonService::fail('请输入手机号码');
        if (!$code) return JsonService::fail('请输入验证码');
        $code=md5('is_phone_code'.$code);
        if (!SmsCode::CheckCode($phone, $code)) return JsonService::fail('验证码验证失败');
        SmsCode::setCodeInvalid($phone, $code);
        return JsonService::successful('验证成功');
    }

    /**
     * 信息保存
     */
    public function save_user_info()
    {
        $data = UtilService::postMore([
            ['avatar', ''],
            ['nickname', ''],
            ['full_name', ''],
            ['grade_id', 0]
        ], $this->request);
        if($data['nickname'] != strip_tags($data['nickname'])){
            $data['nickname'] = htmlspecialchars($data['nickname']);
        }
        if (!$data['nickname']) return JsonService::fail('用户昵称不能为空');
        if (User::update($data, ['uid' => $this->uid]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }

    // 预检测手机号是否已经注册过h5，用于提醒用户合并用户风险
    public function check_is_h5()
    {
        list($phone) = UtilService::getMore([
            ['phone', '']
        ], $this->request, true);
        if (!$phone) return JsonService::fail('请输入手机号码');
        $user=User::where(['phone' => $phone])->where('is_h5user', '>', 0)->find();
        if ($user) {
            return JsonService::successful('检测到该手机号已在其他渠道注册过，继续绑定会丢失部分数据且无法恢复，请谨慎操作，是否继续绑定？', ['used' => 1]);
        } else {
            return JsonService::successful('手机号未注册过', ['used' => 0]);
        }
    }

    /**
     * 保存手机号码
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function save_phone()
    {
        if ($this->request->isAjax()) {
            list($phone, $code, $type) = UtilService::getMore([
                ['phone', ''],
                ['code', ''],
                ['type', 0],
            ], $this->request, true);
            if (!$phone) return JsonService::fail('请输入手机号码');
            if (!$code) return JsonService::fail('请输入验证码');
            $code=md5('is_phone_code'.$code);
            if (!SmsCode::CheckCode($phone, $code)) return JsonService::fail('验证码验证失败');
            SmsCode::setCodeInvalid($phone, $code);
            // 检查手机号是否绑定过
            $user = User::where(['phone' => $phone])->find();
            // 需判断是不是换绑手机号，如果是换绑的话，不允许绑定已经被占用的手机号，因无法保证两个账户购买信息正常合并
            // 如果是微信账号或小程序账号第一次绑定手机号则允许绑定h5占用的手机号，因为账号里肯定没有购买信息
            // 如果现账号已经有了手机号，则说明现在是在换绑手机号
            if($this->userInfo['phone']) {
                if ($user) {
                    return JsonService::fail('该手机号已被占用，请更换手机号重试');
                } else {
                    $ures = User::update(['phone' => $phone, 'account' => $phone], ['uid' => $this->uid]);
                    if ($ures) {
                        return JsonService::successful('绑定成功');
                    } else {
                        return JsonService::fail('绑定失败');
                    }
                }
            }
            // 如果没绑定过，就直接把手机号写入user表
            if(!$user){
                $currentOpenid = $this->userInfo['openid'];
                $bindData = ['phone' => $phone];
                // 对旧系统做兼容，如果$currentOpenid为空，说明是旧系统用户，需要从wechatUser中获取openid
                if (!$currentOpenid) {
                    $currentOpenid = WechatUser::where(['uid' => $this->uid])->value('openid');
                    $bindData['openid'] = $currentOpenid;
                }
                $ures = User::update($bindData, ['uid' => $this->uid]);
                if ($ures) {
                    return JsonService::successful('绑定成功');
                } else {
                    return JsonService::fail('绑定失败');
                }
            }
            if($user['uid'] == $this->uid) {
                return JsonService::fail('不能绑定相同手机号');
            }
            // 如果手机号绑定过，检查该用户是否已经绑定过其他公众号，如果已经绑定了其他公众号那就不让绑
            if ($user['openid']) {
                return JsonService::fail('该手机号已被占用，请更换手机号重试');
            } else {
                // 如果没绑定过其他公众号，说明这个手机号已经在h5或小程序端注册过了，需要把当前openid写到已经绑定过手机的那个账号，并把现有账号删掉，注意需要重新登录；
                $currentOpenid = $this->userInfo['openid'];
                // 对旧系统做兼容，如果$currentOpenid为空，说明是旧系统用户，需要从wechatUser中获取openid
                if (!$currentOpenid) {
                    $currentOpenid = WechatUser::where(['uid' => $this->uid])->value('openid');
                }
                // 把当前账号的购买信息合并到已经绑定过手机号的账号
                User::setUserRelationInfos($this->uid, $user['uid']);
                $duser = User::where(['uid' => $this->uid])->delete();
                $ures = User::update(['openid' => $currentOpenid], ['uid' => $user['uid']]);
                $uwres = true;
                // 判断这个手机号所在用户有没有对应的WeChatUser，因为通过h5注册的用户没有WeChatUser
                // 如果没有wechatuser，则保留刚刚生成WeChatuser，并把uid指向原h5账号
                if (!WechatUser::be(['uid' => $user['uid']])) {
                    $dwuser = WechatUser::update(['uid' => $user['uid']], ['uid' => $this->uid]);
                } else {
                    // 如果原账号已经有了WeChatuser，说明之前已经绑定过小程序，直接删除刚生成的WeChatuser，并把openid写入原有wechatuser
                    $dwuser = WechatUser::where(['uid' => $this->uid])->delete();
                    $uwres = WechatUser::update(['openid' => $currentOpenid], ['uid' => $user['uid']]);
                }

                if ($duser && $dwuser && $ures && $uwres) {
                    return JsonService::successful('绑定成功，请重新登录');
                } else {
                    return JsonService::fail('绑定失败');
                }
            }
        } else {
            $this->assign('user_phone', $this->userInfo['phone']);
            return $this->fetch();
        }
    }

    /**
     * 个人中心
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        $store_brokerage_statu=SystemConfigService::get('store_brokerage_statu');
        if($store_brokerage_statu==1){
           if(isset($this->userInfo['is_promoter'])) $is_statu=$this->userInfo['is_promoter'] >0 ? 1 : 0;
           else $is_statu=0;
        }else if($store_brokerage_statu==2){
            $is_statu=1;
        }
        if(isset($this->userInfo['overdue_time']))$overdue_time=date('Y-m-d',$this->userInfo['overdue_time']);
        else $overdue_time=0;
        $this->assign([
            'store_switch'=>SystemConfigService::get('store_switch'),
            'collectionNumber' => SpecialRelation::where('uid', $this->uid)->count(),
            'recordNumber' => SpecialRecord::where('uid', $this->uid)->count(),
            'overdue_time'=>$overdue_time,
            'is_statu'=>$is_statu,
        ]);
        return $this->fetch();
    }
    /**虚拟币明细
     * @return mixed
     */
    public function gold_coin(){
        $gold_name=SystemConfigService::get('gold_name');//虚拟币名称
        $this->assign(compact('gold_name'));
        return $this->fetch('coin_detail');
    }
    /**签到
     * @return mixed
     */
    public function sign_in()
    {
        $urls=SystemConfigService::get('site_url').'/';
        $gold_name=SystemConfigService::get('gold_name');//虚拟币名称
        $gold_coin=SystemConfigService::get('single_gold_coin');//签到获得虚拟币
        $signed = UserSign::checkUserSigned($this->uid);//今天是否签到
        $sign_image = $urls."uploads/" . "poster_sign_" .$this->uid . ".png";
        $signCount = UserSign::userSignedCount($this->uid);//累记签到天数
        $this->assign(compact('signed', 'signCount',  'gold_name','gold_coin', 'sign_image'));
        return $this->fetch();
    }


    /**签到明细
     * @return mixed
     */
    public function sign_in_list(){

        return $this->fetch();
    }

    /**地址列表
     * @return mixed
     */
    public function address()
    {
        $address=UserAddress::getUserValidAddressList($this->uid, 'id,real_name,phone,province,city,district,detail,is_default');
        $this->assign([
            'address' => json_encode($address)
        ]);
        return $this->fetch();
    }

    /**修改或添加地址
     * @param string $addressId
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_address($addressId = '',$cartId=0)
    {
        if ($addressId && is_numeric($addressId) && UserAddress::be(['is_del' => 0, 'id' => $addressId, 'uid' => $this->uid])) {
            $addressInfo = UserAddress::find($addressId)->toArray();
        } else {
            $addressInfo = [];
        }
        $addressInfo = json_encode($addressInfo);
        $this->assign(compact('addressInfo','cartId'));
        return $this->fetch();
    }

    /**订单详情
     * @param string $uni
     * @return mixed|void
     */
    public function order($uni = '')
    {
        if (!$uni || !$order = StoreOrder::getUserOrderDetail($this->uid, $uni)) return $this->failed('查询订单不存在!');
        $this->assign([
            'order' => StoreOrder::tidyOrder($order, true)
        ]);
        return $this->fetch();
    }

    public function orderPinkOld($uni = '')
    {
        if (!$uni || !$order = StoreOrder::getUserOrderDetail($this->uid, $uni)) return $this->failed('查询订单不存在!');
        $this->assign([
            'order' => StoreOrder::tidyOrder($order, true)
        ]);
        return $this->fetch('order');
    }

    /**获取订单
     * @param int $type
     * @param int $page
     * @param int $limit
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_order_list($type = -1, $page = 1, $limit = 10)
    {
        return JsonService::successful(StoreOrder::getSpecialOrderList((int)$type, (int)$page, (int)$limit, $this->uid));
    }

    /**我的拼课订单
     * @return mixed
     */
    public function order_list()
    {
        return $this->fetch();
    }

    /**申请退款
     * @param string $order_id
     * @return mixed
     */
    public function refund_apply($order_id='')
    {
        if (!$order_id || !$order = StoreOrder::getUserOrderDetail($this->uid, $order_id)) return $this->failed('查询订单不存在!');
        $this->assign([
            'order' => StoreOrder::tidyOrder($order, true,true),
            'order_id'=>$order_id
        ]);
        return $this->fetch();
    }

    /**评价页面
     * @param string $unique
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function order_reply($unique = '')
    {
        if (!$unique || !StoreOrderCartInfo::be(['unique' => $unique]) || !($cartInfo = StoreOrderCartInfo::where('unique', $unique)->find())) return $this->failed('评价产品不存在!');
        $this->assign(['cartInfo' => $cartInfo]);
        return $this->fetch();
    }

    public function express($uni = '')
    {
        if (!$uni || !($order = StoreOrder::getUserOrderDetail($this->uid, $uni))) return $this->failed('查询订单不存在!');
        if ($order['delivery_type'] != 'express' || !$order['delivery_id']) return $this->failed('该订单不存在快递单号!');
        $cacheName = $uni . $order['delivery_id'];
        $result = CacheService::get($cacheName, null);
        if ($result === null) {
            $result = Express::query($order['delivery_id']);
            if (is_array($result) &&
                isset($result['result']) &&
                isset($result['result']['deliverystatus']) &&
                $result['result']['deliverystatus'] >= 3)
                $cacheTime = 0;
            else
                $cacheTime = 1800;
            CacheService::set($cacheName, $result, $cacheTime);
        }
        $this->assign([
            'order' => $order,
            'express' => $result
        ]);
        return $this->fetch();
    }


    public function commission()
    {
        $uid = (int)Request::instance()->get('uid', 0);
        if (!$uid) return $this->failed('用户不存在!');
        $this->assign(['uid' => $uid]);
        return $this->fetch();
    }

    /**
     * 关于我们
     * @return mixed
     */
    public function about_us()
    {
        $this->assign([
            'content' => get_config_content('about_us'),
            'title' => '关于我们'
        ]);
        return $this->fetch('index/agree');
    }

    public function getUserGoldBill()
    {
        $user_info = $this->userInfo;
        list($page, $limit) = UtilService::getMore([
            ['page', 1],
            ['limit', 20],
        ], $this->request, true);
        $where['uid'] = $user_info['uid'];
        $where['category'] = "gold_num";
        return JsonService::successful(UserBill::getUserGoldBill($where, $page, $limit));
    }


    /**
     * 余额明细
     */
    public function bill_detail(){

        return $this->fetch();
    }
}
