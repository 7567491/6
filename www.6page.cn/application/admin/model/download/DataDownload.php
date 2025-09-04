<?php


namespace app\admin\model\download;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\download\DataDownloadCategpry;
use app\admin\model\system\RecommendRelation;
use app\admin\model\system\WebRecommendRelation;
use app\admin\model\system\MpRecommendRelation;

/**资料 model
 * Class DataDownload
 * @package app\admin\model\download
 */
class DataDownload extends ModelBasic
{
    use ModelTrait;

    /**字段过滤
     * @param string $alias
     * @param null $model
     * @return DataDownload
     */
    public static function PreWhere($alias = '',$model=null)
    {
        if (is_null($model)) $model = new self();
        if ($alias) {
            $model = $model->alias($alias);
            $alias .= '.';
        }
        return $model->where([$alias . 'is_show' => 1, $alias . 'is_del' => 0]);
    }

    /**条件处理
     * @param $where
     * @return DataDownload
     */
    public static function setWhere($where)
    {
        $model=new self();
        $time['data']='';
        if(isset($where['start_time']) && isset($where['end_time']) && $where['start_time']!='' && $where['end_time']!=''){
            $time['data']=$where['start_time'].' - '.$where['end_time'];
            $model=$model->getModelTime($time,$model,'add_time');
        }
        if (isset($where['title']) && $where['title']){
            $model=$model->where('title','like',"%$where[title]%");
        }
        if(isset($where['cate_id']) && $where['cate_id']){
            $model=$model->where('cate_id',$where['cate_id']);
        }
        if(isset($where['is_show']) && $where['is_show']!=''){
            $model=$model->where('is_show',$where['is_show']);
        }
        return $model->where('is_del',0);
    }

    /**获取列表
     * @param $where
     * @return array
     * @throws \think\Exception
     */
    public static function get_download_list($where)
    {
        $data = self::setWhere($where)->order('sort DESC,id DESC')
            ->page((int)$where['page'], (int)$where['limit'])
            ->select()
            ->each(function ($item){
                $item['recommend'] = RecommendRelation::where('a.link_id', $item['id'])->where('a.type', 14)->alias('a')
                    ->join('__RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
                $item['web_recommend'] = WebRecommendRelation::where('a.link_id', $item['id'])->where('a.type', 3)->alias('a')
                    ->join('__WEB_RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
                $item['mp_recommend'] = MpRecommendRelation::where('a.link_id', $item['id'])->where('a.type', 14)->alias('a')
                    ->join('__MP_RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
                $item['add_time'] = ($item['add_time'] != 0 ||  $item['add_time']) ? date('Y-m-d H:i:s', $item['add_time']) : '';
                $item['cate_name'] =DataDownloadCategpry::where('id',$item['cate_id'])->value('title');
            });
        $data = count((array)$data) ? $data->toArray() : [];
        $count = self::setWhere($where)->count();
        return compact('data','count');
    }

    /**获取资料
     * @param $where
     */
    public static function dataDownloadLists($where,$source)
    {
        $data=self::setWhere($where)->where('id','not in',$source)->page($where['page'],$where['limit'])->select();
        $count = self::setWhere($where)->where('id','not in',$source)->count();
        return compact('data', 'count');
    }

}
