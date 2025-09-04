<?php


namespace app\wap\model\special;

use app\wap\model\topic\CertificateRelated;
use basic\ModelBasic;
use traits\ModelTrait;
use think\Db;

/**课程观看记录
 * Class SpecialWatch
 * @package app\wap\model\special
 */
class SpecialWatch extends ModelBasic
{
    use ModelTrait;

    /**
     * 素材观看时间
     */
    public static function materialViewing($uid, $data)
    {
        // 找到对应证书记录，得到获取证书获取条件
        $related = CertificateRelated::where(['related'=>$data['special_id'],'obtain'=>1,'is_show'=>1])->find();
        // 如果找到证书，则使用证书获取百分比条件
        if ($related) {
            $condition = $related['condition'];
        } else {
            $condition = 100;
        }
        $viewing = self::where(['uid' => $uid, 'special_id' => $data['special_id'], 'task_id' => $data['task_id']])->find();
        if ($viewing) {
            $dat['viewing_time'] = $data['viewing_time'];
            $dat['percentage'] = $data['percentage'];
            $dat['total'] = $data['total'];
            // 满足完成条件时
            if ($data['percentage'] >= $condition) {
                $dat['is_complete'] = 1;
            }
            return self::edit($dat, $viewing['id']);
        } else {
            $data['uid'] = $uid;
            $data['add_time'] = time();
            return self::set($data);
        }
    }

    /**
     * 查看素材是否观看
     */
    public static function whetherWatch($uid, $special_id = 0, $task_id = 0)
    {
        return self::where(['uid' => $uid, 'special_id' => $special_id, 'task_id' => $task_id])->find();
    }

}
