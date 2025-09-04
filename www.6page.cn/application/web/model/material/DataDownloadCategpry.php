<?php



namespace app\web\model\material;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class DataDownloadCategpry 二级分类
 * @package app\web\model\material
 */
class DataDownloadCategpry extends ModelBasic
{
    use ModelTrait;

    public function children()
    {
        return $this->hasMany('DataDownloadCategpry', 'pid','id')->where(['is_del' => 0,'is_show'=>1])->order('sort DESC,id DESC');
    }

}
