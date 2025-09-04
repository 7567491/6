<?php


namespace app\wap\model\material;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;
use app\wap\model\material\DataDownloadCategpry;

/**资料 model
 * Class DataDownload
 * @package app\wap\model\material
 */
class DataDownload extends ModelBasic
{
    use ModelTrait;

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
     * @param $pid
     * @param $cate_id
     * @param $is_pay
     * @param $salesOrder
     * @param $search
     * @return DataDownload
     */
    public static function setWhere($pid,$cate_id,$is_pay,$salesOrder,$search)
    {
        $model = self::PreWhere();
        if($cate_id) {
            $model = $model->where(['cate_id'=>$cate_id]);
        }else if($pid && !$cate_id){
            $cate_ids=DataDownloadCategpry::where('pid',$pid)->column('id');
            $model = $model->where('cate_id','in',$cate_ids);
        }
        if($is_pay!='') $model = $model->where(['pay_type'=>$is_pay]);
        if($search) $model = $model->where('title','LIKE', "%$search%");
        $baseOrder = '';
        if ($salesOrder) $baseOrder = $salesOrder == 'desc' ? 'sales DESC' : 'sales ASC';//下载量
        if ($baseOrder) $baseOrder .= ', ';
        return $model->order($baseOrder . 'sort DESC, add_time DESC');
    }

    /**列表
     * @param int $page
     * @param int $limit
     * @param $tid
     * @return array
     */
    public static function getDataDownloadExercisesList($page,$limit,$pid,$cate_id,$is_pay,$salesOrder,$search)
    {
        $model=self::setWhere($pid,$cate_id,$is_pay,$salesOrder,$search);
        $data =$model->page($page, $limit)->field('add_time,cate_id,description,ficti,id,image,is_network_disk,is_show,member_money,member_pay_type,money,pay_type,poster_image,sales,sort,title')->select();
        $data = count($data) ? $data->toArray() : [];
        $count= self::setWhere($pid,$cate_id,$is_pay,$salesOrder,$search)->count();
        return compact('data', 'count');
    }

    /**
     * 获取单个资料的详细信息
     * @param $uid 用户id
     * @param $id 资料id
     * */
    public static function getOneDataDownload($uid, $id)
    {
        $data = self::PreWhere()->find($id);
        if (!$data) return self::setErrorInfo('您要查看的资料不存在!');
        if ($data->is_show==0) return self::setErrorInfo('您要查看的资料已下架!');
        $title = $data->title;
        $data->collect = self::getDb('special_relation')->where(['link_id' => $id, 'type' => 1, 'uid' => $uid, 'category' => 1])->count() ? true : false;
        $data->abstract = htmlspecialchars_decode($data->abstract);
        $data = json_encode($data->toArray());
        return compact('data', 'title');
    }

}
