<?php


namespace app\admin\model\system;

use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\special\Lecturer;
use app\admin\model\special\Special;

/**
 * Class Recommend
 * @package app\admin\model\system
 */
class WebRecommend extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    public static function setAddTimeAttr($value)
    {
        return time();
    }

    public static function getTypeNameAttr($value, $data)
    {
        $name = '';
        switch ($data['type']) {
            case 0:
                $name = '课程';
                break;
            case 1:
                $name = '直播';
                break;
            case 2:
                $name = '讲师';
                break;
            case 3:
                $name = '资料';
            break;
            case 4:
                $name = '文章[内置]';
            break;
            case 5:
                $name = '课程推荐[内置]';
            break;
        }
        return $name;
    }

    public static function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public static function recommendList()
    {
        $list = self::where('is_show', 1)->order('sort desc,add_time desc')->select();
        foreach ($list as &$item) {
            $item['number'] = WebRecommendRelation::where(['recommend_id' => $item['id']])->count();
        }
        return $list;
    }

    public static function getRecommendList($where)
    {
        $model = new self();
        if (isset($where['order']) && $where['order']) {
            $model = $model->order(self::setOrder($where['order']));
        } else{
            $model = $model->order('sort desc,add_time desc');
        }
        $model = $model->where('is_fixed', $where['is_fixed']);
        $data = $model->page((int)$where['page'], (int)$where['limit'])->select();
        foreach ($data as $item) {
            $item['type_name'] = self::getTypeNameAttr('', $item);
            switch ($item['type']){
                case 0:
                    $item['number'] = WebRecommendRelation::where(['r.recommend_id' => $item['id']])->alias('r')->join('Special s','s.id=r.link_id')->where(['s.is_del'=>0,'s.is_show'=>1])->count();
                    break;
                case 1:
                    $item['number'] = WebRecommendRelation::where(['r.recommend_id' => $item['id']])->alias('r')->join('Special s','s.id=r.link_id')->where(['s.is_del'=>0,'s.type' =>4,'s.is_show'=>1])->count();
                    break;
                case 2:
                    $item['number'] = WebRecommendRelation::where(['recommend_id' => $item['id']])->alias('r')->join('Lecturer l','l.id=r.link_id')->where(['l.is_del'=>0,'l.is_show'=>1])->count();
                    break;
                case 3:
                    $item['number'] = WebRecommendRelation::where(['recommend_id' => $item['id']])->alias('r')->join('DataDownload d','d.id=r.link_id')->where(['d.is_del'=>0,'d.is_show'=>1])->count();
                    break;
                case 4:
                    $item['number'] = WebRecommendRelation::where(['recommend_id' => $item['id']])->alias('r')->join('Article a','a.id=r.link_id')->where(['a.is_show'=>1,'a.hide'=>0])->count();
                    break;
                case 5:
                    $item['number'] = 8;
                    break;
            }
        }
        $count = self::where('is_fixed', $where['is_fixed'])->count();
        return compact('data', 'count');
    }
}
