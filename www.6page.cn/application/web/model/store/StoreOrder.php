<?php


namespace app\web\model\store;

use basic\ModelBasic;
use traits\ModelTrait;


/**订单表
 * Class StoreOrder
 * @package app\wap\model\store
 */
class StoreOrder extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected static $payType = ['weixin' => '微信支付', 'yue' => '余额支付', 'offline' => '线下支付', 'zhifubao' => '支付宝'];

    protected static $deliveryType = ['send' => '商家配送', 'express' => '快递配送'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    protected function setCartIdAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    protected function getCartIdAttr($value)
    {
        return json_decode($value, true);
    }
}
