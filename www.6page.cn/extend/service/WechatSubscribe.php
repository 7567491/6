<?php



namespace service;

use basic\AuthBasic;

class WechatSubscribe extends AuthBasic
{
    /**
     * 基础访问接口
     * @var string
     */
    const API_OAUTH_GET = 'https://api.weixin.qq.com/sns/userinfo';

    /**GET请求 获取用户信息
     * @param $access_token
     * @param $openId
     * @param string $lang
     * @return mixed
     */
    public static function baseParseGet($access_token,$openId, $lang = 'zh_CN')
    {
        $url=self::API_OAUTH_GET."?access_token=".$access_token."&openid=".$openId."&lang=".$lang;
        $res = HttpService::getRequest($url, [], false, 60);
        return json_decode($res,true);
    }
}
