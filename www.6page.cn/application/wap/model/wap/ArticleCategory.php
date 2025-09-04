<?php



namespace app\wap\model\wap;

use think\Db;
use traits\ModelTrait;
use basic\ModelBasic;

/**新闻分类
 * Class ArticleCategory
 * @package app\wap\model
 */
class ArticleCategory extends ModelBasic
{
    use ModelTrait;

    public static function cidByArticleList($cid, $first, $limit, $field = '*')
    {
        $model = Db::name('article');
        if ($cid) $model->where("CONCAT(',',cid,',') LIKE '%,$cid,%'", 'exp');
        return $model->field($field)->where('status', 1)->where('hide', 0)->order('sort DESC,add_time DESC')->limit($first, $limit)->select();
    }
}
