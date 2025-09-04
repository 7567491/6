<?php



namespace app\admin\model\system;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * Class SystemBucket
 * @package app\admin\model\system
 */
class SystemBucket extends ModelBasic
{
    use ModelTrait;

    /**条件处理
     * @param $where
     */
    public static function setWhere($where)
    {
        $model=new self();
        if($where['title']!='') $model=$model->where('bucket_name','like',"%$where[title]%");
        if($where['endpoint']!='') $model=$model->where('endpoint',$where['endpoint']);
        $model=$model->where('is_del',0);
        return $model;
    }

    /**获取数据表中的储存空间信息
     * @param $where
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function bucKetList($where){

        $data=self::setWhere($where)->order('add_time desc')->select();
        $data = count((array)$data) ? $data->toArray() : [];
        $count = self::setWhere($where)->count();
        return compact('data','count');
    }

    /**
     * 拉取保存存储空间
     *  @param $list
     */
    public static function addListBucket($list=[])
    {
        foreach ($list as $key=>$value){
            $data=[
                'bucket_name'=>$value->getName(),
                'endpoint'=>$value->getLocation().'.aliyuncs.com',
                'domain_name'=>$value->getName().'.'.$value->getLocation().'.aliyuncs.com',
                'creation_time'=>$value->getCreatedate(),
                'add_time'=>time()
            ];
            if(!self::be(['bucket_name'=>$data['bucket_name']])){
                self::set($data);
            }
        }
        return true;
    }
    /**
     * 保存存储空间
     *  @param $value
     */
    public static function addBucket($value=[])
    {
        $data=[
            'bucket_name'=>$value['bucket_name'],
            'endpoint'=>$value['endpoint'],
            'domain_name'=>$value['bucket_name'].'.'.$value['endpoint'],
            'creation_time'=>date('Y/m/d H:i',time()),
            'add_time'=>time()
        ];
        $res=false;
        if(!self::be(['bucket_name'=>$data['bucket_name'], 'is_del' => 0])){
            $res=self::set($data);
        }
        return $res;
    }
}
