<?php


namespace app\admin\model\special;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;
use app\admin\model\special\SpecialTask;

/**课节分类
 * Class SpecialTaskCategory
 * @package app\admin\model\special
 */
class SpecialTaskCategory extends ModelBasic
{
    use ModelTrait;

    /**
     * 全部课节分类
     */
    public static function taskCategoryAll($type=0){
        $model=self::where('is_del',0);
        if($type==1){
            $model=$model->where('pid',0);
        }
        $list=$model->order('sort desc,add_time desc')->select();
        $list=count($list) > 0 ? $list->toArray() : [];
        $list=Util::sortListTier($list);
        return $list;
    }
    /**
     * 课节分类列表
     */
    public static function getAllList($where){
        $data = self::setWhere($where)->column('id,pid');
        $list=[];
        foreach ($data as $ket=>$item){
            $cate=self::where('id',$ket)->find();
            if($cate){
                $cate=$cate->toArray();
                if($item>0){
                    $cate['sum']=SpecialTask::where('pid', $ket)->where('is_del', 0)->count();
                }else{
                    $pids=self::categoryId($ket);
                    $cate['sum']=SpecialTask::where('pid','in', $pids)->where('is_del', 0)->count();
                }
                array_push($list,$cate);
                unset($cate);
            }
            if($item>0 && !array_key_exists($item,$data)){
                $cate=self::where('id',$item)->find();
                if($cate) {
                    $cate=$cate->toArray();
                    $pids=self::categoryId($item);
                    $cate['sum']=SpecialTask::where('is_del', 0)->where('pid','in', $pids)->count();
                    array_push($list,$cate);
                }
            }
        }
        return $list;
    }

    public static function setWhere($where)
    {
        $model = self::order('sort desc,add_time desc')->where('is_del', 0);
        if($where['pid']) $model=$model->where('id',$where['pid']);
        if ($where['cate_name'] != '') $model = $model->where('title', 'like', "%$where[cate_name]%");
        return $model;
    }

    /**获取一个分类下的所有分类ID
     * @param int $pid
     */
    public static function categoryId($pid=0){
        $data=self::where('is_del', 0)->where('pid',$pid)->column('id');
        array_push($data,$pid);
        return $data;
    }
}
