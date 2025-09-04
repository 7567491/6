<?php


namespace app\wap\model\topic;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;
use app\admin\model\questions\Questions as QuestionsModel;
/**
 * 试卷分类 Model
 * Class TestPaperCategory
 */
class TestPaperCategory extends ModelBasic
{
    use ModelTrait;

    public function children()
    {
        return $this->hasMany('TestPaperCategory', 'pid','id')->where(['is_del' => 0,'is_show'=>1])->order('sort DESC,id DESC');
    }
}
