<?php


namespace app\web\model\live;

use basic\ModelBasic;
use traits\ModelTrait;

/**直播间用户表
 * Class LivePlayback
 * @package app\web\model\live
 */
class LivePlayback extends ModelBasic
{
    use ModelTrait;

    /**
     *  设置查询条件
     * */
    public static function setUserWhere($where,$model = null)
    {
        $model = new self();
        if($where['start_time'] && $where['end_time']) $model = $model->where("add_time",'between',[strtotime($where['start_time']),strtotime($where['end_time'])]);
        $model = $model->where(['is_show'=>1,'is_del'=>0]);
        return $model->order("sort desc,add_time desc")->where("stream_name",$where['stream_name']);
    }

    /**
     * 查询直播间回放列表
     * @param array $where
     * */
    public static function getLivePlaybackList($where)
    {
        $data = self::setUserWhere($where)->page((int)$where['page'],(int)$where['limit'])->select();
        $data = count($data) ? $data->toArray() : [];
        $count = self::setUserWhere($where)->count();
        return compact('data','count');
    }

}
