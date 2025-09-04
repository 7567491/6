<?php


namespace app\web\model\topic;

use traits\ModelTrait;
use basic\ModelBasic;
/**
 * 错题库 Model
 * Class ExaminationWrongBank
 */
class ExaminationWrongBank extends ModelBasic
{
    use ModelTrait;

    /**加入错题库
     * @param $examination_id
     * @param $type
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function addWrongBank($examination_id,$test_id,$type,$uid)
    {
        $data=ExaminationTestRecord::where(['e_id'=>$examination_id,'type'=>$type,'is_correct'=>1])->select();
        $data=count($data) > 0 ? $data->toArray() : [];
        if(!count($data)) return true;
        $item['uid']=$uid;
        foreach ($data as $key=>$value){
            $item['questions_id']=$value['questions_id'];
            $item['user_answer']=$value['user_answer'];
            $item['answer']=$value['answer'];
            $item['test_id']=$test_id;
            $item['add_time']=time();
            if(self::be(['uid'=>$item['uid'],'test_id'=>$test_id,'questions_id'=>$item['questions_id']])) continue;
            self::set($item);
        }
        return true;
    }

    /**错题列表
     * @param $uid
     * @param $is_master
     * @param int $page
     * @param int $limit
     * @return mixed
     */
    public static function userWrongBankList($uid,$page=1,$limit=10,$is_master)
    {
        $model=self::alias('w');
        if($is_master!='')$model=$model->where('w.is_master',$is_master);
        $list=$model->join('Questions q','w.questions_id=q.id')
            ->join('TestPaper t','w.test_id=t.id')
            ->where(['w.uid'=>$uid,'q.is_del'=>0,'t.is_del'=>0,'t.is_show'=>1])
            ->field('w.*,q.option,q.stem,q.image,q.answer,q.difficulty,q.analysis,q.relation,q.question_type,q.is_img,t.title')
            ->order('w.add_time desc,w.id desc')->page((int)$page,(int)$limit)->select();
        $model=self::alias('w');
        if($is_master!='')$model=$model->where('w.is_master',$is_master);
        $count = $model->join('Questions q','w.questions_id=q.id')
            ->join('TestPaper t','w.test_id=t.id')
            ->where(['w.uid'=>$uid,'q.is_del'=>0,'t.is_del'=>0,'t.is_show'=>1])->count();
        return compact('list', 'count');
    }

    /**获取错题库试题
     * @param $uid
     * @param $is_master
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserWrongBankListAll($uid,$is_master)
    {
        $list=self::alias('w')->join('Questions q','w.questions_id=q.id')
            ->where(['w.uid'=>$uid,'w.is_master'=>$is_master,'q.is_del'=>0])
            ->field('w.*,q.option,q.stem,q.image,q.answer,q.difficulty,q.analysis,q.relation,q.is_img')
            ->order('w.add_time desc')->select();
        return $list;
    }

    /**单个错题
     * @param $uid
     * @param $id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getUserWrongBankListOne($uid,$id)
    {
        return self::alias('w')->join('Questions q','w.questions_id=q.id')
            ->where(['w.uid'=>$uid,'w.id'=>$id,'q.is_del'=>0])
            ->field('w.*,q.option,q.stem,q.image,q.answer,q.difficulty,q.analysis,q.relation,q.question_type,q.is_img')
            ->order('w.add_time desc')->find();
    }

    /**错题掌握修改
     * @param $data
     * @param $uid
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function userSubmitWrongBank($data,$uid)
    {
        $wrong=self::where(['id'=>$data['wrong_id'],'questions_id'=>$data['questions_id'],'uid'=>$uid])->find();
        if(!$wrong) return false;
        $dat['is_master']=$data['is_master'];
        $res=self::edit($dat,$data['wrong_id']);
        return $res;
    }
}
