<?php


namespace app\web\model\special;

use app\web\model\special\SpecialSource;
use app\web\model\special\StoreOrder;
use app\web\model\user\User;
use basic\ModelBasic;
use service\SystemConfigService;
use think\Url;
use traits\ModelTrait;
use think\Db;
use app\web\model\live\LiveStudio;
use app\web\model\live\LivePlayback;
use app\web\model\special\LearningRecords;
use app\web\model\special\SpecialSubject;
use app\web\model\special\SpecialReply;
use app\web\model\material\DataDownload;

/**课程表
 * Class Special
 * @package app\web\model\special
 */
class Special extends ModelBasic
{
    use ModelTrait;

    /**获取课程详情内容
     * @return \think\model\relation\HasOne
     */
    public function profile()
    {
        return $this->hasOne('SpecialContent', 'special_id', 'id')->field('content,is_try,try_content');
    }

    /**获取精简课链接或视频点播ID
     * @return \think\model\relation\HasOne
     */
    public function singleProfile()
    {
        return $this->hasOne('SpecialContent', 'special_id', 'id')->field('link,videoId,video_type,is_try,try_time,try_content');
    }

    public static function getAddTimeAttr($value)
    {
        return date('Y-m-d H:i:s', $value);
    }

    public static function getBannerAttr($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    public static function getLabelAttr($value)
    {
        return is_string($value) ? json_decode($value, true) : $value;
    }

    /**
     * 设置课程显示条件
     * @param string $alias 别名
     * @param null $model model
     * @param bool $isAL 是否起别名,默认执行
     * @return $this
     */
    public static function PreWhere($alias = '', $model = null, $isAL = false)
    {
        if (is_null($model)) $model = new self();
        if ($alias) {
            $isAL || $model = $model->alias($alias);
            $alias .= '.';
        }
        return $model->where(["{$alias}is_del" => 0]);
    }

    /**
     * 获取单个课程的详细信息
     * @param $uid 用户id
     * @param $id 课程id
     * @param $pinkId 拼团id
     * */
    public static function getOneSpecial($uid, $id)
    {
        $special = self::PreWhere()->where('is_light',0)->find($id);
        if (!$special) return self::setErrorInfo('您要查看的课程不存在!');
        if ($special->is_show==0) return self::setErrorInfo('您要查看的课程已下架!');
        $title = $special->title;
        $special->collect = self::getDb('special_relation')->where(['link_id' => $id, 'type' => 0, 'uid' => $uid, 'category' => 1])->count() ? true : false;
        $special->content = htmlspecialchars_decode($special->profile->content);
        $special->profile->content = '';
        return compact('special', 'title');
    }

    /**获取单个精简课
     * @param $uid
     * @param $id
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSingleOneSpecial($uid, $id)
    {
        $special = self::PreWhere()->where('is_light',1)->find($id);
        if (!$special) return self::setErrorInfo('您要查看的课程不存在!');
        if ($special->is_show==0) return self::setErrorInfo('您要查看的课程已下架!');
        $title = $special->title;
        $special->abstract =htmlspecialchars_decode($special->abstract);
        $special->collect = self::getDb('special_relation')->where(['link_id' => $id, 'type' => 0, 'uid' => $uid, 'category' => 1])->count() ? true : false;
        $special->singleProfile=$special->singleProfile;
        return compact('special', 'title');
    }

    /**获取精简课内容
     * @param $id
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSingleSpecialContent($id)
    {
        $special = self::PreWhere()->find($id);
        if (!$special) return self::setErrorInfo('您要查看的课程不存在!');
        if ($special->is_show==0) return self::setErrorInfo('您要查看的课程已下架!');
        $special->content = htmlspecialchars_decode($special->profile->content);
        $special->singleProfile=$special->singleProfile;
        return $special;
    }

    public static function set_my_collection_where($uid,$model,$type)
    {
        return self::PreWhere('a',$model)->join('__SPECIAL_RELATION__ s', 'a.id=s.link_id')->where(['s.type' => $type, 's.uid' => $uid]);
    }
    /**
     * 我的收藏
     * @param int $type 1=收藏,0=我的购买
     * @param int $page 页码
     * @param int $limit 每页显示条数
     * @param int $uid 用户uid
     * @return array
     * */
    public static function getGradeList($page, $limit, $uid,$is_member,$active = 0)
    {
        if($active){
            $model=self::set_my_collection_where($uid,new DataDownload,1);
            $list = $model->order('a.sort desc')->field('a.*')->page($page, $limit)->select();
            $count= self::set_my_collection_where($uid,new DataDownload,1)->count();
        }else{
            $model=self::set_my_collection_where($uid,new self,0);
            if(!$is_member) $model=$model->where(['a.is_mer_visible' => 0]);
            $list = $model->order('a.sort desc')->field('a.*')->page($page, $limit)->select();
            $modelCount= self::set_my_collection_where($uid,new self,0);
            if(!$is_member) $modelCount=$modelCount->where(['a.is_mer_visible' => 0]);
            $count=$modelCount->count();
        }
        foreach ($list as &$item) {
            if(!$active){
                $item['image'] = get_oss_process($item['image'], 4);
                if (is_string($item['label'])) $item['label'] = json_decode($item['label'], true);
                $item['s_id'] =$item['id'];
                $item['count']=self::numberChapters($item['type'],$item['s_id']);
                if($item['is_light']){
                    $item['type']=self::lightType($item['light_type']);
                }
            }
        }
        return compact('list','count');
    }

    public static function set_my_where($uid)
    {
       return self::PreWhere('s')->join('SpecialBuy b', 's.id=b.special_id')->group('b.special_id')->where(['s.is_show' => 1, 'b.is_del' => 0, 'b.uid' => $uid]);
    }
    /**我的课程
     * @param $page
     * @param $limit
     * @param $uid
     * @param $is_member
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getMySpecialList($page, $limit, $uid)
    {
        $model = self::set_my_where($uid)->order('b.add_time desc');
        $list = $model->field('s.*')->page($page, $limit)->select();
        $list = count($list) > 0 ? $list->toArray() : [];
        foreach ($list as &$item) {
            $item['image'] = get_oss_process($item['image'], 4);
            if (is_string($item['label'])) $item['label'] = json_decode($item['label'], true);
            $item['s_id'] =$item['id'];
            $item['count']=self::numberChapters($item['type'],$item['s_id']);
            if($item['is_light']){
                $item['type']=self::lightType($item['light_type']);
            }
        }
        $count= self::set_my_where($uid)->count();
        return compact('list','count');
    }

    /**
     * 获取某个课程的详细信息
     * @param int $id 课程id
     * @return array
     * */
    public static function getSpecialInfo($id)
    {
        $special = self::PreWhere()->find($id);
        if (!$special) return self::setErrorInfo('没有找到此课程');
        $special->abstract = self::HtmlToMbStr($special->abstract);
        return $special->toArray();
    }

    /**
     * 设置查询条件
     * @param $where
     * @return $this
     */
    public static function setWhere($where,$is_member,$alias = '')
    {
        $model = self::PreWhere($alias);
        if ($alias) {
            $alias .= '.';
        }
        if (isset($where['subject_id']) && $where['subject_id']) {
            $model = $model->where("{$alias}subject_id", $where['subject_id']);
        }else if (isset($where['subject_id']) && $where['subject_id']==0 && isset($where['cate_id']) && $where['cate_id']){
            $subject_ids=SpecialSubject::subjectId($where['cate_id']);
            $model = $model->where("{$alias}subject_id",'in',$subject_ids);
        }
        if (isset($where['search']) && $where['search']!='') {
            $model = $model->where("{$alias}title", 'LIKE', "%$where[search]%");
        }
        if (isset($where['type']) && $where['type']!='') {
            $model = $model->where("{$alias}type", $where['type']);
        }
        if (isset($where['is_pay']) && $where['is_pay']!='') {
            $model = $model->where("{$alias}pay_type", $where['is_pay']);
        }
        if(!$is_member) $model =$model->where(["{$alias}is_mer_visible" => 0]);
        $baseOrder = '';
        if (isset($where['salesOrder']) && $where['salesOrder']) {
            $baseOrder = $where['salesOrder'] == 'desc' ? "{$alias}sales DESC" : "{$alias}sales ASC";//销量
        }
        if (isset($where['scoreOrder']) && $where['scoreOrder']) {
            $baseOrder = $where['scoreOrder'] == 'desc' ? "{$alias}score DESC" : "{$alias}score ASC";//评价
        }
        if ($baseOrder) $baseOrder .= ',';
        $model = $model->order($baseOrder . "{$alias}sort DESC,"."{$alias}add_time DESC");
        return $model->where("{$alias}is_show", 1);
    }

    /**
     * 获取课程列表
     * @param $where
     * @return mixed
     */
    public static function get_special_list($where,$is_member)
    {
        $field = ['browse_count', 'image', 'title','type','sort','IFNULL(browse_count,0) + IFNULL(fake_sales,0) as sales','is_light','light_type','is_mer_visible', 'money','member_money','subject_id','pay_type', 'label', 'id','fake_sales','add_time'];
        $model = self::setWhere($where,$is_member)->field($field);
        $data = $model->page($where['page'], $where['limit'])->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            $item['count']=self::numberChapters($item['type'],$item['id']);
            $count=self::learning_records($item['id']);
            $item['browse_count']=processingData(bcadd($count,$item['fake_sales'],0));
            if($item['is_light']){
                $item['type']=self::lightType($item['light_type']);
            }
        }
        $count=$model = self::setWhere($where,$is_member)->count();
        return compact('data', 'count');
    }

