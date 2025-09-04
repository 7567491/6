<?php


namespace app\wap\model\topic;

use traits\ModelTrait;
use basic\ModelBasic;
use app\wap\model\topic\ExaminationRecord;
use app\wap\model\special\Special as SpecialModel;
use app\wap\model\special\SpecialSource;
use app\wap\model\special\SpecialWatch;
/**
 * 证书关联记录 Model
 */
class CertificateRelated extends ModelBasic
{
    use ModelTrait;

    /**检查是否存在关联
     * @param $id
     * @param $obtain
     */
    public static function checkAssociation($id,$obtain)
    {
        return self::where(['related'=>$id,'obtain'=>$obtain])->count() > 0 ? true : false;
    }

    /**检查是否达到获得证书要求
     * @param $id 课程ID
     * @param $is_light 是否为精简课
     * @param $obtain
     */
    public static function getCertificateRelated($id,$is_light,$obtain,$uid)
    {
        $related=self::where(['related'=>$id,'obtain'=>$obtain,'is_show'=>1])->find();
        if(!$related) return false;
        $record=CertificateRecord::setWhere($uid)->where(['source_id'=>$id,'obtain'=>$obtain])->find();
        if($record) return false;
        if($obtain==1){
            $count = $is_light==1 ? 1 : SpecialSource::where(['special_id'=>$id])->count();
            // is_complete=1的时候为已完成
            $watchCount=SpecialWatch::where(['special_id'=>$id, 'uid'=>$uid, 'is_complete' => 1])->count();
            return $count==$watchCount ? true : false;
        }else if($obtain==2){
            $record=ExaminationRecord::where(['test_id'=>$id,'uid'=>$uid,'type'=>2,'is_submit'=>1])->order('id desc')->find();
            if(!$record) return false;
            return $record['score']>=$related['condition'] ? true : false;
        }
    }

    /**检查是否达到获得证书要求
     * @param $id 课程ID
     * @param $is_light 是否为精简课
     * @param $obtain
     */
    public static function getCertificateRelatedSys($id,$is_light,$obtain,$uid)
    {
        $related=self::where(['related'=>$id,'obtain'=>$obtain,'is_show'=>1])->find();
        if(!$related) return false;
        // 如果已经有证书了就不再判断
        $record=CertificateRecord::setWhere($uid)->where(['source_id'=>$id,'obtain'=>$obtain])->find();
        if($record) return false;
        // 获取该套餐下的所有课程
        $cloumn_source = SpecialSource::get_special_source_list($id,0,5,10000, 1);
        // 课时总数
        $count = 0;
        // 已学习总数
        $watchCount = 0;
        foreach ($cloumn_source as $item) {
            $count += $item['is_light'] == 1 ? 1 : SpecialSource::where(['special_id'=>$item['id']])->count();
            $watchCount += SpecialWatch::where(['special_id'=>$item['id'], 'uid'=>$uid, 'is_complete' => 1])->count();
        }
        return $count==$watchCount ? true : false;
    }

}
