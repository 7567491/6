<?php


namespace app\wap\model\special;


use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class SpecialSource 课程素材关联表
 */
class SpecialSource extends ModelBasic
{
    use ModelTrait;

    /**获取课程素材
     * @param bool $special_id
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSpecialSource($special_id = false, $source_id = false, $limit = false, $page = false)
    {
        $where = array();
        $data = self::where($where);
        if ($special_id) {
            if (is_array($special_id)) {
                $data->whereIn('special_id', $special_id);
            }else{
                $where['special_id'] = $special_id;
                $data->where($where);
            }
        }
        if ($source_id) {
            if (!is_array($source_id)) {
                $where['source_id'] = $source_id;
                $data->where($where);
            } else {
                $data->whereIn('source_id', $source_id);
            }
        }
        if ($page) {
            $data->page((int)$page, !$limit ? 10 : (int)$limit);
        }
        return $data->order('sort DESC,id DESC')->select();
    }
    /**套餐课里课程获取
     * @param int $special_id
     * @param bool $limit
     * @param bool $page
     * @param int $is_member
     * @param $type
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function get_special_source_list($special_id=0,$is_member=0,$type,$limit=false,$page=false)
    {
        $where = array();
        $data = self::alias('o');
        if ($special_id) {
            if (is_array($special_id)) {
                $data =$data->whereIn('o.special_id', $special_id);
            }else{
                $where['o.special_id'] = $special_id;
                $data =$data->where($where);
            }
        }
        if($type==5){
            $data =$data->join('special s','s.id=o.source_id')->where(['s.is_del'=>0,'s.is_show'=>1]);
            if(!$is_member) {
                $data =$data->where('s.is_mer_visible',0);
            }
        }
        if ($page) {
            $data->page((int)$page, !$limit ? 10 : (int)$limit);
        }
        return $data->order('o.sort DESC,o.id DESC')->select();
    }
}
