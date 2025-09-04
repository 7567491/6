<?php



namespace app\web\controller;

use app\wap\model\user\SmsCode;
use app\wap\model\user\WechatUser;
use app\web\model\user\PhoneUser;
use app\web\model\user\User;
use basic\BaseCheck;
use basic\WapBasic;
use service\SystemConfigService;
use service\UtilService;
use service\JsonService;
use service\WechatService;
use think\Cache;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;

/**pc端登录控制器
 * Class Login
 * @package app\web\controller
 */
class Login extends WapBasic
{
    public function index($spread_uid = 0)
    {
        $login_types = SystemConfigService::get('login_types');
        $site_favicon = SystemConfigService::get('site_favicon');
        $this->assign([
            'site_name' => SystemConfigService::get('site_name'),
            'site_url' => SystemConfigService::get('site_url'),
            'home_logo' => SystemConfigService::get('home_logo'),
            'seo_keywords' => SystemConfigService::get('site_keywords'),
            'seo_description' => SystemConfigService::get('site_description'),
            'code_url ' => SystemConfigService::get('wechat_qrcode'),
            'spread_uid' => $spread_uid,
            // 是否开启滑块验证码
            'slide_captcha' => SystemConfigService::get('slide_captcha'),
            'slide_captcha_api' => SystemConfigService::get('site_url'),
            'login_types' => $login_types ? $login_types : 1,
            'site_favicon' => $site_favicon
        ]);
        return $this->fetch();
    }
    /**
     * 短信登陆/注册
     * @param Request $request
     */
    public function phone_check(Request $request)
    {
        list($phone, $code) = UtilService::postMore([
            ['phone', ''],
            ['code', ''],
        ], $request, true);
        if (!$phone || !$code) return JsonService::fail('请输入登录账号');
        if (!$code) return JsonService::fail('请输入验证码');
        $code=md5('is_phone_code'.$code);
        if (!SmsCode::CheckCode($phone, $code)) return JsonService::fail('验证码验证失败');
        SmsCode::setCodeInvalid($phone, $code);
        if (($info = User::UserLogIn($phone, $request)) !== false)
            return JsonService::successful('登录成功', $info);
        else
            return JsonService::fail(User::getErrorInfo('登录失败'));
    }

    /**账号密码登录
     * @param Request $request
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check(Request $request)
    {
        list($account, $pwd) = UtilService::postMore(['account', 'pwd'], $request, true);
        if (!$account || !$pwd) return JsonService::fail('请输入登录账号');
        if (!User::be(['phone' => $account])) return JsonService::fail('登陆账号不存在!');
        $phoneInfo = User::where('phone', $account)->find();
        $errorInfo = Session::get('login_error_info', 'web') ?: ['num' => 0];
        $now = time();
        if ($errorInfo['num'] > 5 && $errorInfo['time'] < ($now - 900))
            return JsonService::fail('错误次数过多,请稍候再试!');
        if ($phoneInfo['pwd'] != $pwd) {
            Session::set('login_error_info', ['num' => $errorInfo['num'] + 1, 'time' => $now], 'web');
            return JsonService::fail('账号或密码输入错误!');
        }
        if (!$phoneInfo['status']) return JsonService::fail('账号已被锁定,无法登陆!');
        $userinfo = $phoneInfo;
        if(!$userinfo)return JsonService::fail('账号异常!');
        $this->_logout();
        $phoneInfo['last_time'] = time();
        $phoneInfo['last_ip'] = $request->ip();
        $phoneInfo->save();
        unset($userinfo['pwd']);
        $token = $this->BaseCheckPwd($userinfo);
        $userinfo['token'] = $token;
        return JsonService::successful('登录成功', $userinfo);
    }

    public function BaseCheckPwd($userinfo)
    {
        Session::delete('login_error_info', 'web');
        $data = [
            'loginUid' => $userinfo['uid'],
            '__login_phone_number' => $userinfo['phone'],
            '__login_phone_num' . $userinfo['uid'] => $userinfo['phone'],
        ];
        $token = BaseCheck::getJWTToken($data);
        Cookie::set('wy-auth', $token, 86400 * 30);
        return $token;
    }

    /**账号密码注册/找回密码
     * @param Request $request
     * @param $account 账号
     * @param $pwd 密码
     * @param $code 验证码
     * @param $type 1=注册 2=找回密码
     */
    public function register(Request $request)
    {
        list($account, $pwd, $code, $type) = UtilService::postMore([
                ['account',''],
                ['pwd',''],
                ['code',''],
                ['type',1]
            ], $request, true);
        if (!$account || !$pwd || !$code) return JsonService::fail('参数有误！');
        if (!$code) return JsonService::fail('请输入验证码');
        $code=md5('is_phone_code'.$code);
        if (!SmsCode::CheckCode($account, $code)) return JsonService::fail('验证码验证失败');
        SmsCode::setCodeInvalid($account, $code);
        $msg=$type==1 ? '注册' : '找回密码';
        if (($info = User::userRegister($account,$pwd,$type,$request)) !== false)
            return JsonService::successful($msg.'成功');
        else
            return JsonService::fail(User::getErrorInfo(User::getErrorInfo($msg.'失败')));
    }

