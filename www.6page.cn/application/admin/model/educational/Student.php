<?php


namespace app\admin\model\educational;

use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\educational\Classes;
/**
 * 学员 Model
 * Class Student
 * @package app\admin\model\educational
 */
class Student extends ModelBasic
{
    use ModelTrait;

    public static function setWhere($where)
    {
        $model = self::order('sort desc,add_time desc')->where('is_del', 0);
        if($where['cid']) $model=$model->where('classes_id',$where['cid']);
        if ($where['title'] != '') $model = $model->where('name|nickname|id', 'like', "%$where[title]%");
        return $model;
    }

    /**学员列表
     * @param $where
     */
    public static function getStudentLists($where){
        $data=self::setWhere($where)->page($where['page'],$where['limit'])->select();
        foreach ($data as $key=>&$value){
            $value['title']= Classes::where('id',$value['classes_id'])->value('title');
            $value['address']=$value['province'].$value['city'].$value['district'].$value['detail'];
            if ($value['sex']==0) {
                $value['sex']='未知';
            } else if ($value['sex']==2) {
                $value['sex']='女';
            } else if ($value['sex']==1) {
                $value['sex']='男';
            }
        }
        $count = self::setWhere($where)->count();
        return compact('data', 'count');
    }


}
