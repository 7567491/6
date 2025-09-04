<?php



namespace behavior\wechat;


use app\admin\model\wechat\WechatReply;
use app\wap\model\user\PhoneUser;
use app\wap\model\user\User;
use app\wap\model\user\WechatUser;
use think\Cache;

class QrcodeEventBehavior
{

    public static function wechatQrcodeSpread($qrInfo, $message)
    {
        try {
            $spreadUid = $qrInfo['third_id'];
            $uid = WechatUser::openidToUid($message->FromUserName, true);
            if ($spreadUid == $uid) return '自己不能推荐自己';
            $userInfo = User::getUserInfo($uid);
            if ($userInfo['spread_uid']) return '已有推荐人!';
            if (User::setSpreadUid($userInfo['uid'], $spreadUid))
                return WechatReply::reply('subscribe');
            else
                return '绑定推荐人失败!';
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public static function wechatQrcodeBinding($qrInfo, $message)
    {
        $bindingPhoneUid = $qrInfo['third_id'];
        $uid = WechatUser::openidToUid($message->FromUserName, true);
        $userInfo = User::getUserInfo($uid);
        if ($userInfo['phone']) return '您已绑定手机号码,需要更换手机号码去[个人中心]更换绑定手机号';
        $bindingPhone = PhoneUser::UidToPhone($bindingPhoneUid);
        if (!$bindingPhone) return '绑定失败,手机号码不存在';
        if (User::setUserRelationInfos($bindingPhone, $bindingPhoneUid, $uid, true, $qrInfo['id']))
            return '恭喜您,手机号码[' . $bindingPhone . ']绑定成功,';
        else
            return User::getErrorInfo();
    }

    // 关注后登录
    public static function wechatQrcodeLoginSubscribe($message, $eventKey)
    {
        $openid = $message->FromUserName;
        //开始登录逻辑
        // 判断是否有用户，如果有用户就存入openid
        if(WechatUser::be(['openid' => $openid])) {
            // 去除key前面的 wechat_login__并生成与前端对应的key
            $frontKey = str_replace('wechat_login__', 'wechat_front__', $eventKey);
            Cache::store('redis')->set($frontKey, $openid, 3600 * 24);
        }
        return '';
    }

    // 扫码直接登录
    public static function wechatQrcodeLoginScan($message, $eventKey)
    {
        $openid = $message->FromUserName;
        // 判断是否有用户，如果有用户就存入openid
        if(WechatUser::be(['openid' => $openid])) {
            // 去除key前面的 wechat_login__并生成与前端对应的key
            $frontKey = str_replace('wechat_login__', 'wechat_front__', $eventKey);
            Cache::store('redis')->set($frontKey, $openid, 3600 * 24);
        }
        return '';
    }
}
