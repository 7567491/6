<?php


namespace app\web\model\topic;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 证书 Model
 * Class Certificate
 */
class Certificate extends ModelBasic
{
    use ModelTrait;

    /**获取单个证书内容
     * @param $id
     * @param $obtain
     */
    public static function getone($id,$obtain)
    {
        return self::where(['id'=>$id,'obtain'=>$obtain,'is_del'=>0])->find();
    }

}
