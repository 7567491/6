<?php


namespace app\wap\model\material;

use traits\ModelTrait;
use basic\ModelBasic;
use app\wap\model\special\Special;
use app\wap\model\special\SpecialSource;
use app\wap\model\topic\Relation;
use app\wap\model\material\DataDownload;
use app\wap\model\material\DataDownloadOrder;

/**
 * 获得资料 Model
 */
class DataDownloadBuy extends ModelBasic
{
    use ModelTrait;

    /** 用户获得资料
     * @param $order_id
     * @param $data_id
     * @param $uid
     * @param $type
     * @return bool|object
     */
    public static function setUserDataDownload($order_id,$data_id,$uid,$type)
    {
        $add_time=time();
        if (self::be([ 'uid' => $uid, 'data_id' => $data_id, 'is_del' => 0])) return false;
        return self::set(compact('order_id','data_id','uid','type','add_time'));
    }

    /**判断是否获得资料
     * @param $test_id
     * @param $uid
     * @param $type
     * @return bool
     * @throws \think\Exception
     */
    public static function PayDataDownload($data_id,$uid)
    {
        return self::where(['uid' => $uid,'data_id' => $data_id,'is_del' => 0])->count() ? true : false;
    }

    /**购买课程获得资料
     * @param $order_id
     * @param $uid
     * @param $special_id
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function setDataDownload($order_id, $uid, $special_id)
    {
        if (!$order_id || !$uid || !$special_id) return false;
        $special = Special::get($special_id);
        if ($special['type'] == SPECIAL_COLUMN) {
            $special_source = SpecialSource::getSpecialSource($special['id']);
            if(!$special_source) return false;
            foreach($special_source as $k => $v) {
                $task_special = Special::get($v['source_id']);
                if (!$task_special) continue;
                if ($task_special['is_show'] != 1) continue;
                $data_ids=Relation::setWhere(4,$task_special['id'])->column('relation_id');
                if(count($data_ids)<=0) continue;
                foreach ($data_ids as $ks=>$value){
                    self::setUserDataDownload($order_id,$value,$uid, 1);
                }
            }
        }else{
            $data_ids=Relation::setWhere(4,$special_id)->column('relation_id');
            if(count($data_ids)<=0) return false;
            foreach ($data_ids as $kf=>$value){
                self::setUserDataDownload($order_id,$value,$uid, 1);
            }
        }
    }

    /**获取用户的资料
     * @param $type
     * @param $uid
     */
    public static function getUserDataDownload($uid,$page,$limit)
    {
        $list=self::alias('b')->join('DataDownload d','b.data_id=d.id')
            ->where(['b.uid'=>$uid,'b.is_del'=>0])
            ->field('d.id,b.uid,b.data_id,b.type,b.add_time,d.title,d.image,d.money,d.pay_type,d.sales,d.ficti')
            ->page((int)$page,(int)$limit)->order('b.add_time DESC')
            ->select();
        $list=count($list) > 0 ? $list->toArray() : [];
        return $list;
    }
}
