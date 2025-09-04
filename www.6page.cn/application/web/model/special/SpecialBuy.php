<?php


namespace app\web\model\special;

use basic\ModelBasic;
use traits\ModelTrait;

/**课程获得
 * Class SpecialBuy
 * @package app\web\model\special
 */
class SpecialBuy extends ModelBasic
{
    use ModelTrait;

    protected function setAddTimeAttr()
    {
        return time();
    }

    protected function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    protected function getTypeAttr($value)
    {
        $name = '';
        switch ($value) {
            case 0:
                $name = '支付获得';
                break;
            case 1:
                $name = '拼团获得';
                break;
            case 2:
                $name = '领取礼物获得';
                break;
            case 3:
                $name = '赠送获得';
                break;
            case 4:
                $name = '兑换获得';
                break;
        }
        return $name;
    }

    /**课程加入获得表
     * @param $order_id
     * @param $uid
     * @param $special_id
     * @param int $type
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function setAllBuySpecial($order_id, $uid, $special_id, $type = 0)
    {
        if (!$order_id || !$uid || !$special_id) return false;
        //如果是专栏，记录专栏下所有课程购买。
        $special = Special::get($special_id);
        if ($special['type'] == SPECIAL_COLUMN) {
            $special_source = SpecialSource::getSpecialSource($special['id']);
            if (!$special_source) return false;
            foreach($special_source as $k => $v) {
                $task_special = Special::get($v['source_id']);
                if (!$task_special) continue;
                if ($task_special['is_show']!=1) continue;
                self::setBuySpecial($order_id, $uid, $v['source_id'], $type,$task_special['validity'],$special_id);
            }
        }
        self::setBuySpecial($order_id, $uid, $special_id, $type,$special['validity']);
    }

    /**记录
     * @param $order_id
     * @param $uid
     * @param $special_id
     * @param int $type
     * @param int $column_id
     * @return bool|object
     */
    public static function setBuySpecial($order_id, $uid, $special_id, $type = 0,$validity=0,$column_id=0)
    {
        $add_time = time();
        if (self::be(['uid' => $uid,'special_id' => $special_id,'column_id'=>$column_id,'is_del'=>0])) return false;
        $validity_time=0;
        if($validity>0){
            $validity_time=bcadd(time(),bcmul($validity,86400,0),0);
        }
        return self::set(compact('order_id','column_id','uid', 'special_id', 'type','validity_time', 'add_time'));
    }


    /**检查课程是否获得
     * @param $special_id
     * @param $uid
     * @return bool
     * @throws \think\Exception
     */
    public static function PaySpecial($special_id, $uid)
    {
        self::where(['uid' => $uid, 'special_id' => $special_id, 'is_del' => 0])->where('validity_time','<>',0)->where('validity_time','<=',time())->update(['is_del'=>1]);
        return self::where(['uid' => $uid, 'special_id' => $special_id, 'is_del' => 0])->count() ? true : false;
    }

    /**获取购买课程的有效时间
     * @param $special_id
     * @param $uid
     */
    public static function getSpecialEndTime($special_id, $uid)
    {
        $res=self::where(['uid' => $uid, 'special_id' => $special_id, 'is_del' => 0])->where('validity_time',0)->find();
        if($res) return 0;
        $buy=self::where(['uid' => $uid, 'special_id' => $special_id, 'is_del' => 0])->order('validity_time desc')->find();
        if($buy){
            return bcsub($buy['validity_time'],time(),0);
        }else{
            return -1;
        }
    }
}