    /**获得课程真实学习人数
     * @param int $special_id
     * @return int
     */
    public static function learning_records($special_id=0)
    {
        $uids=LearningRecords::where(['special_id'=>$special_id])->column('uid');
        $uids=array_unique($uids);
        return count($uids);
    }

    /**讲师名下课程
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLecturerSpecialList($lecturer_id=0,$page=1,$limit=10)
    {
        $field = ['browse_count', 'image', 'title','type', 'money', 'pink_money' ,'is_light','light_type' ,'is_mer_visible' ,'member_money', 'is_pink', 'subject_id', 'pay_type', 'label', 'id','is_show','is_del','lecturer_id'];
        $model = self::PreWhere();
        $model = $model->where(['is_show'=>1,'lecturer_id'=>$lecturer_id])->order('sort desc,id desc');
        $data  = $model->field($field)->page($page, $limit)->select();
        $data  = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            $item['count']=self::numberChapters($item['type'],$item['id']);
        }
        $count= self::PreWhere()->where(['is_show'=>1,'lecturer_id'=>$lecturer_id])->count();
        return compact('data', 'count');
    }


    /**课程下章节数量
     * @param int $type
     * @param int $id
     * @return int|string
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function numberChapters($type=0,$id=0){
        $count=0;
        if($type!=5 && $type!=4){
            $specialSourceId = SpecialSource::getSpecialSource($id);
            if($specialSourceId) $count=count($specialSourceId);
        }else if($type==5){
            $specialSourceId = SpecialSource::getSpecialSource($id);
            if(count($specialSourceId)){
                $specialSource=$specialSourceId->toArray();
                foreach ($specialSource as $key=>$value){
                    $specialSourcetaskId = SpecialSource::getSpecialSource($value['source_id']);
                    if(count($specialSourcetaskId)==0){
                        $is_light = self::PreWhere()->where('id',$value['source_id'])->value('is_light');
                        if($is_light){
                            $count=bcadd($count,1,0);
                        }
                    }else{
                        $count=bcadd($count,count($specialSourcetaskId),0);
                    }
                }
            }
            $count=(int)$count;
        }else if($type==4){
            $liveStudio = LiveStudio::where(['special_id' => $id])->find();
            if (!$liveStudio) return $count=0;
            if (!$liveStudio['stream_name']) return $count=0;
            if ($liveStudio['is_playback'] == 1) {
                $where['stream_name']=$liveStudio['stream_name'];
                $where['start_time']='';
                $where['end_time']='';
                $count= LivePlayback::setUserWhere($where)->count();
            }
        }
        return $count;
    }

    /**精简课类型处理
     * @param $light_type
     * @return int
     */
    public static function lightType($light_type)
    {
        switch ($light_type) {
            case 1:
                $type=1;
                break;
            case 2:
                $type=2;
                break;
            case 3:
                $type=3;
                break;
        }
        return $type;
    }

