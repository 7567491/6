<?php


namespace app\wap\model\special;

use basic\ModelBasic;
use traits\ModelTrait;

/**课程收藏
 * Class SpecialRelation
 * @package app\wap\model\special
 */
class SpecialRelation extends ModelBasic
{
    use ModelTrait;

    /**
     * 收藏和取消收藏
     * @param $uid int 用户uid
     * @param $id int 课程id
     * @return bool|Object
     */
    public static function SetCollect($uid,$id,$type=0){
        if(self::be(['uid'=>$uid,'link_id'=>$id,'category'=>1,'type'=>$type])){
            return self::where(['uid'=>$uid,'link_id'=>$id,'type'=>$type,'category'=>1])->delete();
        }else{
            return self::set(['uid'=>$uid,'link_id'=>$id,'type'=>$type,'category'=>1,'add_time'=>time()]);
        }
    }

}
