<?php


namespace app\wap\model\user;


use basic\BaseCheck;
use basic\ModelBasic;
use service\QrcodeService;
use think\Cookie;
use think\Session;
use traits\ModelTrait;
use service\SystemConfigService;

/**h5 用户记录表
 * Class PhoneUser
 * @package app\wap\model\user
 */
class PhoneUser extends ModelBasic
{
    use ModelTrait;

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
        $isfollow = false;
        self::startTrans();
        try {
            if (self::be(['phone' => $phone])) {
                $user = self::where('phone', $phone)->find();
                $user->last_ip = $request->ip();
                $user->last_time = time();
                $userinfo = User::where('uid', $user->uid)->find();
                if (!$userinfo->status) return self::setErrorInfo('账户已被禁止登录');
                $user->save();
            } else {
                $userinfo = User::where(['phone' => $phone, 'is_h5user' => 0])->find();
                if ($userinfo && !$userinfo->status) return self::setErrorInfo('账户已被禁止登录');
                if (!$userinfo) $userinfo = User::set([
                    'nickname' => $phone,
                    'pwd' => md5(123456),
                    'avatar' => '/system/images/user_log.png',
                    'account' => $phone,
                    'phone' => $phone,
                    'is_h5user' => 1,
                ]);
                if (!$userinfo) return self::setErrorInfo('用户信息写入失败', true);
                $user = self::set([
                    'phone' => $phone,
                    'avatar' => '/system/images/user_log.png',
                    'nickname' => $phone,
                    'uid' => $userinfo['uid'],
                    'add_ip' => $request->ip(),
                    'add_time' => time(),
                    'pwd' => md5(123456),
                ]);
                if (!$user) return self::setErrorInfo('手机用户信息写入失败', true);
            }
            $isfollow = $userinfo['is_h5user'] ? false : true;

            Session::delete('login_error_info', 'web');
            $data = [
                'loginUid' => $userinfo['uid'],
                '__login_phone_number' => $userinfo['phone'],
                '__login_phone_num' . $userinfo['uid'] => $userinfo['phone'],
            ];
            $token = BaseCheck::getJWTToken($data);
            Cookie::set('wy-auth', $token, 86400 * 30);
            $userinfo['token'] = $token;

            $res['url'] = SystemConfigService::get('wechat_qrcode');
            $res['id'] = 0;
            self::commit();
            return ['userinfo' => $user, 'url' => $res['url'], 'qcode_id' => $res['id'], 'isfollow' => $isfollow];
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
                $user = self::where('phone', $account)->find();
                $userinfo = User::where(['phone' => $account])->find();
                if (!$userinfo) return self::setErrorInfo('您要找回的账号不存在', true);
                if (!$userinfo->status) return self::setErrorInfo('账户已被禁止登录');
                if($user && $user['pwd']==$pwd || $userinfo['pwd']==$pwd) return self::setErrorInfo('新密码和旧密码重复', true);
                $res1 = User::where(['phone' => $account])->update(['pwd' => $pwd]);
                $res2=true;
                if($user){
                    $res2 = self::where(['phone' => $account])->update(['pwd' => $pwd]);
                }
                $res = $res1 && $res2;
                self::checkTrans($res);
                if ($res) {
                    return true;
                } else {
                    return false;
                }
            } else if($type==1) {
                $userinfo = User::where(['phone' => $account])->find();
                if($userinfo) return self::setErrorInfo('账号已存在', true);
                $userinfo = User::set([
                    'nickname' => $account,
                    'pwd' => $pwd,
                    'avatar' => '/system/images/user_log.png',
                    'account' => $account,
                    'phone' => $account,
                    'is_h5user' => 1,
                ]);
                if (!$userinfo) return self::setErrorInfo('用户信息写入失败', true);
                $user = self::where('phone', $account)->find();
                if($user) return self::setErrorInfo('账号已被使用', true);
                $user = self::set([
                    'phone' => $account,
                    'avatar' => '/system/images/user_log.png',
                    'nickname' => $account,
                    'uid' => $userinfo['uid'],
                    'add_ip' => $request->ip(),
                    'add_time' => time(),
                    'pwd' => $pwd,
                ]);
                if (!$user) return self::setErrorInfo('手机用户信息写入失败', true);
                self::commitTrans();
                return true;
            }
        } catch (\Exception $e) {
            return self::setErrorInfo($e->getMessage());
        }
    }
}