    /**首页课程排行
     * @param $is_member
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function course_ranking_list($is_member,$order='browse_count',$limit=3)
    {
        $field = ['browse_count', 'member_pay_type', 'member_money','fake_sales','IFNULL(browse_count,0) + IFNULL(fake_sales,0) as sales','image', 'title','type', 'money', 'pink_money' ,'is_light','light_type' ,'is_mer_visible', 'subject_id','pay_type', 'label', 'id','is_show','is_del'];
        $model = self::PreWhere();
        if(!$is_member) $model=$model->where(['is_mer_visible' => 0]);
        if($order=='browse_count'){
            $model=$model->order('sales desc');
        }else if($order=='add_time') {
            $model=$model->order('add_time desc');
        }else{
            $model=$model->order('sort desc,id desc');
        }
        $list =$model->where(['is_show'=>1])->limit($limit)->field($field)->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as $key=>&$item){
            $count=Special::learning_records($item['id']);
            $item['sales'] = bcadd($item['fake_sales'],$count,0);
            $item['browse_count'] = processingData($item['sales']);
        }
        if($order=='browse_count'){
            $browse = array_column($list,'sales');
            array_multisort($browse,SORT_DESC,$list);
        }
        return $list;
    }

    /**好课推荐列表
     * @param $is_member
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function good_class_recommend_list($is_member,$limit=3)
    {
        $model = self::PreWhere()->field(['image', 'member_pay_type', 'member_money', 'title','type','sort','score','money','is_light','light_type' ,'is_mer_visible','pay_type','label', 'id','is_show']);
        if(!$is_member) $model=$model->where(['is_mer_visible' => 0]);
        $list =$model->where(['is_show'=>1])->limit($limit)->order('score desc,sort desc')->select();
        $list = count($list) ? $list->toArray() : [];
        return $list;
    }

    /**首页分类推荐
     * @param $is_member
     * @param $cateId
     * @return array
     */
    public static function cate_special_recommen_list($is_member,$cateId)
    {
        $field = ['browse_count','fake_sales','sort','IFNULL(browse_count,0) + IFNULL(fake_sales,0) as sales','image', 'title','type', 'money', 'pink_money' ,'is_light','light_type' ,'is_mer_visible', 'subject_id','pay_type', 'label', 'id','is_show','is_del'];
        $model = self::PreWhere();
        $model = $model->where(['is_show'=>1]);
        if(!$is_member) $model=$model->where(['is_mer_visible' => 0]);
        if(count($cateId)) $model=$model->where('subject_id','in',$cateId);
        $model=$model->order('sales desc,sort desc')->limit(4);
        $list =$model->field($field)->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as $key=>&$item){
            $count=Special::learning_records($item['id']);
            $item['sales'] = bcadd($item['fake_sales'],$count,0);
            $item['browse_count'] =processingData($item['sales']);
        }
        $browse = array_column($list,'sales');
        array_multisort($browse,SORT_DESC,$list);
        return $list;
    }

    /**
     * 获取直播课程列表
     * @param $where
     * @return mixed
     */
    public static function get_live_special_list_all($where,$is_member)
    {
        $field = ['s.browse_count', 's.image','IFNULL(s.browse_count,0) + IFNULL(s.fake_sales,0) as sales', 's.title','s.type','s.sort','s.is_light','s.light_type','s.is_mer_visible', 's.money','s.member_money','s.subject_id','s.lecturer_id','s.pay_type', 's.label', 's.id','s.fake_sales','s.add_time','l.is_play','l.playback_record_id', 'l.start_play_time'];
        $model = self::setWhere($where,$is_member,'s')->field($field);
        $model =$model->join('__LIVE_STUDIO__ l', 's.id=l.special_id', 'LEFT');
        $data = $model->page($where['page'], $where['limit'])->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            if($item['lecturer_id']) $item['lecturer_name']=Lecturer::where('id',$item['lecturer_id'])->value('lecturer_name');
            else $item['lecturer_name']='';
            $liveInfo=LiveStudio::where('special_id',$item['id'])->field('start_play_time,stop_play_time,is_fake')->find();
            $start_play_time = $liveInfo['start_play_time'];
            if ($start_play_time) {
                $item['start_play_time'] = date('Y-m-d H:i', strtotime($start_play_time));
            }
            if ($item['playback_record_id'] && !$item['is_play']) {
                $item['status'] = 2; //没在直播 有回放
            } else if ($item['is_play']) {
                $item['status'] = 1; //正在直播
            } else if (!$item['playback_record_id'] && !$item['is_play'] && strtotime($item['start_play_time']) > time()) {
                $item['status'] = 3; //等待直播
            }else{
                $item['status'] = 4; //直播结束
            }
            // 对虚拟直播的处理
            if ($liveInfo['is_fake']) {
                $start_play_time_stamp = strtotime($start_play_time);
                $stop_play_time_stamp = strtotime($liveInfo['stop_play_time']);
                $current_time_stamp = time();
                if ($current_time_stamp >= $start_play_time_stamp && $current_time_stamp < $stop_play_time_stamp) {
                    $item['status'] = 1; //正在直播
                } else if ($current_time_stamp < $start_play_time_stamp) {
                    $item['status'] = 3; //等待直播
                } else if ($current_time_stamp >= $stop_play_time_stamp) {
                    $item['status'] = 2; //没在直播 有回放
                }
            }
            $count=Special::learning_records($item['id']);
            $item['browse_count'] = processingData(bcadd($item['fake_sales'],$count,0));
        }
        $count=$model = self::setWhere($where,$is_member,'s')->join('__LIVE_STUDIO__ l', 's.id=l.special_id', 'LEFT')->count();
        return compact('data', 'count');
    }

    /**条件处理
     * @param $is_member
     * @param int $type
     * @return Special
     */
    public static function set_where_pro($is_member,$type=0)
    {
        $model=self::PreWhere()->where('is_show',1);
        if(!$is_member) $model = $model->where(['is_mer_visible' => 0]);
        if($type) $model = $model->where(['type' => $type]);
        return $model;
    }

    /**
     * 获取推广课程列表
     * @param array $where 查询条件
     * @param int $uid 用户uid
     * @return array
     * */
    public static function getSpecialSpread($where, $is_member, $uid)
    {
        $store_brokerage_ratio = SystemConfigService::get('store_brokerage_ratio');
        $store_brokerage_ratio = bcdiv($store_brokerage_ratio, 100, 2);
        $ids = SpecialSubject::where('a.is_show', 1)->alias('a')->join('__SPECIAL__ s', 's.subject_id=a.id')->column('a.id');
        $subjectIds = [];
        foreach ($ids as $item) {
            if (self::PreWhere()->where('is_show', 1)->where('subject_id', $item)->count()) array_push($subjectIds, $item);
        }
        $model = SpecialSubject::where('is_show', 1)->order('sort desc')->field('id,name');
        if ($where['grade_id']) $model = $model->where('grade_id', $where['grade_id']);
        $query = $model->where('id', 'in', $subjectIds)->paginate((int)$where['limit']);
        $list = $query->toJson();
        $list = json_decode($list, true);
        $count = $query->total();
        $data = count($list) ? $list : [];
        foreach ($data as &$item) {
            $itm= self::PreWhere()->where('is_show', 1)->where('subject_id', $item['id'])
                ->field(['image', 'id','is_mer_visible', 'title', 'money']);
            if(!$is_member) $itm =$itm->where(['is_mer_visible' => 0]);
            $item['list'] =  $itm->order('sort desc')->select();
            if (count($item['list'])) $item['list'] = $item['list']->toArray();
            foreach ($item['list'] as &$value) {
                $special_id = $value['id'];
                $special = Special::getSpecialInfo($special_id);
                if($special['is_light']){
                    $value['spread_url'] = SystemConfigService::get('site_url') . url('/single-course') . '?id=' . $special['id'] . '&link_pay_uid=' .$uid. '&link_pay=1&spread_uid=' . $uid . '#link_pay';
                }else {
                    $value['spread_url'] = SystemConfigService::get('site_url') . url('/view-course') . '?id=' . $special['id'] . '&link_pay_uid=' . $uid . '&link_pay=1&spread_uid=' . $uid . '#link_pay';
                }
                $value['image'] = get_oss_process($value['image'], 4);
                if($value['money']>0) $value['spread_money'] = bcmul($value['money'], $store_brokerage_ratio, 2);
                else $value['spread_money'] =0;
            }
        }
//        $page = (int)$where['page'] + 1;
        return compact('data',  'count');
    }

}
