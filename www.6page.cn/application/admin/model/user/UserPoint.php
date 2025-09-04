<?php



namespace app\admin\model\user;

use app\admin\model\user\User;
use app\admin\model\user\UserBill;
use traits\ModelTrait;
use basic\ModelBasic;


class UserPoint extends ModelBasic
{
    use ModelTrait;
    /*
     * 获取积分信息
     * */
    public static function  systemPage($where){
        $model= new UserBill();
        if($where['status']!='')UserBill::where('status',$where['status']);
         if($where['title']!='')UserBill::where('title','like',"%$where[status]%");
        $model->where('category','integral')->select();
        return $model::page($model);
    }


    public static function setWhere($where){
        $model=UserBill::alias('a')->join('__USER__ b','a.uid=b.uid','left')->where('a.category','integral');
        $time['data']='';
        if($where['start_time']!='' && $where['end_time']!=''){
            $time['data']=$where['start_time'].' - '.$where['end_time'];
        }
        $model=self::getModelTime($time,$model,'a.add_time');
        if($where['nickname']!=''){
            $model=$model->where('b.nickname|b.uid','like',$where['nickname']);
        }
        return $model;
    }
}
