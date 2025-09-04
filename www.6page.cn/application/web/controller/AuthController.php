<?php



namespace app\web\controller;

use app\web\model\recommend\WebRecommend;
use app\web\model\special\Special as SpecialModel;
use app\web\model\special\SpecialSubject;
use app\web\model\user\Search;
use app\web\model\user\User;
use basic\WapBasic;
use service\GroupDataService;
use service\JsonService;
use service\SystemConfigService;
use app\web\model\user\MemberShip;
use service\UtilService;
use think\Cache;
use think\cache\driver\Redis;
use think\Cookie;
use think\Session;
use think\Url;

/**全局调用控制器
 * Class AuthController
 * @package app\web\controller
 */
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

    protected $openid;

    protected $redisModel;

    protected $subjectUrl='';

    protected function _initialize()
    {
        parent::_initialize();
        $pc_on_display = SystemConfigService::get('pc_on_display');
        $spread_uid = $this->request->get('spread_uid', 0);
        if(!request()->isAjax() && request()->isMobile() || $pc_on_display==0){
            // 获取当前controller对应的方法
            $action = $this->request->action();
            $controller = $this->request->controller();
            // 获取url所有的参数
            $param = $this->request->param();
            if ($controller == 'Special' && $action == 'details') {
                return $this->redirect(url('/m/view-course').'?'.http_build_query($param));
            }

            if ($controller == 'Material' && $action == 'details') {
                return $this->redirect(url('/m/view-virtual').'?'.http_build_query($param));
            }

            if ($action == 'single_details') {
                return $this->redirect(url('/m/single-course').'?'.http_build_query($param));
            }
            return $this->redirect(url('/m'));
        }
        try {
            $this->redisModel = new Redis();
        } catch (\Exception $e) {
            parent::serRedisPwd($e->getMessage());
        }
        $NoWechantVisitWhite = $this->NoWechantVisitWhite();
        $this->subjectUrl = getUrlToDomain();
        try {
            $uid = User::getActiveUid();
            if (!empty($uid)) {
                $this->userInfo = User::getUserInfo($uid);
                MemberShip::memberExpiration($uid);
                if($spread_uid) $spreadUserInfo = User::getUserInfo($spread_uid);
                $this->uid = $this->userInfo['uid'];
                $this->phone = User::getLogPhone($uid);
                $this->openid = User::getLogOpenid($uid);
                //绑定推广人
                if ($spread_uid && $spreadUserInfo && $this->uid != $spread_uid && $spreadUserInfo['spread_uid']!=$this->uid && $this->userInfo['spread_uid'] != $spread_uid  && !$this->userInfo['spread_uid']) {
                    $storeBrokerageStatu = SystemConfigService::get('store_brokerage_statu') ?: 1;//获取后台分销类型
                    if ($storeBrokerageStatu == 1) {
                        if($spreadUserInfo['is_promoter']) User::edit(['spread_uid' => $spread_uid], $this->uid, 'uid');
                    }else{
                        User::edit(['spread_uid' => $spread_uid], $this->uid, 'uid');
                    }
                }
                $this->account = $this->userInfo['account'];
                if (!isset($this->userInfo['uid'])) $this->userInfo['uid'] = 0;
                if (!isset($this->userInfo['avatar'])) $this->userInfo['avatar'] = '';
                if (!isset($this->userInfo['nickname'])) $this->userInfo['nickname'] = '';

                if (!$NoWechantVisitWhite) {
                    if (!$this->userInfo || !isset($this->uid)) return $this->failed('读取用户信息失败!');
                    if (!$this->userInfo['status']) return $this->failed('已被禁止登陆!');
                }
            }
        } catch (\Exception $e) {
            Cookie::delete('wy-auth');
            if (!$NoWechantVisitWhite) {
                if ($this->request->isAjax())
                    return JsonService::fail('请先登录');
                else
                    return $this->redirect(Url::build('/login', ['spread_uid' => $spread_uid]));
            }
        }
        $codeUrl = SystemConfigService::get('wechat_qrcode');
        $balance_switch=SystemConfigService::get('balance_switch');//余额开关
        $is_alipay=SystemConfigService::get('pc_alipay_code_scanning_payment_switch');//pc端支付宝扫码开关
        $is_wechat=SystemConfigService::get('pc_wechat_code_scanning_payment_switch');//pc端微信扫码支付开关
        $seo_keywords = SystemConfigService::get('site_keywords');//SEO关键词
        $seo_description = SystemConfigService::get('site_description');//SEO描述

        $data = [];
        $data['site_name'] = SystemConfigService::get('site_name');//网站名称
        $data['home_logo'] = SystemConfigService::get('home_pc_logo');//pc首页图标
        $data['site_phone'] = SystemConfigService::get('site_phone');//联系电话
        $data['company_address'] = SystemConfigService::get('company_address');//公司地址
        $data['full_name_the_company'] = SystemConfigService::get('full_name_the_company');//公司全称
        $data['friendly_link'] = SystemConfigService::get('friendly_link');//友情链接
        $data['pc_footer_list']=GroupDataService::getData('pc_end_bottom_display',4);//pc端底部展示
        $data['beian'] = SystemConfigService::get('site_beian');//网站备案信息
        $data['host_search'] = Search::getHostSearch();
        $data['site_url'] = SystemConfigService::get('site_url');
        $data['pc_customer_service'] = SystemConfigService::get('pc_customer_service_configuration');
        $data['service_url'] = SystemConfigService::get('service_url');
        $data['kefu_token'] = SystemConfigService::get('kefu_token');
        $data['kefu_interval'] = SystemConfigService::get('kefu_interval');
        $data['site_service_phone'] = SystemConfigService::get('site_service_phone');
        $data['customer_qrcode'] = SystemConfigService::get('customer_qrcode');
        $cateogry = SpecialSubject::with('children')->where(['is_show' => 1, 'is_del' => 0])->order('sort desc,id desc')->where('grade_id', 0)->select();
        $cateogry = count($cateogry) > 0 ? $cateogry->toArray() : [];
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        foreach ($cateogry as $key => &$item) {
            $cateId = SpecialSubject::subjectId($item['id']);
            $item['list'] = SpecialModel::cate_special_recommen_list($is_member, $cateId);
        }
        $data['cate'] = $cateogry;
        $watermark = SystemConfigService::get('video_watermark');
        $slide_captcha = SystemConfigService::get('slide_captcha');
        $video_bar = SystemConfigService::get('video_bar');

        $site_favicon = SystemConfigService::get('site_favicon');

        // $this->force_binding=SystemConfigService::get('force_binding');//微信端是否强制绑定手机号
        $this->force_binding=1;
        // 如果强制绑定手机号并且已登录，且手机号没有填写，则跳转到绑定手机号页面
        // 已经是个人中心页就不需要重定向了
        $theController = $this->request->controller();
        if($this->force_binding == 1 && $this->uid && !$this->userInfo['phone'] && $theController != 'My' && !$this->request->isAjax()){
            return $this->redirect(Url::build('/my').'?page=account');
        }
        $if_bind_tip = $this->force_binding == 1 && $this->uid && !$this->userInfo['phone'];
        $login_types = SystemConfigService::get('login_types');
        $this->assign([
            'is_yue' => $balance_switch,
            'is_alipay' => $is_alipay,
            'code_url' => str_replace("\\", "/", $codeUrl),
            'is_wechat' => $is_wechat,
            'phone' => $this->phone,
            'openid' => $this->openid,
            'userInfo' => $this->userInfo,
            'seo_keywords' => $seo_keywords,
            'seo_description' => $seo_description,
            'now_money' => $this->userInfo && $this->userInfo['now_money'] ? $this->userInfo['now_money'] : 0,
            'Auth_site_name' => SystemConfigService::get('site_name'),
            'navigation' => WebRecommend::getWebRecommend(),
            'public_data' => $data,
            'website_statistics' => SystemConfigService::get('website_statistics'),
            'fxchat_value' => 1,
            'is_close_watermark' => in_array("none", $watermark),
            'is_site_watermark' => in_array("site", $watermark),
            'is_account_watermark' => in_array("account", $watermark),
            // 是否开启微信登录
            'wechat_login' => SystemConfigService::get('wechat_login'),
            // 是否开启滑块验证码
            'slide_captcha' => $slide_captcha ? 1 : 0,
            'video_bar' => $video_bar ? 1 : 0,
            'slide_captcha_api' => $data['site_url'],
            'if_bind_tip' => $if_bind_tip ? 1 : 0,
            'site_favicon' => $site_favicon,
            'login_types' => $login_types ? $login_types : 1,
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
