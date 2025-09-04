<?php


namespace app\admin\model\system;

use app\admin\model\article\Article;
use app\admin\model\special\Special;
use app\admin\model\special\SpecialTask;
use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\store\StoreProduct;
use app\admin\model\system\Recommend;
use app\admin\model\questions\TestPaper;
use app\admin\model\download\DataDownload;

/**
 * Class RecommendRelation
 * @package app\admin\model\system
 */
class MpRecommendRelation extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    public static function setAddTimeAttr($value)
    {
        return time();
    }

    public static function setWhere($id){
        $recommend=MpRecommend::where('id', $id)->find();
        $model=self::where('r.recommend_id', $id)->alias('r')->order('r.sort desc,r.add_time desc');
        switch ($recommend['type']){
            case 0:
            case 8:
                $model=$model->join('Special s','r.link_id=s.id')->field('r.*,s.is_show,s.is_del')->where(['s.is_show'=>1,'s.is_del'=>0]);
                break;
            case 1:
                $model=$model->join('Article a','r.link_id=a.id')->field('r.*,a.is_show')->where(['a.is_show'=>1]);
                break;
            case 4:
                $model=$model->join('StoreProduct p','r.link_id=p.id')->field('r.*,p.is_show,p.is_del')->where(['p.is_show'=>1,'p.is_del'=>0]);
                break;
            case 11:
                $model=$model->join('TestPaper t','r.link_id=t.id')->field('r.*,t.is_show,t.is_del')->where(['t.is_show'=>1,'t.is_del'=>0]);
                break;
            case 12:
                $model=$model->join('TestPaper t','r.link_id=t.id')->field('r.*,t.is_show,t.is_del')->where(['t.is_show'=>1,'t.is_del'=>0]);
                break;
            case 14:
                $model=$model->join('DataDownload d','r.link_id=d.id')->field('r.*,d.is_show,d.is_del')->where(['d.is_show'=>1,'d.is_del'=>0]);
                break;
        }
        return $model;
    }
    public static function getAll($where, $id)
    {
        $data=self::setWhere($id)->page((int)$where['page'], (int)$where['limit'])->select();
        foreach ($data as &$itme) {
            if ($itme['type'] == 0) {
                $itme['type_name'] = '课程';
                $link = Special::PreWhere()->where('id', $itme['link_id'])->field('is_light,title')->find();
                if($link['is_light']){
                    $itme['count'] =1;
                }else{
                    $itme['count'] = SpecialTask::getTaskCount($itme['link_id']);
                }
                $itme['title'] = $link['title'];
            } else if ($itme['type'] == 1) {
                $itme['type_name'] = '新闻';
                $itme['title'] = Article::PreWhere()->where('id', $itme['link_id'])->value('title');
            }else if ($itme['type'] == 4) {
                $itme['type_name'] = '商品';
                $itme['title'] = StoreProduct::PreWhere()->where('id', $itme['link_id'])->value('store_name');
            }else if ($itme['type'] == 8) {
                $itme['type_name'] = '拼团';
                $link = Special::PreWhere()->where('id', $itme['link_id'])->field('is_light,title')->find();
                if($link['is_light']){
                    $itme['count'] =1;
                }else{
                    $itme['count'] = SpecialTask::getTaskCount($itme['link_id']);
                }
                $itme['title'] = $link['title'];
            }else if ($itme['type'] == 10) {
                $itme['type_name'] = '课节';
                $itme['title'] = SpecialTask::where(['id'=>$itme['link_id'],'is_del'=>0,'is_show'=>1])->value('title');
            }else if ($itme['type'] == 11) {
                $itme['type_name'] = '练习';
                $itme['title'] = TestPaper::where(['id'=>$itme['link_id'],'is_del'=>0,'is_show'=>1])->value('title');
            }else if ($itme['type'] == 12) {
                $itme['type_name'] = '考试';
                $itme['title'] = TestPaper::where(['id'=>$itme['link_id'],'is_del'=>0,'is_show'=>1])->value('title');
            }else if ($itme['type'] == 14) {
                $itme['type_name'] = '资料';
                $itme['title'] = DataDownload::where(['id'=>$itme['link_id'],'is_del'=>0,'is_show'=>1])->value('title');
            }
        }
        $count = self::setWhere($id)->count();
        return compact('data', 'count');
    }
}
