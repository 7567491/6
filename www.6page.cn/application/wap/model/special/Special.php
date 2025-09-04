<?php


namespace app\wap\model\special;

use app\wap\model\special\SpecialSource;
use app\wap\model\store\StoreOrder;
use app\wap\model\store\StorePink;
use app\wap\model\user\User;
use basic\ModelBasic;
use service\SystemConfigService;
use think\Url;
use traits\ModelTrait;
use think\Db;
use app\wap\model\live\LiveStudio;
use app\wap\model\live\LivePlayback;
use app\wap\model\special\LearningRecords;
use app\wap\model\special\SpecialSubject;
use app\wap\model\material\DataDownload;

/**课程 model
 * Class Special
 * @package app\wap\model\special
 */
class Special extends ModelBasic
{
    use ModelTrait;

    public function profile()
    {
        return $this->hasOne('SpecialContent', 'special_id', 'id')->field('content,is_try,try_content');
    }
    public function singleProfile()
    {
        return $this->hasOne('SpecialContent', 'special_id', 'id')->field('link,videoId,video_type,is_try,try_time,try_content');
    }

    //动态赋值
    public static function getPinkStrarTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
    }

    public static function getPinkEndTimeAttr($value)
    {
        return $value ? date('Y-m-d H:i:s', $value) : '';
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
        self::setPinkSpecial();
        if (is_null($model)) $model = new self();
        if ($alias) {
            $isAL || $model = $model->alias($alias);
            $alias .= '.';
        }
        return $model->where(["{$alias}is_del" => 0]);
    }

    /**
     * 获取拼团详情页的课程详情和分享连接
     * @param string $order_id 订单id
     * @param int $pinkId 当前拼团id
     * @param int $uid 当前用户id
     * @return array
     * */
    public static function getPinkSpecialInfo($order_id, $pinkId, $uid)
    {
        $special = self::PreWhere()->where('id', StoreOrder::where('order_id', $order_id)->value('cart_id'))
            ->field(['image', 'title', 'abstract', 'money', 'label', 'id','is_light','light_type','is_mer_visible', 'is_pink', 'pink_money'])->find();
        if (!$special) return [];
        $special['image'] = get_oss_process($special['image'], 4);
        if($special['is_light']){
            $special['link'] = SystemConfigService::get('site_url') . Url::build('/m/single-course') . '?id=' . $special['id'] . '&pinkId=' . $pinkId . '&partake=1#partake';
        }else{
            $special['link'] = SystemConfigService::get('site_url') . Url::build('/m/view-course') . '?id=' . $special['id'] . '&pinkId=' . $pinkId . '&partake=1#partake';
        }
        $special['abstract'] = self::HtmlToMbStr($special['abstract']);
        return $special;
    }

    /**
     * 设置拼团到时间的课程
     * */
    public static function setPinkSpecial()
    {
        self::where('pink_strar_time', '<', time())->where('pink_end_time', '<', time())->update([
            'is_pink' => 0,
            'pink_strar_time' => 0,
            'pink_end_time' => 0
        ]);
    }

    /**
     * 获取单个课程的详细信息,拼团信息,拼团用户信息
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
        $swiperlist = json_encode($special->banner);
        $special = json_encode($special->toArray());
        return compact('swiperlist', 'special', 'title');
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
        $special = json_encode($special->toArray());
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
    public static function getSingleImgSpecialContent($id)
    {
        $special = self::PreWhere()->find($id);
        if (!$special) return self::setErrorInfo('您要查看的课程不存在!');
        if ($special->is_show==0) return self::setErrorInfo('您要查看的课程已下架!');

        $data['title'] = $special->title;
        $data['image'] = $special->image;
        $data['profile']=$special->profile;
        $data['profile']['content']=htmlspecialchars_decode($special->profile->content);
        $data['profile']['try_content']=htmlspecialchars_decode($special->profile->try_content);
        $data['content'] = htmlspecialchars_decode($special->profile->content);
        return $data;
    }

    /**
     * 我的课程
     * @param int $active 1=购买的课程,0=赠送的课程
     * @param int $page 页码
     * @param int $limit 每页显示条数
     * @param int $uid 用户uid
     * @return array
     * */
    public static function getMyGradeList($page, $limit, $uid,$is_member,$active = 0)
    {
        $model = self::PreWhere('s', SpecialBuy::where('s.uid', $uid)->where('s.is_del', 0)->group('s.special_id')
            ->order('a.sort desc,s.add_time desc')->alias('s'), true)
            ->join('__SPECIAL__ a', 'a.id=s.special_id');
        $list = $model->field('a.*,a.type as types,s.*')->where(['a.is_del'=>0,'a.is_show'=>1])->page($page, $limit)->select();
        $list=count($list)>0 ? $list->toArray() :[];
        foreach ($list as &$item) {
            $item['image'] = get_oss_process($item['image'], 4);
            if (is_string($item['label'])) $item['label'] = json_decode($item['label'], true);
            $id=$item['special_id'];
            $item['s_id'] =$id;
            $item['count']=self::numberChapters($item['types'],$item['s_id']);
            if($item['is_light']){
                $item['type']=self::lightType($item['light_type']);
            }
         }
        $page += 1;
        return compact('list', 'page');
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
            $model = DataDownload::PreWhere('a')->where('s.uid', $uid)->where('s.type', 1)->join('__SPECIAL_RELATION__ s', 'a.id=s.link_id');
            $list = $model->order('a.sort desc')->field('a.*')->page($page, $limit)->select();
        }else{
            $model = self::PreWhere('a')->where('s.uid', $uid)->where('s.type', 0)->join('__SPECIAL_RELATION__ s', 'a.id=s.link_id');
            if(!$is_member) $model=$model->where(['a.is_mer_visible' => 0]);
            $list = $model->order('a.sort desc')->field('a.*,a.type as types')->page($page, $limit)->select();
        }
        $list=count($list)>0 ? $list->toArray() :[];
        foreach ($list as &$item) {
            if(!$active){
                $item['image'] = get_oss_process($item['image'], 4);
                if (is_string($item['label'])) $item['label'] = json_decode($item['label'], true);
                $id=$item['id'];
                $item['s_id'] =$id;
                $item['count']=self::numberChapters($item['types'],$item['s_id']);
                if($item['is_light']){
                    $item['type']=self::lightType($item['light_type']);
                }
            }
        }
        $page += 1;
        return compact('list', 'page');
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
     * 获取推广课程列表
     * @param array $where 查询条件
     * @param int $uid 用户uid
     * @return array
     * */
    public static function getSpecialSpread($where, $is_member)
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
        $list = $model->where('id', 'in', $subjectIds)->page((int)$where['page'], (int)$where['limit'])->select();
        $data = count($list) ? $list->toArray() : [];
        foreach ($data as &$item) {
            $itm= self::PreWhere()->where('is_show', 1)->where('subject_id', $item['id'])
                ->field(['image', 'id','is_mer_visible', 'title', 'money']);
            if(!$is_member) $itm =$itm->where(['is_mer_visible' => 0]);
            $item['list'] =  $itm->order('sort desc')->select();
            if (count($item['list'])) $item['list'] = $item['list']->toArray();
            foreach ($item['list'] as &$value) {
                $value['image'] = get_oss_process($value['image'], 4);
                if($value['money']>0) $value['spread_money'] = bcmul($value['money'], $store_brokerage_ratio, 2);
                else $value['spread_money'] =0;
            }
        }
        $page = (int)$where['page'] + 1;
        return compact('data', 'page');
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

    /**讲师名下课程
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getLecturerSpecialList($lecturer_id=0,$page=1,$limit=10)
    {
        $field = ['browse_count', 'image', 'title','type', 'money', 'pink_money' ,'is_light','light_type' ,'is_mer_visible' , 'is_pink', 'subject_id', 'label', 'id','is_show','is_del','lecturer_id'];
        $model = self::PreWhere();
        $model = $model->where(['is_show'=>1,'lecturer_id'=>$lecturer_id])->order('sort desc,id desc');
        $list =$model->field($field)->page($page, $limit)->select();
        $list = count($list) ? $list->toArray() : [];
        return $list;
    }

    /**拼团课程
     * @param int $page
     * @param int $limit
     */
    public static function getPinkSpecialList($page=1,$limit=10, $is_member)
    {
        $field = ['browse_count', 'image' ,'is_light','light_type','is_mer_visible', 'title','type', 'money', 'pink_money', 'is_pink', 'subject_id', 'label', 'id','is_show','is_del','lecturer_id','pink_number'];
        $model = self::PreWhere();
        if(!$is_member) $model =$model->where(['is_mer_visible' => 0]);
        $model = $model->where(['is_show'=>1,'is_pink'=>1])->order('sort desc,id desc');
        $list =$model->field($field)->page($page, $limit)->select();
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as &$item) {
            $item['count'] =StorePink::where(['status'=>2,'cid'=>$item['id']])->count();
        }
        return $list;
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

    /**精简课 类型
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

    /**
     * 获取单独分销设置
     */
    public static function getIndividualDistributionSettings($id=0)
    {
        $data=self::where('id',$id)->field('is_alone,brokerage_ratio,brokerage_two')->find();
        if($data) return $data;
        else return [];
    }

    // 获取课程浏览记录
    public static function get_special_record($where)
    {
        $model = self::alias('c')
            ->join('__SPECIAL_RECORD__ uc', 'c.id = uc.special_id')
            ->where('uc.uid', $where['uid'])
            ->where('c.title', 'like', '%' . $where['search'] . '%')
            ->field('c.browse_count, c.image, c.title, c.type, c.sort, c.is_light, c.light_type, c.is_mer_visible, c.money, c.member_money, c.subject_id, c.pay_type, c.label, c.id, c.fake_sales, c.add_time');

        $data = $model->page($where['page'], $where['limit'])->order('uc.add_time desc')->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            $item['count']=self::numberChapters($item['type'],$item['id']);
            $count=self::learning_records($item['id']);
            $item['browse_count']=processingData(bcadd($count,$item['fake_sales'],0));
            if($item['is_light']){
                $item['type']=self::lightType($item['light_type']);
            }
        }
        $count=$model = self::alias('c')
            ->join('__SPECIAL_RECORD__ uc', 'c.id = uc.special_id')
            ->where('uc.uid', $where['uid'])
            ->where('c.title', 'like', '%' . $where['search'] . '%')->count();
        return compact('data', 'count');
    }
}
