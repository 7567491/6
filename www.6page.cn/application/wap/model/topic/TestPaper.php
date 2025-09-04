<?php


namespace app\wap\model\topic;

use traits\ModelTrait;
use basic\ModelBasic;
use app\wap\model\topic\TestPaperCategory;

/**
 * 试卷列表 Model
 * Class TestPaper
 */
class TestPaper extends ModelBasic
{
    use ModelTrait;

    /**
     * 设置课程显示条件
     * @param string $alias 别名
     * @param null $model model
     * @param bool $isAL 是否起别名,默认执行
     * @return $this
     */
    public static function PreExercisesWhere($alias = '', $model = null, $isAL = false)
    {
        if (is_null($model)) $model = new self();
        if ($alias) {
            $isAL || $model = $model->alias($alias);
            $alias .= '.';
        }
        return $model->where(["{$alias}is_del" => 0,"{$alias}is_show"=>1]);
    }

    /**练习试卷列表
     * @param int $page
     * @param int $limit
     * @param $tid
     * @return array
     */
    public static function getTestPaperExercisesList($type,$page,$limit,$pid,$tid,$search)
    {
        $model = self::PreExercisesWhere();
        if($pid && $tid) {
            $model = $model->where(['tid'=>$tid]);
        }else if($pid && !$tid){
           $tids=TestPaperCategory::where('pid',$pid)->column('id');
           $model = $model->where('tid','in',$tids);
        }
        if($search) $model = $model->where('title','LIKE', "%$search%");
        $list =$model->where('type',$type)->order('sort desc,id desc')->page($page, $limit)->select();
        $list = count($list) ? $list->toArray() : [];
        return $list;
    }

}
