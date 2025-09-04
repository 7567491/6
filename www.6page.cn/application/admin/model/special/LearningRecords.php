<?php


namespace app\admin\model\special;

use app\admin\model\special\SpecialWatch;
use basic\ModelBasic;
use traits\ModelTrait;
use think\Db;
use app\admin\model\special\Special;
use service\PhpSpreadsheetService;

/**浏览记录
 * Class LearningRecords
 * @package app\admin\model\special
 */
class LearningRecords extends ModelBasic
{
    use ModelTrait;

    /**最后学习时间
     * @param $id
     * @param $uid
     * @return mixed
     */
    public static function lastStudyTime($id,$uid)
    {
        return self::where(['special_id'=>$id,'uid'=>$uid])->value('add_time');
    }

    /**条件处理
     * @param $where
     */
    public static function getOrderWhere($where,$id)
    {
        $model=self::alias('l')->join('User u', 'l.uid=u.uid')
            ->join('Special s','l.special_id=s.id','left');
        if($id) $model = $model->where('l.special_id',$id);
        if(isset($where['uid']) && $where['uid'])$model = $model->where('l.uid',$where['uid']);
        $model = $model->group('l.uid');
        if ($where['data'] != '') {
            $model = self::getModelTime($where, $model, 'l.add_time');
        }
        $model = $model->order('l.add_time desc')->field('l.uid,l.special_id,l.add_time,s.id,s.type,s.title,s.is_light,u.nickname,u.account,u.full_name,u.phone,u.level');
        return $model;
    }

    /**学习记录
     * @param $where
     * @param $id
     */
    public static function specialLearningRecordsLists($where,$id)
    {
        $model =self::getOrderWhere($where,$id);
        if (isset($where['excel']) && $where['excel'] == 1) {
            $data = ($data = $model->select()) && count($data) ? $data->toArray() : [];
        } else {
            $data = ($data = $model->page((int)$where['page'], (int)$where['limit'])->select()) && count($data) ? $data->toArray() : [];
        }
        foreach ($data as $key=>&$value){
            $value['last_study_time']=date('Y-m-d',$value['add_time']);
        }
        if (isset($where['excel']) && $where['excel'] == 1) {
            self::SaveExcel($data);
        }
        $count = self::getOrderWhere($where,$id)->count();
        return compact('count', 'data');
    }

    /**
     * 保存并下载excel
     * $list array
     * return
     */
    public static function SaveExcel($list)
    {
        $export = [];
        foreach ($list as $index => $item) {
            // 查询每个课节进度
            $watchData = SpecialWatch::percen_tage_specials([
                'special_id' => $item['special_id'],
                'page' => 1,
                'limit' => 1000,
                'uid' => $item['uid'],
                'is_light' => $item['is_light'],
                'type' => $item['type']
            ]);
            $watchDataStr = '';
            foreach ($watchData['data'] as $k => $v) {
                $watchDataStr .= $v['title'] . '：' . $v['percentage'] . '%' . PHP_EOL;
            }
            // 去掉最后一个换行
            $watchDataStr = rtrim($watchDataStr, PHP_EOL);
            $export[] = [
                $item['title'],
                $item['uid'],
                $item['account'],
                $item['nickname'],
                $item['full_name'],
                $item['phone'],
                $item['level'] == 1 ? '会员' : '非会员',
                $item['last_study_time'],
                $watchDataStr
            ];
        }
        $filename='学习记录' . time().'.xlsx';
        $head=['课程名称', '用户ID', '账号','昵称','真实姓名','电话','身份', '最后学习时间', '进度详情'];
        PhpSpreadsheetService::outdata($filename,$export,$head);
    }
}
