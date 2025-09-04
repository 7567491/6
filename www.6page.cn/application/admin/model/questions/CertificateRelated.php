<?php


namespace app\admin\model\questions;

use traits\ModelTrait;
use basic\ModelBasic;
use service\UtilService as Util;
use app\admin\model\questions\Certificate;
use app\admin\model\user\User;
use app\admin\model\special\Special;
use app\admin\model\questions\TestPaper;
/**
 * 证书关联记录 Model
 * Class CertificateRelated
 * @package app\admin\model\questions
 */
class CertificateRelated extends ModelBasic
{
    use ModelTrait;

    /**添加/修改关联信息
     * @param $data
     * @param $id
     */
    public static function addCertificateRelated($data,$id)
    {
        if($id){
          return self::edit($data,$id);
        }else{
           return self::set($data);
        }
    }
}
