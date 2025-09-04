<?php



namespace app\web\model\special;


use basic\ModelBasic;
use traits\ModelTrait;

/**订单状态
 * Class StoreOrderStatus
 * @package app\web\model\special
 */
class StoreOrderStatus extends ModelBasic
{
    use ModelTrait;

    public static function status($oid,$change_type,$change_message,$change_time = null)
    {
        if($change_time == null) $change_time = time();
        return self::set(compact('oid','change_type','change_message','change_time'));
    }

    public static function getTime($oid,$change_type)
    {
        return self::where('oid',$oid)->where('change_type',$change_type)->value('change_time');
    }

}
