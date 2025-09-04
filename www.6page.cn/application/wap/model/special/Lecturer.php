<?php


namespace app\wap\model\special;

use basic\ModelBasic;
use traits\ModelTrait;

/**讲师 model
 * Class Lecturer
 * @package app\wap\model\special
 */
class Lecturer extends ModelBasic
{
    use ModelTrait;

    /**讲师列表
     * @param int $page
     * @param int $limit
     * @return array
     */
   public static function getLecturer($page=1,$limit=10)
   {
        $data=self::where(['is_del'=>0,'is_show'=>1])->page((int)$page,(int)$limit)
            ->field('id,lecturer_name,lecturer_head,label,curriculum,explain,study,sort,is_show,is_del')
            ->order('sort DESC,id DESC')->select();
        $data=count($data) > 0 ? $data->toArray() : [];
        foreach ($data as $key=>&$value){
            $value['label'] =json_decode($value['label']);
        }
        return $data;
   }

    /**讲师详情
     * @param int $id
     */
   public static function details($id=0)
   {
       $details=self::where(['is_del'=>0,'is_show'=>1])->where('id',$id)->find();
       if($details){
        $details['label']=json_decode($details['label']);
        $details['introduction'] = htmlspecialchars_decode($details['introduction']);
        return $details;
        }else{
            return null;
        }
   }
}
