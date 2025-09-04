<?php



namespace app\wap\model\activity;

use traits\ModelTrait;
use basic\ModelBasic;

class EventPrice extends ModelBasic
{
    use ModelTrait;

    /**
     *获取 活动人数及对应价格列表
     */
    public static function eventPriceList($id=0)
    {
        $list = self::where(['event_id'=>$id])->order('sort DESC,id ASC')->select();
        return count($list) > 0 ? $list->toArray() : [];
    }

    /**
     * 获取单个最低价格
     */
    public static function getminEventPrice($id=0)
    {
      return  self::where(['event_id'=>$id])->order('event_number ASC')->find();
    }
}
