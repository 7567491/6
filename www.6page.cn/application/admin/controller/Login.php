<?php


namespace app\admin\controller;

use app\admin\model\system\SystemAdmin;
use app\admin\model\system\SystemConfig;
use basic\SystemBasic;
use service\CacheService;
use service\UtilService;
use think\Request;
use think\Response;
use think\Session;
use think\Url;

/**
 * 登录验证控制器
 * Class Login
 * @package app\admin\controller
 */
class Login extends SystemBasic
{
    public function index()
    {
        $this->assign([
            'site_name' => SystemConfig::getValue("site_name")
        ]);
        return $this->fetch();
    }

    /**
     * 登录验证 + 验证码验证
     */
    public function verify(Request $request)
    {
        if (!$request->isPost()) return ['code'=>4];
        list($account, $pwd, $verify) = UtilService::postMore([
            'account', 'pwd', 'verify'
        ], $request, true);
        //检验验证码
        if (!captcha_check($verify)) return ['code'=>2];
        $error = Session::get('login_error') ? Session::get('login_error') : ['num' => 0, 'time' => time()];
        if ($error['num'] >= 5 && time() - $error['time'] < 300){
            return ['code'=>3];
        }
        //检验帐号密码
        $res = SystemAdmin::login($account, $pwd);
        if ($res) {
            Session::set('login_error', null);
            return ['code'=>1];
        } else {
            $error['num'] += 1;
            $error['time'] = time();
            Session::set('login_error', $error);
            return ['code'=>0];
        }
    }

    public function captcha()
    {
        ob_clean();
        $captcha = new \think\captcha\Captcha([
            'codeSet' => '0123456789',
            'length' => 4,
            'fontSize' => 30
        ]);
        return $captcha->entry();
    }

    /**
     * 退出登陆
     */
    public function logout()
    {
        SystemAdmin::clearLoginInfo();
        $this->redirect('Login/index');
    }
}
