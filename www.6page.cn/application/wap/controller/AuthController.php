<?php



namespace app\wap\controller;

use app\admin\model\wechat\WechatQrcode;
use app\wap\model\user\User;
use app\wap\model\user\WechatUser;
use basic\WapBasic;
use service\JsonService;
use service\SystemConfigService;
use service\UtilService;
use think\Cache;
use think\cache\driver\Redis;
use think\Cookie;
use think\Session;
use think\Url;
use app\wap\model\user\MemberShip;
use service\GroupDataService;

class AuthController extends WapBasic
{
    /**
     * 用户ID
     * @var int
     */
    protected $uid = 0;
    /**
     * 用户信息
     * @var
     */
    protected $userInfo;

    protected $phone;

    protected $force_binding;

    protected $isWechat = false;

    protected $redisModel;

    protected $subjectUrl='';

    protected function _initialize()
    {
        parent::_initialize();
        $pc_on_display = SystemConfigService::get('pc_on_display');
        if(!request()->isMobile() && is_dir(APP_PATH.'web') && $pc_on_display){
            // 获取当前controller对应的方法
            $action = $this->request->action();
            // 获取url所有的参数
            $param = $this->request->param();
            if ($action == 'details') {
                return $this->redirect(url('/view-course').'?'.http_build_query($param));
            }
            if ($action == 'single_details') {
                return $this->redirect(url('/single-course').'?'.http_build_query($param));
            }
            return $this->redirect(url('/'));
        }
        try {
            $this->redisModel = new Redis();
        } catch (\Exception $e) {
            parent::serRedisPwd($e->getMessage());
        }
        $this->isWechat = UtilService::isWechatBrowser();
        $spread_uid = $this->request->get('spread_uid', 0);
        $NoWechantVisitWhite = $this->NoWechantVisitWhite();
        $subscribe = false;
        $site_url = SystemConfigService::get('site_url');
        $this->subjectUrl=getUrlToDomain();
        try {
            $uid = User::getActiveUid();
            if (!empty($uid)) {
                $this->userInfo = User::getUserInfo($uid);
                if($this->isWechat){
                    if($this->userInfo['nickname']=='' && $this->userInfo['avatar']=='' || $this->userInfo['nickname']=='' && $this->userInfo['avatar']=='/system/images/user_log.png'){
                        $url = $this->request->url(true);
                        if (!$this->request->isAjax()){
                            return $this->redirect(Url::build('Login/index', ['spread_uid' => $spread_uid]) . '?ref=' . base64_encode(htmlspecialchars($url)));
                        }
                    }
                }
                MemberShip::memberExpiration($uid);
                if($spread_uid) $spreadUserInfo = User::getUserInfo($spread_uid);
                $this->uid = $this->userInfo['uid'];
                $this->phone = User::getLogPhone($uid);
                //绑定推广人
                if ($spread_uid && $spreadUserInfo && $this->uid != $spread_uid && $spreadUserInfo['spread_uid']!=$this->uid && $this->userInfo['spread_uid'] != $spread_uid  && !$this->userInfo['spread_uid']) {
                    $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
                    if ($storeBrokerageStatu == 1) {
                        if($spreadUserInfo['is_promoter']) User::edit(['spread_uid' => $spread_uid], $this->uid, 'uid');
                    }else{
                        User::edit(['spread_uid' => $spread_uid], $this->uid, 'uid');
                    }
                }
                if (!isset($this->userInfo['uid'])) $this->userInfo['uid'] = 0;
                if (!isset($this->userInfo['is_promoter'])) $this->userInfo['is_promoter'] = 0;
                if (!isset($this->userInfo['avatar'])) $this->userInfo['avatar'] = '';
                if (!isset($this->userInfo['nickname'])) $this->userInfo['nickname'] = '';
                //是否关注公众号
                $subscribe = WechatUser::where('uid', $this->uid)->value('subscribe');
                if (!$NoWechantVisitWhite) {
                    if (!$this->userInfo || !isset($this->uid)) return $this->failed('读取用户信息失败!');
                    if (!$this->userInfo['status']) return $this->failed('已被禁止登陆!');
                }
            }
        } catch (\Exception $e) {
            Session::clear('wap');
            Cookie::delete('wy-auth');
            $url = $this->request->url(true);
            if (!$NoWechantVisitWhite) {
                if ($this->request->isAjax())
                    return JsonService::fail('请先登录');
                else
                    return $this->redirect(Url::build('/m/login') . '?spread_uid='. $spread_uid .'&ref=' . base64_encode(htmlspecialchars($url)));
            }
        }
//        if (Cache::has('__SYSTEM__')) {
//            $overallShareWechat = Cache::get('__SYSTEM__');
//        } else {
//            $overallShareWechat = SystemConfigService::more(['wechat_share_img', 'wechat_share_title', 'wechat_share_synopsis']);
//            Cache::set('__SYSTEM__', $overallShareWechat, 800);
//        }

        $overallShareWechat = SystemConfigService::more(['wechat_share_img', 'wechat_share_title', 'wechat_share_synopsis']);

        $codeUrl = SystemConfigService::get('wechat_qrcode');
        $balance_switch=SystemConfigService::get('balance_switch');//余额开关
        $alipay_switch=SystemConfigService::get('alipay_switch');//支付宝开关
        $h5_wechat_payment_switch=SystemConfigService::get('h5_wechat_payment_switch');//h5端微信支付开关
        $official_account_switch=SystemConfigService::get('official_account_switch');//关注公众号开关
        // $this->force_binding=SystemConfigService::get('force_binding');//微信端是否强制绑定手机号
        $this->force_binding=1;
        // 如果强制绑定手机号并且已登录，且手机号没有填写，则跳转到绑定手机号页面
        // 已经是个人中心页就不需要重定向了
        $theController = $this->request->controller();
        $theAction = $this->request->action();
        if($this->force_binding == 1 && $this->uid && !$this->userInfo['phone'] && $theController != 'My' && $theAction != 'code'){
            if ($this->request->isAjax()) {
                return JsonService::fail('请先绑定手机号', ['path' => Url::build('wap/my/save_phone', [], true, true)], 302);
            } else {
                return $this->redirect(Url::build('wap/my/save_phone'));
            }
        }
        $if_bind_tip = $this->force_binding == 1 && $this->uid && !$this->userInfo['phone'];
        $now_money=isset($this->userInfo['now_money']) ? $this->userInfo['now_money'] : 0;
        $watermark = SystemConfigService::get('video_watermark');
        $video_bar = SystemConfigService::get('video_bar');
        $this->assign([
//            'm_home_url' => url('/m'),
            'site_url' => $site_url,
            'callback_url'=>$site_url.'/wap/callback/pay_success_synchro',
            'code_url' => str_replace("\\", "/", $codeUrl),
            'is_yue' => $balance_switch,
            'is_alipay' => $alipay_switch,
            'is_h5_wechat_payment_switch' => $h5_wechat_payment_switch,
            'is_official_account_switch' => $official_account_switch,
            'subscribe' => $subscribe,
            'subscribeQrcode' => str_replace("\\", "/", SystemConfigService::get('wechat_qrcode')),
            'userInfo' => $this->userInfo,
            'uid' => isset($this->userInfo['uid']) ? $this->userInfo['uid'] : 0,
            'now_money' => $now_money,
            'phone' => $this->phone,
            'isWechat' => $this->isWechat,
            'overallShareWechat' => json_encode($overallShareWechat),
            'Auth_site_name' => SystemConfigService::get('site_name'),
            'website_statistics' => SystemConfigService::get('website_statistics'),
            'menus'=>GroupDataService::getData('bottom_navigation'),
            'is_close_watermark' => in_array("none", $watermark),
            'is_site_watermark' => in_array("site", $watermark),
            'is_account_watermark' => in_array("account", $watermark),
            'video_bar' => $video_bar ? 1 : 0,
            'if_bind_tip' => $if_bind_tip,
        ]);
    }

    /**
     * 检查白名单控制器方法 存在带名单返回 true 不存在则进行登录
     * @return bool
     */
    protected function NoWechantVisitWhite()
    {
        list($module, $controller, $action, $className) = $this->getCurrentController();
        if (method_exists($className, 'WhiteList')) {
            $whitelist = $className::WhiteList();
            if (!is_array($whitelist)) return false;
            foreach ($whitelist as $item) {
                if (strtolower($module . '\\' . $controller . '\\' . $item) == strtolower($module . '\\' . $controller . '\\' . $action)) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * 获取当前的控制器名,模块名,方法名,类名并返回
     * @return array
     */
    protected function getCurrentController()
    {
        $module = $this->request->module();
        $controller = $this->request->controller();
        $action = $this->request->action();
        if (strstr($controller, '.'))
            $controllerv1 = str_replace('.', '\\', $controller);
        else
            $controllerv1 = $controller;
        $className = 'app\\' . $module . '\\controller\\' . $controllerv1;
        return [$module, $controller, $action, $className];
    }

}
