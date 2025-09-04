<?php


namespace app\web\model\special;

use basic\ModelBasic;
use traits\ModelTrait;
use think\Db;

/**用户浏览记录
 * Class LearningRecords
 * @package app\web\model\special
 */
class LearningRecords extends ModelBasic
{
    use ModelTrait;

    /**
     * 记录用户浏览记录
     * @param $specialId
     * @param $uid
     * @return false|int|object
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function recordLearning($specialId, $uid,$time)
    {
        $info = self::where(['uid'=>$uid,'special_id'=>$specialId,'add_time'=>$time])->find();
        $res=true;
        if (!$info) {
            $res = self::set([
                'add_time' => $time,
                'uid' => $uid,
                'special_id' => $specialId
            ]);
        }
        return $res;
    }

}
