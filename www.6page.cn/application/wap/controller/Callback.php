<?php


namespace app\wap\controller;

use think\Request;


class Callback extends AuthController
{
    /**
     * @param $type 1=课程 2=商品 3=报名 4=金币充值 5=会员 6=考试 7=精简课 8=资料
     * @return mixed
     */
    public function pay_success_synchro($type=0,$id=0)
    {
        $this->assign(['type'=>$type,'id'=>$id]);
        return $this->fetch();
    }

}


