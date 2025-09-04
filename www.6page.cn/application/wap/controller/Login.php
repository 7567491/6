<?php
namespace app\wap\controller;

use app\wap\model\user\SmsCode;
use app\wap\model\user\PhoneUser;
use app\wap\model\user\User;
use app\wap\model\user\WechatUser;
use basic\BaseCheck;
use basic\WapBasic;
use service\SystemConfigService;
use service\UtilService;
use service\JsonService;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;

/**移动端登录控制器
 * Class Login
 * @package app\wap\controller
 */
class Login extends WapBasic
{
    public function index($ref = '', $spread_uid = 0)
    {
        Cookie::set('is_bg', 1);
        $ref && $ref = htmlspecialchars_decode(base64_decode($ref));
        $site_favicon = SystemConfigService::get('site_favicon');
        $login_types = SystemConfigService::get('login_types');
        $this->assign([
            'ref' => $ref,
            'Auth_site_name' => SystemConfigService::get('site_name'),
            'home_logo' => str_replace("\\", "/", SystemConfigService::get('home_logo')),
            'site_favicon' => $site_favicon,
            'login_types' => $login_types ? $login_types : 1,
        ]);
        return $this->fetch();
    }

    public function wechatLogin($ref = '', $spread_uid = 0)
    {
        Cookie::set('is_bg', 1);
        $ref && $ref = htmlspecialchars_decode(base64_decode($ref));
        if (UtilService::isWechatBrowser()) {
            $this->_logout();
            $openid = $this->oauth($spread_uid);
            // update设为true，更新用户缓存
            $uid = WechatUser::openidToUid($openid, true);
            $data = [
                'loginUid' => $uid,
                '__login_openid_number' => $openid,
                '__login_openid_num' . $uid => $openid,
            ];
            $token = BaseCheck::getJWTToken($data);
            Cookie::set('wy-auth', $token, 86400 * 30);
            Cookie::delete('_oen');
            exit($this->redirect(empty($ref) ? $this->mHomeUrl : $ref));
        } else {
            echo '请在微信中打开';
        }
    }

    /**
     * 短信登陆
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

        Session::delete('login_error_info', 'wap');
        $data = [
            'loginUid' => $userinfo['uid'],
            '__login_phone_number' => $userinfo['phone'],
            '__login_phone_num' . $userinfo['uid'] => $userinfo['phone'],
        ];
        $token = BaseCheck::getJWTToken($data);
        Cookie::set('wy-auth', $token, 86400 * 30);
        $userinfo['token'] = $token;

        Session::delete('login_error_info', 'wap');
        $qrcode_url = SystemConfigService::get('wechat_qrcode');
        $info=['userinfo' => $userinfo, 'url' => $qrcode_url, 'qcode_id' => 0, 'isfollow' => false];
        return JsonService::successful('登录成功', $info);
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
     * 退出登陆
     */
    public function logout()
    {
        $this->_logout();
        $this->successful('退出登陆成功', $this->mHomeUrl);
    }
    /**
     * 清除缓存
     */
    private function _logout()
    {
        Session::clear('wap');
        Cookie::delete('is_login');
        Cookie::delete('__login_phone');
    }

}
