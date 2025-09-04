<?php


namespace app\admin\model\special;

use app\admin\model\special\Special as SpecialModel;
use app\admin\model\system\RecommendRelation;
use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class SpecialSource 课程素材关联表
 * @package app\admin\model\special
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
    public static function getSpecialSource($special_id = false, $source_id = false)
    {
        $where = array();
        $data = self::where($where);
        if ($special_id && is_numeric($special_id)) {
            $where['special_id'] = $special_id;
            $data->where($where);
        }
        if ($source_id) {
            if (!is_array($source_id)) {
                $where['source_id'] = $source_id;
                $data->where($where);
            } else {
                $data->whereIn('source_id', $source_id);
            }
        }
        return $data->order('sort desc,id desc')->select();
    }

    /**更新及添加课程素材
     * @param $source_list_ids  一维数组，素材id
     * @param int $special_id 课程id
     * @return bool
     */
    public static function saveSpecialSource($source_list_ids,$special_id=0,$special_type=1,$data=[])
    {
        if (!$special_id || !is_numeric($special_id)) {
            return false;
        }
        if (!$source_list_ids || !is_array($source_list_ids)) {
            return false;
        }
        try {
            $specialSourceAll = self::getSpecialSource($special_id)->toArray();
            if ($specialSourceAll) {
                self::where(['special_id' => $special_id])->delete();
            }
            $inster['special_id'] = $special_id;
            foreach ($source_list_ids as $sk => $sv) {
                if($special_type==SPECIAL_COLUMN){
                    $special=SpecialModel::where('id',$sv->id)->field('pay_type,member_pay_type')->find();
                    if($data['pay_type']==1 && $data['member_pay_type']==0){
                        if($special['pay_type']==1 && $special['member_pay_type']==1){
                            SpecialModel::where('id',$sv->id)->update(['member_pay_type'=>0,'member_money'=>0]);
                        }
                        $inster['pay_status'] = 1;
                    }else if($data['pay_type']==0){
                        if($special['pay_type']==1){
                            SpecialModel::where('id',$sv->id)->update(['member_pay_type'=>0,'member_money'=>0,'pay_type'=>0,'money'=>0]);
                        }
                        $inster['pay_status'] = 0;
                    }
                    SpecialModel::where('id',$sv->id)->update(['validity'=>$data['validity']]);
                }else{
                    $inster['pay_status'] = $sv->pay_status;
                }
                $inster['source_id'] = $sv->id;
                $inster['sort'] = $sv->sort;
                $inster['add_time'] = time();
                self::set($inster);
            }
                return true;
        } catch (\Exception $e) {
            return false;
        }
    }

}
