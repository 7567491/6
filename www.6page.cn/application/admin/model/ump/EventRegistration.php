<?php



namespace app\admin\model\ump;

use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

class EventRegistration extends ModelBasic
{
    use ModelTrait;


    public static function systemPage($where = array()){
        $model = self::setWherePage(self::setWhere($where));
        $model = $model->order('add_time DESC');
        $list = $model ->page((int)$where['page'], (int)$where['limit'])->select()->each(function ($item){
            $item['address']=$item['province'].$item['city'].$item['district'].$item['detail'];
        });
        $count = self::setWherePage(self::setWhere($where))->count();
        return ['count' => $count, 'data' => $list];
    }
    /**
      * 设置搜索条件
      *
      */
    public static function setWhere($where)
    {
        $model=new self;
        if ($where['title'] != '') {
            $model = $model->where('title','like',"%$where[title]%");
        }
        $model = $model->where('is_del',0);
        return $model;
    }

    /**删除
     * @param $id
     * @return bool
     */
    public static function delArticleCategory($id){
        $data['is_del']=1;
        return self::edit($data,$id);
    }

    /**获取活动
     * @param $id
     */
    public static function eventRegistrationOne($id)
    {
        $event=self::where('id',$id)->find();
        if(!$event) return [];
        $event['signup_start_time'] =date('Y-m-d H:i:s',$event['signup_start_time']);
        $event['signup_end_time'] =date('Y-m-d H:i:s',$event['signup_end_time']);
        $event['start_time'] =date('Y-m-d H:i:s',$event['start_time']);
        $event['end_time'] =date('Y-m-d H:i:s',$event['end_time']);
        $event['activity_rules'] = htmlspecialchars_decode($event['activity_rules']);
        $event['content'] = htmlspecialchars_decode($event['content']);
        return $event;
    }
}
