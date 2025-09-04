<?php


namespace app\admin\model\questions;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 证书 Model
 * Class Certificate
 * @package app\admin\model\questions
 */
class Certificate extends ModelBasic
{
    use ModelTrait;

    /**条件
     * @param $where
     */
    public static function setWhere($where=[])
    {
        $model=self::where(['is_del'=>0]);
        if($where['obtain']>0) $model=$model->where('obtain',$where['obtain']);
        if($where['title']!='') $model=$model->where('title','like',"%$where[title]%");
        return $model;
    }

    /**证书列表
     * @param $where 条件
     */
    public static function getCertificateList($where)
    {
        $data=self::setWhere($where)->page((int)$where['page'],(int)$where['limit'])->order('sort desc,add_time desc')->select();
        foreach ($data as $key=>&$value){
            switch ($value['obtain']){
                case 1:
                    $value['obtains']='课程';
                break;
                case 2:
                    $value['obtains']='考试';
                break;
            }
        }
        $count=self::setWhere($where)->count();
        return compact('data', 'count');
    }

    /**
     * 证书列表
     */
    public static function certificateList()
    {
        $list=self::where(['is_del'=>0])->order('sort desc,add_time desc')->select();
        return $list;
    }
}