    /**
     * 公众号扫码登录/注册
     * @param Request $request
     */
    public function wechat_check(Request $request)
    {

    }

    // 微信公众号登录获取二维码
    public function wechat_get_login_qrcode()
    {
        $qrcode = WechatService::qrcodeService();
        $expire = 3600 * 24;
        $key = md5(uniqid(mt_rand(), true));
        $sence_key = 'wechat_login__' . $key;
        $data  = $qrcode->temporary($sence_key, $expire)->toArray();
        $url   = $qrcode->url($data['ticket']);
        $data['sence_key'] = $key;
        Cache::store('redis')->set($sence_key, $url, $expire);
        return JsonService::successful('成功', $data);
    }

    // 检查登录状态
    public function check_wechat_login_status(Request $request) {
        $sence_key = $request->param('sence_key');
        $flag = 'wechat_front__' . $sence_key;
        if (!$flag) {
            return JsonService::fail('参数错误');
        }
        // 根据微信标识在缓存中获取需要登录用户的 UID
        $openid = Cache::store('redis')->get($flag);
        if ($openid === false) {
            return JsonService::successful('等待扫码', ['status' => 'waiting']);
        }
        //开始登录逻辑
        //如果用户不存在，可能是已经通过其他渠道关注了公众号，先注册
        if (!WechatUser::be(['openid' => $openid])) return JsonService::fail('登陆账号不存在!');

        $wechatInfo = WechatUser::where('openid', $openid)->find();
        $errorInfo = Session::get('login_error_info', 'web') ?: ['num' => 0];
        $now = time();
        if ($errorInfo['num'] > 5 && $errorInfo['time'] < ($now - 900))
            return JsonService::fail('错误次数过多,请稍候再试!');

        $userinfo = User::where('uid', $wechatInfo['uid'])->find();
        if(!$userinfo)return JsonService::fail('账号异常!');

        $this->_logout();
        $wechatInfo['last_time'] = time();
        $wechatInfo['last_ip'] = $request->ip();
        $wechatInfo->save();
        unset($userinfo['pwd']);

        Session::delete('login_error_info', 'web');
        $data = [
            'loginUid' => $userinfo['uid'],
            '__login_openid_number' => $openid,
            '__login_openid_num' . $userinfo['uid'] => $openid,
        ];
        $token = BaseCheck::getJWTToken($data);
        Cookie::set('wy-auth', $token, 86400 * 30);
        $userinfo['token'] = $token;

        // 删除redis缓存
        Cache::store('redis')->rm($flag);
        Cache::store('redis')->rm('wechat_login__'.$sence_key);
        return JsonService::successful('登录成功', $userinfo);
    }

    protected function generalString($length)
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
     * 退出登陆
     */
    public function logout()
    {
        $this->_logout();
        $this->successful('退出登陆成功', Url::build('index'));
    }

    /**
     * 清除缓存
     */
    private function _logout()
    {
        Session::clear('web');
        Cookie::delete('wy-auth');
    }

}
