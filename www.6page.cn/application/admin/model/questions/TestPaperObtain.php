<?php


namespace app\admin\model\questions;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;
use app\admin\model\questions\Questions as QuestionsModel;
use app\admin\model\questions\TestPaper as TestPaperModel;
use app\admin\model\educational\Student as StudentModel;
/**
 * 获得试卷 Model
 * Class TestPaperObtain
 * @package app\admin\model\questions
 */
class TestPaperObtain extends ModelBasic
{
    use ModelTrait;

    /**给学员发送试卷
     * @param $id
     * @param $data
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addsend($id,$data)
    {
        $student=StudentModel::where('id',$id)->field('id,uid,classes_id')->find();
        if(!$student) return false;
        foreach ($data as $key=>$value){
            $type=TestPaperModel::where(['id'=>$value])->value('type');
            $item['uid']=$student['uid'];
            $item['test_id']=$value;
            $item['type']=$type;
            if(self::be($item)) continue;
            $item['source']=3;
            $item['add_time']=time();
            self::set($item);
        }
        return true;
    }

    /**给用户单独发送试卷
     * @param $uid
     * @param $data
     * @return bool
     */
    public static function addUidSend($uid,$data)
    {
        foreach ($data as $key=>$value){
            $type=TestPaperModel::where(['id'=>$value])->value('type');
            $item['uid']=$uid;
            $item['test_id']=$value;
            $item['type']=$type;
            if(self::be($item)) continue;
            $item['source']=3;
            $item['add_time']=time();
            self::set($item);
        }
        return true;
    }
}
