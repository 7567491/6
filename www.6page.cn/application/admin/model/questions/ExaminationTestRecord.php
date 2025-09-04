<?php


namespace app\admin\model\questions;

use traits\ModelTrait;
use basic\ModelBasic;
/**
 * 考试试题记录 Model
 * Class ExaminationTestRecord
 */
class ExaminationTestRecord extends ModelBasic
{
    use ModelTrait;

    /**获取答题情况
     * @param $questions_id
     */
    public static function testRecord($questions_id)
    {
        $data['wrong']=self::where(['questions_id'=>$questions_id,'is_correct'=>1])->count();
        $data['yes']=self::where(['questions_id'=>$questions_id,'is_correct'=>2])->count();
        $data['total']=bcadd($data['wrong'],$data['yes'],0);
        return $data;
    }
}
