<?php


namespace app\admin\model\questions;

use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\questions\TestPaperCategory as TestPaperCategoryModel;
use app\admin\model\system\RecommendRelation;
use app\admin\model\questions\TestPaperObtain;

/**
 * 试卷列表 Model
 * Class TestPaper
 * @package app\admin\model\questions
 */
class TestPaper extends ModelBasic
{
    use ModelTrait;

    public static function setWhere($where)
    {
        $model = self::order('sort desc,add_time desc')->where(['is_del'=>0]);
        if(isset($where['pid']) && $where['pid']) $model=$model->where('tid',$where['pid']);
        if(isset($where['type']) && $where['type']) $model=$model->where('type',$where['type']);
        if(isset($where['is_show']) && $where['is_show']!='') $model=$model->where('is_show',$where['is_show']);
        if ($where['title'] != '') $model = $model->where('title', 'like', "%$where[title]%");
        return $model;
    }

    /**试卷列表
     * @param $where
     */
    public static function testPaperExercisesList($where){
        $data=self::setWhere($where)->page($where['page'],$where['limit'])->select();
        foreach ($data as $key=>&$value){
            $value['cate']= TestPaperCategoryModel::where('id',$value['tid'])->value('title');
            $value['recommend'] = RecommendRelation::where('a.link_id', $value['id'])->where('a.type','in', '11,12')->alias('a')
                ->join('__RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
            $value['types']= $value['type']==1 ? '练习': '考试';
        }
        $count = self::setWhere($where)->count();
        return compact('data', 'count');
    }

    /**试卷列表
     * @param $type
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function testPaperList($type)
    {
        return self::order('sort desc,add_time desc')->where(['is_del'=>0,'type'=>$type])
            ->field('id,title')
            ->select();
    }

    /**获取练习、试卷
     * @param $where
     */
    public static function testPaperLists($where,$source)
    {
        $data=self::setWhere($where)->where('id','not in',$source)->page($where['page'],$where['limit'])->select();
        $count = self::setWhere($where)->where('id','not in',$source)->count();
        return compact('data', 'count');
    }

    public static function getUserWhere($where)
    {
        return self::alias('t')->join('TestPaperObtain o','t.id=o.test_id')
            ->where(['o.uid'=>$where['uid'],'t.is_del'=>0,'o.is_del'=>0,'o.source'=>3, 't.type'=>$where['type']])
            ->field('t.title,t.type,t.id');
    }

    /**已获得试卷
     * @param $where
     * @return array
     * @throws \think\Exception
     */
    public static function getUserTestPaperList($where)
    {
        $data=self::getUserWhere($where)->page($where['page'],$where['limit'])->select();
        foreach ($data as $key=>&$value){
            $value['types']= $value['type']==1 ? '练习': '考试';
        }
        $count = self::getUserWhere($where)->count();
        return compact('data', 'count');
    }

}
