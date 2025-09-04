<?php

namespace app\wechat\controller;

use service\WechatService;


/**
 * 微信服务器  验证控制器
 * Class Wechat
 * @package app\wap\controller
 */
class Index
{

    /**
     * 微信服务器验证
     */
    public function verify()
    {
        ob_clean();
        WechatService::serve();
    }

    /**
     *   支付  异步回调
     */
    public function notify()
    {
        WechatService::handleNotify();
    }
}


