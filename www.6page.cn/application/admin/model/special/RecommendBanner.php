<?php


namespace app\admin\model\special;

use traits\ModelTrait;
use basic\ModelBasic;

/**Banner 图
 * Class RecommendBanner
 * @package app\admin\model\special
 */
class RecommendBanner extends ModelBasic
{
    use ModelTrait;

    public static function valiWhere($alias = '', $model = null)
    {
        if (is_null($model)) $model = new self();
        if ($alias) {
            $model = $model->alias($alias);
            $alias .= '.';
        }
        return $model->where("{$alias}is_show", 1);
    }

    public static function getRecemmodBannerList($where)
    {
        $data = self::valiWhere()->order('sort DESC,id DESC')->where('recommend_id', $where['id'])->page((int)$where['page'], (int)$where['limit'])->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
        }
        $count = self::valiWhere()->order('sort DESC,id DESC')->where('recommend_id', $where['id'])->count();
        return compact('data', 'count');
    }
}
