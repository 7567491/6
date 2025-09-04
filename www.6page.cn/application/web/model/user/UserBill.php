<?php



namespace app\web\model\user;


use app\web\model\special\Special;
use basic\ModelBasic;
use traits\ModelTrait;

/**
 * Class UserBill
 * @package app\web\model\user
 */
class UserBill extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    public static function income($title, $uid, $category, $type, $number, $link_id = 0, $balance = 0, $mark = '', $status = 1, $get_uid = 0)
    {
        $pm = 1;
        return self::set(compact('title', 'uid', 'link_id', 'category', 'type', 'number', 'balance', 'mark', 'status', 'pm', 'get_uid'));
    }

    public static function expend($title, $uid, $category, $type, $number, $link_id = 0, $balance = 0, $mark = '', $status = 1)
    {
        $pm = 0;
        return self::set(compact('title', 'uid', 'link_id', 'category', 'type', 'number', 'balance', 'mark', 'status', 'pm'));
    }

    /**获取金币
     * @param $uid
     * @param string $type
     * @param $pm
     * @return float|int
     */
    public static function getUserGoldCoins($uid,$type='recharge',$pm)
    {
        $sum=UserBill::where(['uid'=>$uid,'category'=>'gold_num','type'=>$type,'pm'=>$pm,'status'=>1])
            ->where('number','>',0)->sum('number');
       return bcsub($sum,0,2);
    }

    public static function getUserbalance($uid,$pm)
    {
        $sum=UserBill::where(['uid'=>$uid,'category'=>'now_money','pm'=>$pm,'status'=>1])
            ->where('number','>',0)->sum('number');

        return bcsub($sum,0,2);
    }

}
