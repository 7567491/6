<?php


namespace app\web\model\topic;

use traits\ModelTrait;
use basic\ModelBasic;
use app\web\model\topic\ExaminationRecord;
use app\web\model\special\Special as SpecialModel;
use app\web\model\special\SpecialSource;
use app\web\model\special\SpecialWatch;
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
            $watchCount=SpecialWatch::where(['special_id'=>$id,'uid'=>$uid])->where('percentage','>=',(int)$related['condition'])->count();
            return $count==$watchCount ? true : false;
        }else if($obtain==2){
            $record=ExaminationRecord::where(['test_id'=>$id,'uid'=>$uid,'type'=>2,'is_submit'=>1])->order('id desc')->find();
            if(!$record) return false;
            return $record['score']>=$related['condition'] ? true : false;
        }
    }

}
