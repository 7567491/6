<?php


namespace app\admin\controller\wechat;

use app\admin\controller\AuthController;
use app\admin\model\wechat\WechatMessage as MessageModel;

/**
 * 用户扫码点击事件
 * Class WechatMessage
 * @package app\admin\controller\wechat
 */
class WechatMessage extends AuthController
{

    /**
     * 显示操作记录
     */
    public function index()
    {
        $where = parent::getMore([
            ['nickname', ''],
            ['type', ''],
            ['data', ''],
        ], $this->request);
        $this->assign('where', $where);
        $this->assign('mold', MessageModel::$mold);
        $this->assign(MessageModel::systemPage($where));
        return $this->fetch();
    }


}

