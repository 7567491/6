<?php


namespace app\wap\model\special;

use app\wap\model\user\User;
use basic\ModelBasic;
use traits\ModelTrait;

/**课程关联素材
 * Class SpecialCourse
 * @package app\wap\model\special
 */
class SpecialCourse extends ModelBasic
{
    use ModelTrait;

    /**
     * @param $special_id
     * @param int $limit
     * @param int $page
     * @param int $uid
     * @param $is_member
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSpecialSourceList($special_id, $limit = 10, $page = 1,$uid=0,$is_member)
    {
        $special = Special::where('id',$special_id)->where(['is_del'=>0,'is_show'=>1])->find();
        if (!$special) return compact('page', 'list');
        //获得套餐课下面的课程
        $cloumnSource = SpecialSource::get_special_source_list($special_id,$is_member,$special['type'],$limit,$page);
        $list = array();
        if (!$cloumnSource) return compact('page', 'list');
        foreach ($cloumnSource as $k => $v) {
            if ($special['type'] == SPECIAL_COLUMN) {
                $cloumnTask = Special::where('id',$v['source_id'])->where(['is_del'=>0,'is_show'=>1])
                    ->field('id,is_del,is_light,is_mer_visible,is_show,light_type,title,type')->find();
                if(!$cloumnTask) continue;
                $cloumnTask = $cloumnTask ? $cloumnTask->toArray() : [];
                if(!$is_member && $cloumnTask['is_mer_visible']==1) continue;
                    //获得课程下面的素材
                    $specialTask = array();
                    $specialSource = SpecialSource::getSpecialSource($v['source_id']);
                    if(count($specialSource)>0) {
                        foreach ($specialSource as $sk => $sv) {
                            $task = SpecialTask::defaultWhere()->where('id',$sv['source_id'])->field('id,special_id,title,type,is_pay,sort,is_show,live_id,is_try,try_time,try_content')->find();
                            if(!$task) continue;
                            $task =  $task->toArray();
                            $task['special_id'] = $sv['special_id'];
                            $task['is_free'] = $sv['pay_status'];
                            $task['pay_status'] = $sv['pay_status'];
                            $taskSpecialIsPay = self::specialIsPay($special_id,$uid);
                            if (!$taskSpecialIsPay){//如果整个课程免费，那么里面素材都免非，否则就默认素材本身的状态
                                $task['pay_status'] = $taskSpecialIsPay;
                            }
                            $task['watch'] = SpecialWatch::whetherWatch($uid, $sv['special_id'], $sv['source_id']);
                            $specialTask[] = $task;
                        }
                    }

                    if($cloumnTask['type']!=6 && $cloumnTask['is_light']==0) {
                        $cloumnTask['special_task'] = $specialTask;
                    }else{
                        $watch = SpecialWatch::whetherWatch($uid, $cloumnTask['id'], 0);
                        $cloumnTask['special_task'][0] = $cloumnTask;
                        $cloumnTask['special_task'][0]['watch'] = $watch;
                    }
                    $cloumnTask['pay_status'] = $v['pay_status'];//付费,先默认素材本身的付费状态
                    $cloumnTask['is_free'] = $v['pay_status'];
                    $specialIsPay = self::specialIsPay($v['source_id'],$uid);
                    if (!$specialIsPay){//如果整个课程免费，那么里面素材都免非，否则就默认素材本身的状态
                        $cloumnTask['pay_status'] = $specialIsPay;
                    }
                    $cloumnTask['cloumn_special_id'] = $special_id;
                    if ($cloumnTask['is_show'] == 1) {
                        $list[] = $cloumnTask;
                    }
            } else {
                    $task = SpecialTask::getSpecialTaskOne($v['source_id']);
                    if(!$task) continue;
                    $task =  $task->toArray();
                    $task['pay_status'] = $v['pay_status'];//付费
                    $task['is_free'] = $v['pay_status'];
                    $specialIsPay = self::specialIsPay($special_id,$uid);
                    if (!$specialIsPay){//如果整个课程免费，那么里面素材都免非，否则就默认素材本身的状态
                        $task['pay_status'] = $specialIsPay;
                    }
                    $task['special_id'] = $special_id;
                    if ($task['is_show'] == 1 && $special['is_show'] == 1) {
                        $list[] = $task;
                    }
            }
        }
        $page++;
        return compact('page', 'list');
    }

    /**获取套餐课下的课程
     * @param $special_id
     * @param bool $source_id
     * @param int $limit
     * @param int $page
     * @param int $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function get_cloumn_special($special_id, $source_id = 0, $limit = 10, $page = 1,$uid=0,$is_member)
    {
        $special = Special::where('id',$special_id)->where(['is_del'=>0,'is_show'=>1])->find();
        if (!$special) return [];
        $cloumn_source = SpecialSource::get_special_source_list($special_id,$is_member,$special['type'],$limit,$page);
        if (!$cloumn_source) return [];
        $list = array();
        foreach ($cloumn_source as $k => $v) {
            $task_special = Special::where('id',$v['source_id'])->where(['is_del'=>0,'is_show'=>1])->find();
            if(!$task_special) continue;
            if(!$is_member && $task_special['is_mer_visible']==1) continue;
            $specialIsPay = self::specialIsPay($v['special_id'],$uid);
            $task_special['is_free'] = $v['pay_status'];
            $task_special['pay_status'] = $specialIsPay;
            if ($task_special['is_show'] == 1) {
                $list[] = $task_special;
            }
        }
        $page++;
        return compact('page', 'list');
    }

    /**课程是否需要付费
     * @param $special_id
     * @return int
     * @throws \think\exception\DbException
     */
    public static function specialIsPay($special_id,$uid)
    {
        if (!$special_id) return false;
        $special = Special::get($special_id);
        if (!$special) return false;
        $specialIsPay = 1;//收费
        if(!$uid) return $specialIsPay;
        $uid = User::getActiveUid();
        $isMember = User::getUserInfo($uid);
        $isPay = SpecialBuy::PaySpecial($special['id'], $uid);
        if ($special['pay_type'] == 0) {//套餐课里面整个课程免费
            $specialIsPay = 0;//免费
        }
        if ($isPay === false && $special['pay_type'] == 0 && $special['is_pink'] == 0) {//没有购买，
            $specialIsPay = 0;//免费
        }
        if ($isMember['level'] > 0 && $special['member_pay_type'] == 0) {//会员，
            $specialIsPay = 0;//免费
        }
        if ($isPay) {//购买过，
            $specialIsPay = 0;//免费
        }
        return $specialIsPay;
    }

}
