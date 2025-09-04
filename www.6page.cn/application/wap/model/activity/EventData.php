<?php



namespace app\wap\model\activity;

use traits\ModelTrait;
use basic\ModelBasic;

class EventData extends ModelBasic
{
    use ModelTrait;

    /**
     * 活动资料列表
     */
    public static function eventDataList($id=0)
    {
        $list = self::where(['event_id'=>$id])->order('sort DESC,id ASC')->select();
        return count($list) > 0 ? $list->toArray() : [];
    }

}
