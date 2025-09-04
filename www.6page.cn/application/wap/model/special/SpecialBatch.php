<?php


namespace app\wap\model\special;

use traits\ModelTrait;
use basic\ModelBasic;

/**课程兑换记录 model
 * Class SpecialBatch
 * @package app\wap\model\special
 */
class SpecialBatch extends ModelBasic
{
    use ModelTrait;

    /**课程是否开启兑换活动
     * @param $special_id
     */
    public static function isBatch($special_id)
    {
        $batch=self::where(['special_id'=>$special_id,'status'=>1])->find();
        if($batch) return true;
        else return false;
    }


}
