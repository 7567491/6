<?php


namespace app\web\model\special;

use basic\ModelBasic;
use traits\ModelTrait;

/**讲师 model
 * Class Lecturer
 * @package app\web\model\special
 */
class Lecturer extends ModelBasic
{
    use ModelTrait;

    public static function setWhere()
    {
        return self::where(['is_del'=>0,'is_show'=>1])->order('sort DESC,id DESC');
    }

    /**讲师列表
     * @param int $page
     * @param int $limit
     * @return array
     */
   public static function getLecturer($page=1,$limit=10)
   {
        $data=self::setWhere()->page((int)$page,(int)$limit)
            ->field('id,lecturer_name,lecturer_head,label,curriculum,explain,study,sort,is_show,is_del')
            ->select();
        $data=count($data) > 0 ? $data->toArray() : [];
        foreach ($data as $key=>&$value){
            $value['label'] =json_decode($value['label']);
        }
       $count= self::setWhere()->count();
       return compact('data', 'count');
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
