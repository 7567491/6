<?php


namespace app\admin\controller\special;

use app\admin\controller\AuthController;
use app\admin\model\live\LiveGoods;
use app\admin\model\special\SpecialBarrage;
use app\admin\model\system\SystemConfig;
use app\admin\model\live\LiveStudio;
use app\admin\model\store\StoreProduct;
use app\admin\model\store\StoreCategory;
use app\admin\model\special\Special as SpecialModel;
use app\admin\model\special\Special;
use app\admin\model\special\SpecialBuy;
use app\admin\model\special\Lecturer as LecturerModel;
use app\admin\model\special\SpecialContent;
use app\admin\model\special\SpecialCourse;
use app\admin\model\special\SpecialSource;
use app\admin\model\special\SpecialSubject;
use app\admin\model\special\SpecialTask;
use app\admin\model\special\SpecialWatch;
use app\admin\model\special\LearningRecords;
use app\admin\model\system\Recommend;
use app\admin\model\system\WebRecommend;
use app\admin\model\system\MpRecommend;
use app\admin\model\system\RecommendRelation;
use app\admin\model\system\WebRecommendRelation;
use app\admin\model\system\MpRecommendRelation;
use service\JsonService as Json;
use service\SystemConfigService;
use service\VodService;
use think\Db;
use think\Exception;
use service\FormBuilder as Form;
use Api\AliyunLive as ApiAliyunLive;
use think\Url;
use app\admin\model\special\SpecialTaskCategory;
use app\admin\model\questions\Certificate;
use app\admin\model\questions\CertificateRelated;
use app\admin\model\questions\Relation;
use app\admin\model\questions\TestPaper;
use app\admin\model\download\DataDownload;

/**课程管理-图文课程控制器
 * Class SpecialType
 * @package app\admin\controller\special
 */
class SpecialType extends AuthController
{

    /** 图文课程列表模板渲染
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($subject_id = 0)
    {
        $special_type = $this->request->param('special_type');
        if(!$special_type) echo "<script>top.location.reload();</script>";
        $subjectlist =SpecialSubject::specialCategoryAll();
        $this->assign([
            'activity_type' => $this->request->param('activity_type', 1),
            'subject_id' => $subject_id,
            'special_title' => SPECIAL_TYPE[$special_type],
            'special_type' => $special_type,
            'subject_list' => $subjectlist
        ]);
        $template = $this->switch_template($special_type, request()->action());
        if (!$template) $template = "";
        if (!$this->bc) {
            return $this->redirect(url('admin/index/unauth'));
        }
        return $this->fetch($template);
    }

    public function groupList(){
        $subjectlist =SpecialSubject::specialCategoryAll();
        $this->assign(['subject_list' => $subjectlist]);
        return $this->fetch('special/group_list/index');
    }

    /**
     * 课程拼团列表
     */
    public function pink_list(){
        $where = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['store_name', ''],
            ['start_time', ''],
            ['end_time', ''],
            ['order', ''],
            ['is_show', ''],
        ]);
        return Json::successlayui(SpecialModel::getPinkList($where));
    }
    /**
     * 获取图文课程列表数据
     */
    public function list($special_type = 6)
    {
        $where = parent::getMore([
            ['subject_id', 0],
            ['page', 1],
            ['limit', 20],
            ['store_name', ''],
            ['start_time', ''],
            ['end_time', ''],
            ['order', ''],
            ['is_show', ''],
        ]);
        $where['type'] = $special_type;
        return Json::successlayui(SpecialModel::getSpecialList($where));
    }

    /**精简课添加
     * @param int $id
     */
    public function single_add($id = 0)
    {
        $special_type = $this->request->param('special_type');
        if($id){
            $special = SpecialModel::getsingleOne($id);
            if (!$special)  return Json::fail('课程不存在');
            $this->assign(['special'=>json_encode($special)]);
        }
        if (!$this->bc) {
            return $this->redirect(url('admin/index/unauth'));
        }
        $alicloud_account_id=SystemConfigService::get('alicloud_account_id');//阿里云账号ID
        $configuration_item_region=SystemConfigService::get('configuration_item_region');//配置项region
        $demand_switch=SystemConfigService::get('demand_switch');//视频点播开关
        $this->assign(['id'=>$id,'special_type'=>$special_type,'alicloud_account_id'=>$alicloud_account_id,'configuration_item_region'=>$configuration_item_region,'demand_switch'=>$demand_switch]);
        return $this->fetch('special/special_single/add');
    }

    /**保存、编辑精简课
     * @param int $id
     */
    public function save_single_special($id = 0)
    {
        $special_type = $this->request->param('special_type');
        if (!$special_type || !is_numeric($special_type)) return Json::fail('课程类型参数缺失');
        $data = parent::postMore([
            ['title', ''],
            ['abstract', ''],
            ['subject_id', 0],
            ['lecturer_id', 0],
            ['fake_sales', 0],
            ['browse_count', 0],
            ['light_type', 0],
            ['is_mer_visible', 0],
            ['validity', 0],
            ['label', []],
            ['image', ''],
            ['poster_image', ''],
            ['service_code', ''],
            ['money', 0],
            ['content', ''],
            ['is_pink', 0],
            ['pink_money', 0],
            ['pink_number', 0],
            ['pink_time', 0],
            ['pink_strar_time', ''],
            ['pink_end_time', ''],
            ['phrase', ''],
            ['is_fake_pink', 0],
            ['sort', 0],
            ['fake_pink_number', 0],
            ['member_money', 0],
            ['member_pay_type', 0],
            ['pay_type', 0],//支付方式：免费、付费、密码
            ['is_try', 1],
            ['try_content', ''],
            ['try_time', 0],
            ['link', ''],
            ['videoId', ''],
            ['video_type', 1],
            ['file_type', ''],
            ['file_name', ''],
            ['is_alone', 0],
            ['brokerage_ratio', 0],
            ['brokerage_two', 0]
        ]);

        $lecturer_id=$data['lecturer_id'];
        if (!$data['subject_id']) return Json::fail('请选择分类');
        if (!$data['title']) return Json::fail('请输入课程标题');
        if (!$data['abstract']) return Json::fail('请输入课程简介');
        if (!count($data['label'])) return Json::fail('请输填写标签');
        if (!$data['image']) return Json::fail('请上传课程封面图');
        if (!$data['poster_image']) return Json::fail('请上传推广海报');
//        if (!$data['service_code']) return Json::fail('请上传客服二维码');
        if ($data['validity']<0) return Json::fail('课程有效期不能小于0');
        if ($data['pay_type'] == PAY_MONEY && ($data['money'] == '' || $data['money'] == 0.00 || $data['money'] < 0)) return Json::fail('购买金额未填写或者金额非法');
        if ($data['member_pay_type'] == MEMBER_PAY_MONEY && ($data['member_money'] == '' || $data['member_money'] == 0.00 || $data['member_money'] < 0)) return Json::fail('会员购买金额未填写或金额非法');
        if ($data['pay_type'] != PAY_MONEY) {
            $data['money'] = 0;
            $data['is_alone'] = 0;
            $data['brokerage_ratio'] = 0;
            $data['brokerage_two'] = 0;
            $data['is_try'] = 0;
            $data['try_content'] = '';
            $data['try_time'] = 0;
        }
        if($data['is_alone'] && bcadd($data['brokerage_ratio'],$data['brokerage_two'],2)>100)return Json::fail('两级返佣比例之和不能大于100');
        if ($data['member_pay_type'] != MEMBER_PAY_MONEY) {
            $data['member_money'] = 0;
        }
        $data['pink_strar_time'] = strtotime($data['pink_strar_time']);
        $data['pink_end_time'] = strtotime($data['pink_end_time']);
        if ($data['is_pink']) {
            if (!$data['pink_money'] || $data['pink_money'] == 0.00 || $data['pink_money'] < 0) return Json::fail('拼团金额未填写或者金额非法');
            if (!$data['pink_number'] || $data['pink_number'] <= 0) return Json::fail('拼团人数未填写或拼团人数非法');
            if (!$data['pink_strar_time']) return Json::fail('请填选择拼团开始时间');
            if (!$data['pink_end_time']) return Json::fail('请填选择拼团结束时间');
            if (bcsub($data['pink_end_time'],$data['pink_strar_time'],0)<=0) return Json::fail('拼团时间范围非法');
            if (!$data['pink_time'] || $data['pink_time'] < 0) return Json::fail('拼团时间未填写或时间非法');
            if (($data['is_fake_pink'] && !$data['fake_pink_number']) || ($data['is_fake_pink'] && $data['fake_pink_number'] < 0)) return Json::fail('虚拟拼团比例未填写或者比例非法');
            $times=bcsub($data['pink_end_time'],$data['pink_strar_time'],0);
            $pink_time=bcmul($data['pink_time'],3600,0);
            if($pink_time>$times) return Json::fail('拼团时效不能大于拼团活动区间时间');
        }
        $data['label'] = json_encode($data['label']);
        $content = htmlspecialchars($data['content']);
//        $link='';
//        $videoId='';
//        $video_type=1;
//        $file_type='';
//        $file_name='';
//        if($data['videoId'] && $data['light_type']!=1){
//            $content ='';
//            $videoId=$data['videoId'];
//            $video_type=$data['video_type'];
//            $file_type=$data['file_type'];
//            $file_name=$data['file_name'];
//        }else if($data['light_type']!=1 && $data['link'] && $data['videoId']==''){
//            $link=$data['link'];
//        }
        $link=$data['link'];
        $videoId=$data['videoId'];
        $video_type=$data['video_type'];
        $file_type=$data['file_type'];
        $file_name=$data['file_name'];
        // 如果是加密视频，就把视频真实地址隐藏
        if ($video_type == 2 || $video_type == 3) {
            $link = '';
        }
        if($data['is_try']){
            if($data['light_type']>1){
                $try_content='';
                $try_time=$data['try_time'];
            }else{
                $try_time=0;
                $try_content=$data['try_content'];
            }
        }else{
            $try_content='';
            $try_time=0;
        }
        $is_try=$data['is_try'];
        SpecialModel::beginTrans();
        try {
            unset($data['content']);
            unset($data['link']);
            unset($data['is_try']);
            unset($data['try_time']);
            unset($data['try_content']);
            unset($data['videoId']);
            unset($data['video_type']);
            unset($data['file_type']);
            unset($data['file_name']);
            if ($id) {
                $original = SpecialModel::where('id',$id)->field('lecturer_id,browse_count')->find();
                SpecialModel::update($data, ['id' => $id]);
                SpecialContent::update(['content' => $content,'is_try' => $is_try,'try_time' => $try_time,'try_content' => $try_content,'link' => $link,'videoId' => $videoId,'video_type' => $video_type,'file_type' => $file_type,'file_name' => $file_name], ['special_id' => $id]);
                if($original['lecturer_id']!=$lecturer_id && $lecturer_id>0){
                    $count=LearningRecords::where('special_id',$id)->count();
                    $this->lecturerCurriculum($lecturer_id,1,$count);
                    $this->lecturerCurriculum($original['lecturer_id'],-1,$count);
                }
                SpecialModel::commitTrans();
                return Json::successful('修改成功');
            } else {
                $data['add_time'] = time();
                $data['is_light'] = 1;
                $data['is_show'] = 1;
                $data['is_fake_pink'] = $data['is_pink'] ? $data['is_fake_pink'] : 0;
                $res1 = SpecialModel::insertGetId($data);
                $res2 = SpecialContent::set(['special_id' => $res1, 'content' => $content ,'is_try' => $is_try,'try_time' => $try_time,'try_content' => $try_content,'link' => $link,'videoId' => $videoId,'video_type' => $video_type,'file_type' => $file_type,'file_name' => $file_name , 'add_time' => time()]);
                if ($res1 && $res2) {
                    if($lecturer_id){
                        $this->lecturerCurriculum($lecturer_id,1,0);
                    }
                    SpecialModel::commitTrans();
                    return Json::successful('添加成功');
                } else {
                    SpecialModel::rollbackTrans();
                    return Json::fail('添加失败');
                }
            }
        } catch (\Exception $e) {
            SpecialModel::rollbackTrans();
            return Json::fail($e->getMessage());
        }
    }
    /**
     * 添加页面
     * @param int $id
     * @param int $is_live
     * @return mixed|void
     */
    public function add($id = 0)
    {
        $special_type = $this->request->param('special_type');
        if($id){
            $special = SpecialModel::getOne($id, $special_type == SPECIAL_LIVE ? $special_type : 0);
            if (!$special)  return Json::fail('课程不存在');
            list($specialInfo, $liveInfo) = $special;
            $this->assign(['liveInfo'=>json_encode($liveInfo),'special'=>json_encode($specialInfo)]);
        }
        $this->assign(['id'=>$id,'special_type'=>$special_type]);
        $template = $this->switch_template($special_type, request()->action());
        if (!$template) $template = "";
        if (!$this->bc) {
            return $this->redirect(url('admin/index/unauth'));
        }

        $alicloud_account_id=SystemConfigService::get('alicloud_account_id');//阿里云账号ID
        $configuration_item_region=SystemConfigService::get('configuration_item_region');//配置项region
        $demand_switch=SystemConfigService::get('demand_switch');//视频点播开关
        $this->assign([
            'special_type'=>$special_type,
            'alicloud_account_id'=>$alicloud_account_id,
            'configuration_item_region'=>$configuration_item_region,
            'demand_switch'=>$demand_switch
        ]);
        return $this->fetch($template);
    }

    /**获取编辑数据
     * @param $id
     * @param $special_type
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_check_source_sure($id,$special_type)
    {
        if(!$id){
            $data['sourceCheckList']=[];
            $data['storeCheckList']=[];
        }else{
            $special = SpecialModel::get($id);
            if (!$special)  return Json::fail('课程不存在');
            if($special_type!=SPECIAL_LIVE){
                $specialSourceId = SpecialSource::getSpecialSource($id)->toArray();
                $sourceCheckList = [];
                if ($specialSourceId) {
                    foreach ($specialSourceId as $k => $v) {
                        if ($special_type == SPECIAL_COLUMN) {
                            $task_list = SpecialModel::where(['id' => $v['source_id'],'is_del'=>0,'is_show'=>1])->find();
                        }else{
                            $task_list = SpecialTask::where(['id' => $v['source_id'],'is_del'=>0,'is_show'=>1])->find();
                        }
                        if($task_list){
                            $task_list['is_check'] = 1;
                            $task_list['sort'] = $v['sort'];
                            $task_list['pay_status'] = $v['pay_status'];
                            array_push($sourceCheckList,$task_list);
                        }else{
                            array_splice($specialSourceId,$k,1);
                            continue;
                        }
                    }
                }
                $storeCheckList=[];
            }else{
                $live_id=LiveStudio::where('special_id',$id)->value('id');
                $sourceCheckList=LiveGoods::getLiveGoodsLists($live_id,0);
                $storeCheckList=LiveGoods::getLiveProductLists($live_id,1);
            }
            $data['sourceCheckList']=$sourceCheckList;
            $data['storeCheckList']=$storeCheckList;
        }
        return Json::successful($data);
    }
    /**
     * 素材页面渲染
     * @return
     * */
    public function source_index($coures_id = 0)
    {
        $special_type = $this->request->param('special_type');
        $this->assign('coures_id', $coures_id);
        $this->assign('special_title', SPECIAL_TYPE[$special_type]);
        $this->assign('special_type', $special_type);//图文课程
        $this->assign('activity_type', $this->request->param('activity_type', 1));
        $this->assign('special_id', SpecialCourse::where('id', $coures_id)->value('special_id'));
        $this->assign('specialList', SpecialModel::PreWhere()->field(['id', 'title'])->select());
        $template = $this->switch_template($special_type, request()->action());
        if (!$template) $template = "";
        return $this->fetch($template);
    }

    /**
     * 课节管理
     */
    public function sources_index(){
        $this->assign(['category'=>SpecialTaskCategory::taskCategoryAll()]);
        return $this->fetch('special/task/source_index');
    }

    /**收费课程
     * @param int $id
     */
    public function is_pay_status_c($id=0)
    {
        $this->assign('source_id',$id);
        return $this->fetch('special/task/special_pay');
    }

    /**
     * @param int $source_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function is_pay_source_list($source_id=0)
    {
        $special_id=SpecialSource::where(['source_id' => $source_id,'pay_status' =>1])->column('special_id');
        $special=SpecialModel::where('id','in',$special_id)->field('id,title,image')->select();
        $special=count($special)>0 ? $special->toArray() : [];
        return Json::successlayui(count($special),$special);
    }

    /**
     * 后台课节列表
     */
    public function get_source_list(){
        $where = parent::getMore([
            ['page', 1],
            ['is_show', ''],
            ['limit', 20],
            ['title', ''],
            ['pid', ''],
            ['type', ''],
            ['order', ''],
        ]);
        $special_task = SpecialTask::getTaskList($where);
        if (isset($special_task['data']) && $special_task['data']) {
            foreach ($special_task['data'] as $k => $v) {
                $special_task['data'][$k]['use'] =SpecialSource::where(['source_id' => $v['id']])->count();
                $special_task['data'][$k]['is_pay_status'] =SpecialSource::where(['source_id' => $v['id'],'pay_status' =>1])->count();
                $special_task['data'][$k]['recommend'] = RecommendRelation::where('a.link_id', $v['id'])->where('a.type', 'in', [10])->alias('a')
                    ->join('__RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
                $special_task['data'][$k]['types'] =parent::specialTaskType($v['type']);
            }
        }
        return Json::successlayui($special_task);
    }
    /**
     * 图文、音频、视频、套餐课课程课节列表获取
     * @return json
     * */
    public function source_list()
    {
        $where = parent::getMore([
            ['page', 1],
            ['is_show', 1],
            ['limit', 20],
            ['title', ''],
            ['pid', ''],
            ['type', ''],
            ['order', ''],
            ['special_id', 0],
            ['special_type', 0],
            ['check_source_sure', '']
        ]);
        $special_source =[];
        if(isset($where['special_id']) && $where['special_id'] && $where['special_type']==SPECIAL_SEVEN){
            $special_source = Relation::setWhere(3,$where['special_id'])->column('relation_id');
        }if(isset($where['special_id']) && $where['special_id'] && $where['special_type']==SPECIAL_STORE){
            $special_source = Relation::setWhere(5,$where['special_id'])->column('relation_id');
        }else if(isset($where['special_id']) && $where['special_id'] && $where['special_type']==SPECIAL_LIVE){
            $live_id=LiveStudio::where('special_id',$where['special_id'])->value('id');
            $special_source = LiveGoods::where(['live_id' => $live_id,'is_delete'=>0,'type'=>0])->column('special_id');
        }else if (isset($where['special_id']) && $where['special_id'] && in_array($where['special_type'],[SPECIAL_IMAGE_TEXT,SPECIAL_AUDIO,SPECIAL_VIDEO,SPECIAL_COLUMN])) {
            $special_source = SpecialSource::where(['special_id' => $where['special_id']])->column('source_id');
        }
        $special_task = SpecialTask::getTaskList2($where,$special_source);
        if (isset($special_task['data']) && $special_task['data']) {
            foreach ($special_task['data'] as $k => $v) {
                $special_task['data'][$k]['is_check'] = 0;
                $special_task['data'][$k]['pay_status'] = PAY_MONEY;
                if($v['type']==6) $v['type']=$v['light_type'];
                $special_task['data'][$k]['types'] =parent::specialTaskType($v['type']);
            }
        }
        return Json::successlayui($special_task);
    }

    /**
     * 商品列表获取
     * @return json
     * */
    public function store_list()
    {
        $where = parent::getMore([
            ['page', 1],
            ['is_show', ''],
            ['limit', 20],
            ['title', ''],
            ['order', ''],
            ['special_id', 0],
            ['cate_id', '']
        ]);
        $special_source = array();
        if(isset($where['special_id']) && $where['special_id']){
            $live_id=LiveStudio::where('special_id',$where['special_id'])->value('id');
            $special_source = LiveGoods::where(['live_id' => $live_id,'is_delete'=>0,'type'=>1])->column('special_id');
        }
        $special_task = StoreProduct::storeProductList($where,$special_source);
        if (isset($special_task['data']) && $special_task['data']) {
            foreach ($special_task['data'] as $k => $v) {
                $special_task['data'][$k]['is_check'] = 0;
                $special_task['data'][$k]['LAY_CHECKED'] = false;
            }
        }
        return Json::successlayui($special_task);
    }

    /**
     * 添加和修改素材
     * @param int $id 修改
     * @return
     * */
    public function add_source($id = 0)
    {
        $special_type = $this->request->param("special_type");
        $this->assign('id', $id);
        if ($id) {
            $task = SpecialTask::get($id);
            $task->detail = htmlspecialchars_decode($task->detail);
            $task->content = htmlspecialchars_decode($task->content);
            $task->image = get_key_attr($task->image);
            $this->assign('special', $task);
        }
        $alicloud_account_id=SystemConfigService::get('alicloud_account_id');//阿里云账号ID
        $configuration_item_region=SystemConfigService::get('configuration_item_region');//配置项region
        $demand_switch=SystemConfigService::get('demand_switch');//视频点播开关
        $this->assign('alicloud_account_id', $alicloud_account_id);
        $this->assign('configuration_item_region', $configuration_item_region);
        $this->assign('demand_switch', $demand_switch);
        $this->assign('special_type', $special_type);
        $template = $this->switch_template($special_type, request()->action());
        if (!$template) $template = "";
        return $this->fetch($template);
    }

    /**
     * 添加和修改素材
     * @param int $id 修改
     * @return json
     * */
    public function save_source($id = 0)
    {
        $special_type = $this->request->param('special_type');
        if (!$special_type) return Json::fail('课程类型参数缺失');
        $data = parent::postMore([
            ['title', ''],
            ['image', ''],
            ['content', ''],
            ['detail', ''],
            ['image', ''],
            ['link', ''],
            ['videoId', ''],
            ['video_type', 1],
            ['file_type', ''],
            ['file_name', ''],
            ['sort', 0],
            ['pid', 0],
            ['is_show', 1],
            ['is_try', 1],
            ['try_content', ''],
            ['try_time', 0]
        ]);
        $data['type'] = $special_type;
        if (!$data['title']) return Json::fail('请输入课程标题');
//        if (!$data['image']) return Json::fail('请上传封面图');
        if($data['is_try']){
            if($special_type>1){
                $data['try_content']='';
            }else{
                $data['try_time']=0;
            }
        }else{
            $data['try_content']='';
            $data['try_time']=0;
        }
        // 加密视频则把真实路径隐藏
        if($data['video_type'] == 2 || $data['video_type'] == 3) {
            $data['link'] = '';
        }
        if ($id) {
            unset($data['is_show']);
            SpecialTask::update($data, ['id' => $id]);
            return Json::successful('修改成功');
        } else {
            $data['add_time'] = time();
            if (SpecialTask::set($data))
                return Json::successful('添加成功');
            else
                return Json::fail('添加失败');
        }
    }

    /**
     * 统一添加课节
     */
    public function addSources($id=0)
    {
        if ($id) {
            $task = SpecialTask::get($id);
            $task->detail = htmlspecialchars_decode($task->detail);
            if($task['type']!=1){
                $task->content =$task->link ? ($task->content ? htmlspecialchars_decode($task->content) : '') : '';
            }else{
                $task->content = htmlspecialchars_decode($task->content);
            }
            $task->image = get_key_attr($task->image);
            $this->assign('special', $task);
        }
        $alicloud_account_id=SystemConfigService::get('alicloud_account_id');//阿里云账号ID
        $configuration_item_region=SystemConfigService::get('configuration_item_region');//配置项region
        $demand_switch=SystemConfigService::get('demand_switch');//视频点播开关
        $this->assign('alicloud_account_id', $alicloud_account_id);
        $this->assign('configuration_item_region', $configuration_item_region);
        $this->assign('demand_switch', $demand_switch);
        $this->assign('id', $id);
        $this->assign('fxdisk', config('fxdisk'));
        return $this->fetch('special/task/add_source');
    }
    /**
     * 快速编辑
     * @param string $field 字段名
     * @param int $id 修改的主键
     * @param string value 修改后的值
     * @return json
     */
    public function set_value($field = '', $id = '', $value = '', $model_type)
    {
        if(!$field || !$id || $value == '' || !$model_type) Json::fail('缺少参数3');

        if (!$model_type) Json::fail('缺少参数2');
        if ($model_type == "special") {//需要执行事件触发器，db写法无法触发。
            if($field=='sort' && bcsub($value,0,0)<0)return Json::fail('排序不能为负数');
        }else{
            if($field=='is_show' && $model_type == "task"){
                $model_source = parent::switch_model('source');
                $count= $model_source::where('source_id', $id)->count();
                if($count) Json::fail('素材使用中，请先在课程中移除！');
            }
        }
        $res =parent::getDataModification($model_type,$id,$field,$value);
        if ($res)
            return Json::successful('保存成功');
        else
            return Json::fail('保存失败');
    }

    /**
     * 编辑详情
     * @return mixed
     */
    public function update_content($id = 0)
    {
        $field = $this->request->param('field');
        $special_type = $this->request->param('special_type');
        if (!$special_type) {
            return $this->failed('课程类型丢失 ');
        }
        if (!$id) {
            return $this->failed('缺少id ');
        }
        if (!$field) {
            return $this->failed('缺少要修改的字段参数 ');
        }
        try {
            $this->assign([
                'action' => Url::build('save_content', ['id' => $id, 'field' => $field]),
                'field' => $field,
                'contentOrDetail' => htmlspecialchars_decode(SpecialTask::where('id', $id)->value($field))
            ]);
            $template = $this->switch_template($special_type, request()->action());
            if (!$template) $this->failed('模板查询异常 ');
            return $this->fetch($template);
        } catch (\Exception $e) {
            return $this->failed('异常错误 ');
        }
    }

    /**
     * @param $id
     * @throws \think\exception\DbException
     */
    public function save_content($id, $field)
    {
        $content = $this->request->post($field, '');
        $task = SpecialTask::get($id);
        if (!$field) return Json::fail('修改项缺失');
        if (!$task) return Json::fail('修改得素材不存在');
        $task->$field = htmlspecialchars($content);
        if ($task->save()) {
            return Json::successful('保存成功');
        } else {
            return Json::fail('保存失败或者您没有修改什么');
        }
    }


    /**获取分类
     * @param int $grade_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_subject_list()
    {
        $subjectlist =SpecialSubject::specialCategoryAll();
        return Json::successful($subjectlist);
    }

    /**
     * 获取讲师
     */
    public function get_lecturer_list()
    {
        $list=LecturerModel::where(['is_del'=>0,'is_show'=>1])->order('sort desc')->select();
        return Json::successful($list);
    }
    /**获取课节列表
     * @param bool $type
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_special_source_list()
    {
        $special_type = $this->request->param('special_type');
        $where['is_show'] = 1;
        if ($special_type && is_numeric($special_type) && $special_type != SPECIAL_COLUMN) {
            $where['type'] = $special_type;
        }
        if ($special_type == SPECIAL_COLUMN) {//专栏
            $sourceList = SpecialModel::where($where)->whereIn('type', [SPECIAL_IMAGE_TEXT, SPECIAL_AUDIO, SPECIAL_VIDEO])->field('id, title, type')->order('type desc, sort desc')->select();
            if ($sourceList) {
                foreach ($sourceList as $k => $v) {
                    $sourceList[$k]['title'] = SPECIAL_TYPE[$v['type']] . "--" . $v['title'];
                }
            }
        } else {
            $sourceList = SpecialTask::where($where)->field('id, title')->order('sort desc')->select();
        }
        return Json::successful($sourceList->toArray());
    }

    /**获取视频上传地址和凭证
     * @param string $videoId
     * @param string $FileName
     * @param int $type
     */
    public function video_upload_address_voucher()
    {
        $data = parent::postMore([
            ['FileName',''],
            ['type',1],
            ['image',''],
            ['videoId','']
        ]);
        $url=VodService::videoUploadAddressVoucher($data['FileName'],$data['type'],$data['videoId'],$data['image']);
        return Json::successful($url);
    }
    /**
     * 编辑和新增
     *
     * @return json
     */
    public function save_special($id = 0)
    {
        $special_type = $this->request->param('special_type');
        if (!$special_type || !is_numeric($special_type)) return Json::fail('课程类型参数缺失');
        $data = parent::postMore([
            ['title', ''],
            ['abstract', ''],
            ['subject_id', 0],
            ['lecturer_id', 0],
            ['fake_sales', 0],
            ['browse_count', 0],
            ['is_mer_visible', 0],
            ['validity', 0],
            ['label', []],
            ['image', ''],
            ['banner', []],
            ['poster_image', ''],
            ['service_code', ''],
            ['money', 0],
            ['content', ''],
            ['is_pink', 0],
            ['pink_money', 0],
            ['pink_number', 0],
            ['pink_time', 0],
            ['pink_strar_time', ''],
            ['pink_end_time', ''],
            ['subjectIds',''],
            ['storeIds',''],
            ['phrase', ''],
            ['is_fake_pink', 0],
            ['sort', 0],
            ['fake_pink_number', 0],
            ['member_money', 0],
            ['member_pay_type', 0],
            ['pay_type', 0],//支付方式：免费、付费、密码
            ['is_alone', 0],
            ['brokerage_ratio', 0],
            ['brokerage_two', 0],
        ]);
        $lecturer_id=$data['lecturer_id'];
        $data['check_source_sure']=json_decode($data['subjectIds']);
        $data['check_store_sure']=json_decode($data['storeIds']);
        $data['type'] = $special_type;
        if ($special_type == SPECIAL_LIVE) {
            $liveInfo = parent::postMore([
                ['is_remind', 1],//开播提醒
                ['remind_time', 0],//开播提醒时间
                ['live_time', ''],//直播开始时间
                ['live_duration', 0],//直播时长 单位：分钟
                ['auto_phrase', ''],//首次进入直播间欢迎词
                ['password', ''],//密码（密码访问模式）
                ['is_recording', ''],//是否录制视频
                ['is_fake', 0],
                ['link', ''],
                ['videoId', ''],
                ['video_type', 1],
            ]);
        }
        if (!$data['subject_id']) return Json::fail('请选择分类');
        if ($special_type != SPECIAL_LIVE) {
            if (!$data['check_source_sure']) return Json::fail('请选择素材');
        }
        if (!$data['title']) return Json::fail('请输入课程标题');
        if (!$data['abstract']) return Json::fail('请输入课程简介');
        if (!count($data['label'])) return Json::fail('请输填写标签');
//        if (!count($data['banner'])) return Json::fail('请上传banner图');
        if (!$data['image']) return Json::fail('请上传课程封面图');
//        if (!$data['poster_image']) return Json::fail('请上传推广海报');
//        if (!$data['service_code']) return Json::fail('请上传客服二维码');
        if ($data['validity']<0) return Json::fail('课程有效期不能小于0');
        if ($data['pay_type'] == PAY_MONEY && ($data['money'] == '' || $data['money'] == 0.00 || $data['money'] < 0)) return Json::fail('购买金额未填写或者金额非法');
        if ($data['member_pay_type'] == MEMBER_PAY_MONEY && ($data['member_money'] == '' || $data['member_money'] == 0.00 || $data['member_money'] < 0)) return Json::fail('会员购买金额未填写或金额非法');
        if ($data['pay_type'] != PAY_MONEY) {
            $data['money'] = 0;
            $data['is_alone'] = 0;
            $data['brokerage_ratio'] = 0;
            $data['brokerage_two'] = 0;
        }
        if($data['is_alone'] && bcadd($data['brokerage_ratio'],$data['brokerage_two'],2)>100)return Json::fail('两级返佣比例之和不能大于100');
        if ($data['member_pay_type'] != MEMBER_PAY_MONEY) {
            $data['member_money'] = 0;
        }
        $data['pink_strar_time'] = strtotime($data['pink_strar_time']);
        $data['pink_end_time'] = strtotime($data['pink_end_time']);
        if ($data['is_pink']) {
            if (!$data['pink_money'] || $data['pink_money'] == 0.00 || $data['pink_money'] < 0) return Json::fail('拼团金额未填写或者金额非法');
            if (!$data['pink_number'] || $data['pink_number'] <= 0) return Json::fail('拼团人数未填写或拼团人数非法');
            if (!$data['pink_strar_time']) return Json::fail('请填选择拼团开始时间');
            if (!$data['pink_end_time']) return Json::fail('请填选择拼团结束时间');
            if (bcsub($data['pink_end_time'],$data['pink_strar_time'],0)<=0) return Json::fail('拼团时间范围非法');
            if (!$data['pink_time'] || $data['pink_time'] < 0) return Json::fail('拼团时间未填写或时间非法');
            if (($data['is_fake_pink'] && !$data['fake_pink_number']) || ($data['is_fake_pink'] && $data['fake_pink_number'] < 0)) return Json::fail('虚拟拼团比例未填写或者比例非法');
            $times=bcsub($data['pink_end_time'],$data['pink_strar_time'],0);
            $pink_time=bcmul($data['pink_time'],3600,0);
            if($pink_time>$times) return Json::fail('拼团时效不能大于拼团活动区间时间');
        }
        $content = htmlspecialchars($data['content']);
        $data['label'] = json_encode($data['label']);
        if ($special_type == SPECIAL_LIVE) {
            $liveInfo['live_title'] = $data['title'];
            $liveInfo['studio_pwd'] = $liveInfo['password'];
            if (strlen($liveInfo['studio_pwd']) > 32) return Json::fail('密码长度不能超过32位');
            $liveInfo['start_play_time'] = $liveInfo['live_time'];
            $liveInfo['stop_play_time'] = date('Y-m-d H:i:s', bcadd(strtotime($liveInfo['live_time']), bcmul($liveInfo['live_duration'], 60)));
            $liveInfo['live_introduction'] = $data['abstract'];
            unset($liveInfo['live_time'], $liveInfo['password']);
        }
        $banner = [];
        SpecialModel::beginTrans();
        try {
            foreach ($data['banner'] as $item) {
                $banner[] = $item['pic'];
            }
            $sourceCheckList = $data['check_source_sure'];
            $storeCheckList = $data['check_store_sure'];
            if($special_type==SPECIAL_COLUMN && $data['is_mer_visible']==0){
                foreach ($sourceCheckList as $key=>$value){
                    if($value->is_mer_visible) {
                        SpecialModel::rollbackTrans();
                        return Json::fail('所有人都可观看的套餐课下不能加仅会员可见的课程');
                    }
                }
            }
            unset($data['check_source_sure']);
            $data['banner'] = json_encode($banner);
            unset($data['content']);
            if ($id) {
                $original = SpecialModel::where('id',$id)->field('lecturer_id,browse_count')->find();
                SpecialModel::update($data, ['id' => $id]);
                SpecialContent::update(['content' => $content], ['special_id' => $id]);
                if ($special_type == SPECIAL_LIVE) {
                    LiveStudio::update($liveInfo, ['special_id' => $id]);
                }
                if ($special_type == SPECIAL_LIVE) {
                    $save_source = LiveGoods::saveLiveGoods($sourceCheckList, $id,0);
                    $save_store = LiveGoods::saveLiveGoods($storeCheckList, $id,1);
                }else{
                    $save_source = SpecialSource::saveSpecialSource($sourceCheckList,$id,$special_type,$data);
                    $save_store=true;
                }
                if (!$save_source || !$save_store) return Json::fail('添加失败');
                if($original['lecturer_id']!=$lecturer_id){
                    $count=LearningRecords::where('special_id',$id)->count();
                    $this->lecturerCurriculum($lecturer_id,1,$count);
                    $this->lecturerCurriculum($original['lecturer_id'],-1,$count);
                }
                if ($special_type == SPECIAL_COLUMN) {
                    SpecialBuy::columnUpdate($id);
                }
                SpecialModel::commitTrans();
                return Json::successful('修改成功');
            } else {
                $data['add_time'] = time();
                $data['is_show'] = 1;
                $data['is_fake_pink'] = $data['is_pink'] ? $data['is_fake_pink'] : 0;
                if(SpecialModel::be(['title'=>$data['title'],'is_show'=>1,'is_del'=>0])) return Json::fail('该课程已存在');
                $res1 = SpecialModel::insertGetId($data);
                $res2 = SpecialContent::set(['special_id' => $res1, 'content' => $content, 'add_time' => time()]);
                $res5=true;
                if ($special_type == SPECIAL_LIVE) {
                    $liveInfo['special_id'] = $res1;
                    $liveInfo['stream_name'] = LiveStudio::getliveStreamName();
                    $liveInfo['live_image'] = $data['image'];
                    $res5 = LiveStudio::set($liveInfo);
                }
                if ($special_type == SPECIAL_LIVE) {
                    $res3 = LiveGoods::saveLiveGoods($sourceCheckList, $res1,0);
                    $res4 = LiveGoods::saveLiveGoods($storeCheckList, $res1,1);
                }else{
                    $res3 = SpecialSource::saveSpecialSource($sourceCheckList,$res1,$special_type,$data);
                    $res4 = true;
                }
                if ($res1 && $res2 && $res3 && $res4 && $res5) {
                    $this->lecturerCurriculum($lecturer_id,1,0);
                    SpecialModel::commitTrans();
                    return Json::successful('添加成功');
                } else {
                    SpecialModel::rollbackTrans();
                    return Json::fail('添加失败');
                }
            }
        } catch (\Exception $e) {
            SpecialModel::rollbackTrans();
            return Json::fail($e->getMessage());
        }
    }

    /**
     * 讲师课程数量更新
     */
    public function lecturerCurriculum($lecturer_id=0,$pm=1,$study=0)
    {
        if(!$lecturer_id) return true;
        $lecturer=LecturerModel::where('id',$lecturer_id)->find();
        if(!$lecturer) return false;
        if($pm==1){
            $res=LecturerModel::where('id',$lecturer_id)->update(['curriculum'=>bcadd($lecturer['curriculum'],1,0), 'study'=>bcadd($lecturer['study'],$study,0)]);
        }else{
            $res=LecturerModel::where('id',$lecturer_id)->update(['curriculum'=>bcsub($lecturer['curriculum'],1,0), 'study'=>bcsub($lecturer['study'],$study,0)]);
        }
        return $res;
    }

    /**
     * 拼团设置
     * @param int $special_id
     * @return mixed
     * @throws \FormBuilder\exception\FormBuilderException
     * @throws \think\exception\DbException
     */
    public function pink($special_id = 0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $special = SpecialModel::get($special_id);
        if (!$special) $this->failed('没有查到此课程');
        if ($special->is_del) $this->failed('此课程已删除');
        $form = [
            Form::input('title', '课程标题', $special->title)->disabled(true),
            Form::number('pink_money', '拼团金额', $special->pink_money),
            Form::number('pink_number', '拼团人数', $special->pink_number),
            Form::number('pink_time', '拼团时效(h)', $special->pink_time ? $special->pink_time : 24),
            Form::dateTimeRange('pink_time_new', '拼团时间', $special->pink_strar_time, $special->pink_end_time),
            Form::radio('is_fake_pink', '开启虚拟拼团', $special->is_fake_pink)->options([['label' => '开启', 'value' => 1], ['label' => '关闭', 'value' => 0]]),
            Form::number('fake_pink_number', '补齐比例', $special->fake_pink_number),
        ];
        $form = Form::make_post_form('开启拼团设置', $form, Url::build('save_pink', ['special_id' => $special_id]), 2);
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**保存拼团
     * @param $special_id
     */
    public function save_pink($special_id)
    {
        if (!$special_id) $this->failed('缺少参数');
        $data = parent::postMore([
            ['pink_money', 0],
            ['pink_number', 0],
            ['pink_time', 0],
            ['pink_time_new', []],
            ['is_fake_pink', 0],
            ['fake_pink_number', 0]
        ]);
        if (!$data['pink_number']) return Json::fail('拼团人数不能为0');
        if (!$data['pink_time']) return Json::fail('拼团时效不能为0');
        if (bcsub($data['pink_money'],0,2)<=0) return Json::fail('拼团金额不能为0');
        if ($data['pink_time_new'][0]=='' || $data['pink_time_new'][1]=='') return Json::fail('请设置拼团时间');
        if ($data['is_fake_pink'] && !$data['fake_pink_number']) return Json::fail('请设置虚拟拼团比例');
        if ($data['is_fake_pink'] != 1) {
            $data['fake_pink_number'] = 0;
        }
        $data['is_pink'] = 1;
        if (is_array($data['pink_time_new']) && isset($data['pink_time_new'][0]) && $data['pink_time_new'][1]) {
            $data['pink_strar_time'] = strtotime($data['pink_time_new'][0]);
            $data['pink_end_time'] = strtotime($data['pink_time_new'][1]);
            $times=bcsub($data['pink_end_time'],$data['pink_strar_time'],0);
            $pink_time=bcmul($data['pink_time'],3600,0);
            if($pink_time>$times){
                return Json::fail('拼团时效不能大于拼团活动区间时间');
            }
        }
        unset($data['pink_time_new']);
        SpecialModel::update($data, ['id' => $special_id]);
        return Json::successful('保存成功');
    }

    /**删除指定课程和素材
     * @param int $id修改的主键
     * @param $model_type要修改的表
     * @throws \think\exception\DbException
     */

    public function delete($id = 0, $model_type = false)
    {
        if (!$id || !isset($model_type) || !$model_type) return Json::fail('缺少参数');
        $model_table = parent::switch_model($model_type);
        $id_arr = explode(',', $id);
        if (!$model_table) return Json::fail('缺少参数');
        foreach ($id_arr as $k => $id_item) {
            try {
                $res_get = $model_table::get($id_item);
                $model_table::beginTrans();
                if (!$res_get) return Json::fail('删除的数据不存在');
                if ($model_type == 'special' && $res_get) {
                    $model_source = parent::switch_model('source');
                    $res = $model_source::where('special_id', $id_item)->delete();
                    if($res_get['lecturer_id'] && $res){
                        $count=LearningRecords::where('special_id',$id_item)->count();
                        $this->lecturerCurriculum($res_get['lecturer_id'],-1,$count);
                    }
                    $res_get->where('id',$id_item)->update(['is_del'=>1]);
                }else if($model_type == 'task' && $res_get){
                    $model_source = parent::switch_model('source');
                    $res_get->where('id',$id_item)->update(['is_del'=>1]);
                    $model_source::where('source_id', $id_item)->delete();
                }
                $model_table::commitTrans();
            } catch (\Exception $e) {
                $model_table::rollbackTrans();
                return Json::fail(SpecialTask::getErrorInfo('删除失败' . $e->getMessage()));
            }
        }
        return Json::successful('删除成功');
    }

    /**转换课程
     * @param int $id修改的主键
     * @param $model_type要修改的表
     * @throws \think\exception\DbException
     */

    public function turnTo($id = 0, $model_type = false,$type=1)
    {
        if (!$id || !isset($model_type) || !$model_type) return Json::fail('缺少参数');
        $model_table = parent::switch_model($model_type);
        if (!$model_table) return Json::fail('缺少参数');
        try {
            $res_get = $model_table::get($id);
            $model_table::startTrans();
            if (!$res_get) return Json::fail('转换的数据不存在');
            parent::getDataModification($model_type,$id,'type',$type);
            $model_table::commit();
            return Json::successful('转换成功');
        } catch (\Exception $e) {
            $model_table::rollback();
            return Json::fail(SpecialTask::getErrorInfo('转换失败' . $e->getMessage()));
        }
    }

    /**
     * 添加推荐
     * @param int $special_id
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function recommend($special_id = 0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $special = SpecialModel::get($special_id);
        if (!$special) $this->failed('没有查到此课程');
        if ($special->is_del) $this->failed('此课程已删除');
        $is_pink=$special->is_pink;
        $form = Form::create(Url::build('save_recommend', ['special_id' => $special_id]), [
            Form::select('recommend_id', '推荐')->setOptions(function () use ($is_pink) {
                $model=Recommend::where(['is_show' => 1,'is_fixed'=>0]);
                if($is_pink){
                    $model=$model->where('type','in',[0,8]);
                }else{
                    $model=$model->where('type',0);
                }
                $list = $model->field('title,id')->order('sort desc,add_time desc')->select();
                $menus = [];
                foreach ($list as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['title']];
                }
                return $menus;
            })->filterable(1),
            Form::number('sort', '排序'),
        ]);
        $form->setMethod('post')->setTitle('推荐设置')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload(); setTimeout(function(){parent.layer.close(parent.layer.getFrameIndex(window.name));},800);');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存推荐
     * @param int $special_id
     * @throws \think\exception\DbException
     */
    public function save_recommend($special_id = 0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $data = parent::postMore([
            ['recommend_id', 0],
            ['sort', 0],
        ]);
        if (!$data['recommend_id']) return Json::fail('请选择推荐');
        $recommend = Recommend::get($data['recommend_id']);
        if (!$recommend) return Json::fail('导航菜单不存在');
        $data['add_time'] = time();
        $data['type'] = $recommend->type;
        $data['link_id'] = $special_id;
        if (RecommendRelation::be(['type' => $recommend->type, 'link_id' => $special_id, 'recommend_id' => $data['recommend_id']])) return Json::fail('已推荐,请勿重复推荐');
        if (RecommendRelation::set($data))
            return Json::successful('推荐成功');
        else
            return Json::fail('推荐失败');
    }

    /**取消推荐
     * @param int $id
     */
    public function cancel_recommendation($id=0,$special_id=0)
    {
        if (!$id || !$special_id) $this->failed('缺少参数');
        if (RecommendRelation::be(['id' => $id, 'link_id' => $special_id])){
            $res=RecommendRelation::where(['id'=>$id,'link_id'=>$special_id])->delete();
            if ($res)
                return Json::successful('取消推荐成功');
            else
                return Json::fail('取消推荐失败');
        }else{
            return Json::fail('推荐不存在');
        }
    }
    /**
     * 添加推荐
     * @param int $special_id
     * @param int $type 0=课程 1=直播
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function web_recommend($special_id = 0,$type=0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $special = SpecialModel::get($special_id);
        if (!$special) $this->failed('没有查到此课程');
        if ($special->is_del) $this->failed('此课程已删除');
        $form = Form::create(Url::build('save_web_recommend', ['special_id' => $special_id]), [
            Form::select('recommend_id', '推荐')->setOptions(function () use($type){
                $model=WebRecommend::where(['is_show' => 1,'type'=>$type]);
                $list = $model->field('title,id')->order('sort desc,add_time desc')->select();
                $menus = [];
                foreach ($list as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['title']];
                }
                return $menus;
            })->filterable(1),
            Form::number('sort', '排序'),
        ]);
        $form->setMethod('post')->setTitle('推荐设置')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload(); setTimeout(function(){parent.layer.close(parent.layer.getFrameIndex(window.name));},800);');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存推荐
     * @param int $special_id
     * @throws \think\exception\DbException
     */
    public function save_web_recommend($special_id = 0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $data = parent::postMore([
            ['recommend_id', 0],
            ['sort', 0],
        ]);
        if (!$data['recommend_id']) return Json::fail('请选择推荐');
        $recommend = WebRecommend::get($data['recommend_id']);
        if (!$recommend) return Json::fail('导航菜单不存在');
        $data['add_time'] = time();
        $data['type'] = $recommend->type;
        $data['link_id'] = $special_id;
        if (WebRecommendRelation::be(['type' => $recommend->type, 'link_id' => $special_id, 'recommend_id' => $data['recommend_id']])) return Json::fail('已推荐,请勿重复推荐');
        if (WebRecommendRelation::set($data))
            return Json::successful('推荐成功');
        else
            return Json::fail('推荐失败');
    }

    /**取消推荐
     * @param int $id
     */
    public function cancel_web_recommendation($id=0,$special_id=0)
    {
        if (!$id || !$special_id) $this->failed('缺少参数');
        if (WebRecommendRelation::be(['id' => $id, 'link_id' => $special_id])){
            $res=WebRecommendRelation::where(['id'=>$id,'link_id'=>$special_id])->delete();
            if ($res)
                return Json::successful('取消推荐成功');
            else
                return Json::fail('取消推荐失败');
        }else{
            return Json::fail('推荐不存在');
        }
    }

    /**
     * 添加推荐(小程序)
     * @param int $special_id
     * @param int $type 0=课程 1=直播
     * @return mixed
     * @throws \think\exception\DbException
     */
    public function mp_recommend($special_id = 0,$type=0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $special = SpecialModel::get($special_id);
        if (!$special) $this->failed('没有查到此课程');
        if ($special->is_del) $this->failed('此课程已删除');
        $form = Form::create(Url::build('save_mp_recommend', ['special_id' => $special_id]), [
            Form::select('recommend_id', '推荐')->setOptions(function (){
                $model=MpRecommend::where(['is_show' => 1,'is_fixed'=>0]);
                $model=$model->where('type',0);
                $list = $model->field('title,id')->order('sort desc,add_time desc')->select();
                $menus = [];
                foreach ($list as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['title']];
                }
                return $menus;
            })->filterable(1),
            Form::number('sort', '排序'),
        ]);
        $form->setMethod('post')->setTitle('推荐设置')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload(); setTimeout(function(){parent.layer.close(parent.layer.getFrameIndex(window.name));},800);');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**
     * 保存推荐(小程序)
     * @param int $special_id
     * @throws \think\exception\DbException
     */
    public function save_mp_recommend($special_id = 0)
    {
        if (!$special_id) $this->failed('缺少参数');
        $data = parent::postMore([
            ['recommend_id', 0],
            ['sort', 0],
        ]);
        if (!$data['recommend_id']) return Json::fail('请选择推荐');
        $recommend = MpRecommend::get($data['recommend_id']);
        if (!$recommend) return Json::fail('导航菜单不存在');
        $data['add_time'] = time();
        $data['type'] = $recommend->type;
        $data['link_id'] = $special_id;
        if (MpRecommendRelation::be(['type' => $recommend->type, 'link_id' => $special_id, 'recommend_id' => $data['recommend_id']])) return Json::fail('已推荐,请勿重复推荐');
        if (MpRecommendRelation::set($data))
            return Json::successful('推荐成功');
        else
            return Json::fail('推荐失败');
    }

    /**取消推荐(小程序)
     * @param int $id
     */
    public function cancel_mp_recommendation($id=0,$special_id=0)
    {
        if (!$id || !$special_id) $this->failed('缺少参数');
        if (MpRecommendRelation::be(['id' => $id, 'link_id' => $special_id])){
            $res=MpRecommendRelation::where(['id'=>$id,'link_id'=>$special_id])->delete();
            if ($res)
                return Json::successful('取消推荐成功');
            else
                return Json::fail('取消推荐失败');
        }else{
            return Json::fail('推荐不存在');
        }
    }

    /**素材推荐
     * @param int $source_id
     */
    public function sourceRecommend($source_id = 0)
    {
        if (!$source_id) $this->failed('缺少参数');
        $source = SpecialTask::get($source_id);
        if (!$source) $this->failed('没有查到此素材');
        if ($source->is_del) $this->failed('此素材已删除');
        $form = Form::create(Url::build('save_recommend', ['special_id' => $source_id]), [
            Form::select('recommend_id', '推荐')->setOptions(function () use ($source_id) {
                $model=Recommend::where(['is_show' => 1,'is_fixed'=>0,'type'=>10]);
                $list = $model->field('title,id')->order('sort desc,add_time desc')->select();
                $menus = [];
                foreach ($list as $menu) {
                    $menus[] = ['value' => $menu['id'], 'label' => $menu['title']];
                }
                return $menus;
            })->filterable(1),
            Form::number('sort', '排序')
        ]);
        $form->setMethod('post')->setTitle('推荐设置')->setSuccessScript('parent.$(".J_iframe:visible")[0].contentWindow.location.reload(); setTimeout(function(){parent.layer.close(parent.layer.getFrameIndex(window.name));},800);');
        $this->assign(compact('form'));
        return $this->fetch('public/form-builder');
    }

    /**课程编辑内课节列表
     * @param int $coures_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function search_task($coures_id = 0)
    {
        $special_type = $this->request->param('special_type');
        $special_id = $this->request->param('special_id');
        $this->assign('coures_id', $coures_id);
        $this->assign('special_title', SPECIAL_TYPE[$special_type]);
        $this->assign('special_type', $special_type);//图文课程
        $this->assign('activity_type', $this->request->param('activity_type', 1));
        $this->assign('special_id', $special_id);
        $this->assign('specialList', SpecialModel::PreWhere()->field(['id', 'title'])->select());
        return $this->fetch('special/task/search_task');
    }

    /**
     * @param int $coures_id
     * @return mixed
     */
    public function searchs_task($coures_id=0)
    {
        $special_type = $this->request->param('special_type');
        $special_id = $this->request->param('special_id');
        $this->assign('coures_id', $coures_id);
        $this->assign('special_title', SPECIAL_TYPE[$special_type]);
        $this->assign('special_type', $special_type);//图文课程
        $this->assign('special_id', $special_id);
        $this->assign('cateList', SpecialTaskCategory::taskCategoryAll());
        return $this->fetch('special/task/searchs_task');
    }

    /**课程编辑内课节列表
     * @param int $coures_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function special_task($coures_id = 0)
    {
        $special_type = $this->request->param('special_type');
        $special_id = $this->request->param('special_id');
        $this->assign('coures_id', $coures_id);
        $this->assign('special_title', SPECIAL_TYPE[$special_type]);
        $this->assign('special_type', $special_type);//图文课程
        $this->assign('activity_type', $this->request->param('activity_type', 1));
        $this->assign('special_id', $special_id);
        $this->assign('specialList', SpecialModel::PreWhere()->field(['id', 'title'])->select());
        return $this->fetch('special/task/special_task');
    }

    /**直播课程编辑内商品列表
     * @param int $coures_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function store_task($coures_id = 0)
    {
        $special_id = $this->request->param('special_id');
        $this->assign('special_id', $special_id);
        $this->assign('cateList',StoreCategory::getTierList());
        return $this->fetch('special/task/store_task');
    }

    /**
     * 课程弹幕列表和添加
     * */
    public function special_barrage()
    {
        $this->assign([
            'type' => $this->request->param('type', 1),
            'is_layui' => true,
            'open_barrage' => SystemConfig::getValue('open_barrage'),
        ]);
        return $this->fetch('special/barrage/special_barrage');
    }

    /**
     * 获取课程弹幕列表
     * */
    public function get_barrage_list($page = 1, $limit = 22)
    {
        $list = SpecialBarrage::where('is_show', 1)->order('sort desc,id desc')->page((int)$page, (int)$limit)->select();
        $list = count($list) ? $list->toArray() : [];
        $count = SpecialBarrage::where('is_show', 1)->count();
        return Json::successful(compact('list', 'count'));
    }

    /**
     * 删除某个弹幕
     * @param int $id 弹幕id
     * */
    public function del_barrage($id = 0)
    {
        if (SpecialBarrage::del($id))
            return Json::successful('删除成功');
        else
            return Json::fail('删除失败');
    }

    /**
     * 保存课程弹幕
     * */
    public function save_barrage($id = 0)
    {
        $data = parent::postMore([
            ['nickname', ''],
            ['avatar', ''],
            ['action', 0],
            ['sort', 0],
        ]);
        if (!$data['nickname']) return Json::fail('请填写用户昵称');
        if (!$data['avatar']) return Json::fail('请上传用户图像');
        if (!$data['action']) return Json::fail('请勾选动作类型');
        if ($id) {
            SpecialBarrage::edit($data, $id);
            return Json::successful('修改成功');
        } else {
            $data['add_time'] = time();
            if (SpecialBarrage::set($data))
                return Json::successful('添加成功');
            else
                return Json::fail('添加失败');
        }
    }

    /**
     * 设置虚拟用户弹幕是否开启
     * */
    public function set_barrage_show($value = 0, $key_nime = '')
    {
        if (!$key_nime) return Json::fail('缺少参数');
        $confing = SystemConfig::where(['menu_name' => $key_nime])->find();
        if ($confing) {
            SystemConfig::edit(['value' => json_encode($value)], $confing->id);
            return Json::successful('操作成功');
        } else {
            $res = SystemConfig::set([
                'menu_name' => $key_nime,
                'type' => 'radio',
                'parameter' => "1=开启\n0=关闭",
                'value' => '1',
                'config_tab_id' => 1,
                'upload_type' => 0,
                'width' => '100%',
                'info' => '虚拟用户课程弹幕开关',
                'desc' => '虚拟用户课程弹幕开关',
                'sort' => 0,
                'status' => 1
            ]);
            if ($res)
                return Json::successful('操作成功');
            else
                return Json::fail('操作失败');
        }
    }

    /**渲染模板
     * @param $special_type
     * @param $template_type
     * @return bool|string|void
     */
    protected function switch_template($special_type, $template_type)
    {
        if (!$special_type || !$template_type) {
            return false;
        }
        switch ($special_type) {
            case 1:
                return 'special/image_text/' . $template_type;
                break;
            case 2:
                return 'special/audio_video/' . $template_type;
                break;
            case 3:
                return 'special/audio_video/' . $template_type;
                break;
            case 4:
                return 'special/live/' . $template_type;
                break;
            case 5:
                return 'special/column/' . $template_type;
                break;
            case 6:
                return 'special/special_single/' . $template_type;
                break;
            default:
                return $this->failed('没有对应模板 ');
        }
    }
    /**关联证书
     * @param int $id
     */
    public function certificate($related_id=0)
    {
        if(!$related_id) return Json::fail('参数错误');
        $certificate=CertificateRelated::where(['related'=>$related_id,'obtain'=>1])->find();
        if($certificate){
            $id=$certificate['id'];
        }else{
            $id=0;
            $certificate=[];
        }
        $this->assign(['related_id'=>$related_id,'id'=>$id,'certificate'=>json_encode($certificate)]);
        return $this->fetch('special/task/certificate');
    }

    /**获取对应证书
     * @param int $obtain
     */
    public function certificateLists($obtain=1)
    {
        $list=Certificate::where(['is_del'=>0,'obtain'=>$obtain])->order('sort desc,add_time desc')->select();
        $list=count($list) > 0 ? $list->toArray() : [];
        return  Json::successful($list);
    }

    /**试卷关联证书
     * @param int $id
     * @param int $obtain
     */
    public function certificateRecord($id=0,$obtain=1)
    {
        $data = parent::postMore([
            ['cid', 0],
            ['condition', ''],
            ['related', 0],
            ['is_show', 0]
        ]);
        $data['obtain']=$obtain;
        $res=CertificateRelated::addCertificateRelated($data,$id);
        if($res){
            return  Json::successful('关联成功');
        }else{
            return Json::fail('关联失败');
        }
    }

    /**学习记录
     * @param int $id
     */
    public function learningRecords($id=0,$uid=0)
    {
        $this->assign(['id'=>$id,'uid'=>$uid, 'year' => getMonth('y')]);
        return $this->fetch('special/task/learning_records');
    }
    /**学习进度
     * @param int $id
     */
    public function percentage($uid=0,$special_id=0,$type=0,$is_light=0)
    {
        $this->assign(['special_id'=>$special_id, 'uid' =>$uid,'type' =>$type,'is_light' =>$is_light]);
        return $this->fetch('special/task/percentage');
    }

    /**课程的学习记录
     * @param $id
     */
    public function specialLearningRecordsList($id,$uid=0)
    {
        $where = parent::getMore([
            ['id',0],
            ['page', 1],
            ['limit', 20],
            ['excel', 0],
            ['data', '']
        ]);
        $where['id']=$where['id']>=0 ? $where['id'] : $id;
        if($uid) $where['uid']=$uid;
        return Json::successlayui(LearningRecords::specialLearningRecordsLists($where,$where['id']));
    }

    /**学习情况
     * @param $special_id
     * @param $uid
     * @param $type
     */
    public static function percentageData()
    {
        $where = parent::getMore([
            ['special_id',0],
            ['page', 1],
            ['limit', 20],
            ['uid', 0],
            ['is_light', 0],
            ['type', 0]
        ]);
        $data= SpecialWatch::percen_tage_specials($where);
        return Json::successlayui($data);
    }
    /**课程关联的考试或练习
     * @param $id
     * @param $relationship
     * @return mixed
     */
    public function testPaperRelation($id=0,$relationship=1)
    {
        $this->assign(['id'=>$id, 'relationship' => $relationship]);
        return $this->fetch('special/task/test_paper');
    }

    /**获取关联的试卷
     * @param int $id
     * @param int $relationship 1=练习 2=考试
     */
    public function getRelationTestPaperList($id=0,$relationship=1)
    {
        if(!$id) Json::fail('缺少参数');
        $data=Relation::getRelationTestPaper($id,$relationship);
        return Json::successlayui($data);
    }
    /**课程关联试卷排序
     * @param int $id
     * @param int $data_id
     * @param $value
     * @param int $relationship 1=练习 2=考试 4=资料
     */
    public function upRelationSort($id,$data_id,$value,$relationship)
    {
        if(!$id || !$data_id) Json::fail('缺少参数');
        $res=Relation::updateRelationSort($id,$data_id,$relationship,$value);
        if ($res)
            return Json::successful('修改成功');
        else
            return Json::fail('修改失败');
    }
    /**课程关联试卷删除
     * @param int $id
     * @param int $data_id
     * @param int $relationship 1=练习 2=考试
     */
    public function delRelation($id,$data_id,$relationship)
    {
        if(!$id || !$data_id) Json::fail('缺少参数');
        $res=Relation::delRelation($id,$data_id,$relationship);
        if ($res)
            return Json::successful('删除成功');
        else
            return Json::fail('删除失败');
    }

    /**关联试卷
     * @param int $id
     */
    public function relation($id=0,$relationship=1){
        if(!$id) Json::fail('缺少参数');
        $this->assign(['id'=>$id, 'relationship' => $relationship]);
        return $this->fetch('special/task/relation');
    }

    /**课程关联试卷、资料
     * @param int $id
     */
    public function addRelation($id,$ids,$relationship)
    {
        if(!$id) Json::fail('缺少参数');
        $res=Relation::setRelations($id,$ids,$relationship);
        if ($res)
            return Json::successful('关联成功');
        else
            return Json::fail('关联失败');
    }

    /**
     * 获取试卷列表
     */
    public function getTestPapersList()
    {
        $where = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['special_id', 0],
            ['relationship', 1],
            ['is_show', 1],
            ['title', '']
        ]);
        $source =[];
        if(isset($where['special_id']) && $where['special_id'] && $where['relationship']){
            $source = Relation::setWhere($where['relationship'],$where['special_id'])->column('relation_id');
        }
        switch ($where['relationship']){
            case 1:
                $where['type']=1;
                break;
            case 2:
                $where['type']=2;
                break;
        }
        $TestPapers = TestPaper::testPaperLists($where,$source);
        if (isset($TestPapers['data']) && $TestPapers['data']) {
            foreach ($TestPapers['data'] as $k => $v) {
                $TestPapers['data'][$k]['is_check'] = 0;
            }
        }
        return Json::successlayui($TestPapers);
    }

    /**课程关联的资料
     * @param $id
     * @param $relationship
     * @return mixed
     */
    public function dataDownloadRelation($id=0,$relationship=4)
    {
        $this->assign(['id'=>$id, 'relationship' => $relationship]);
        return $this->fetch('special/task/data_download');
    }

    /**获取关联的资料
     * @param int $id
     * @param int $relationship 4=资料
     */
    public function getRelationDataDownloadList($id=0,$relationship=4)
    {
        if(!$id) Json::fail('缺少参数');
        $data=Relation::getRelationDataDownload($id,$relationship);
        return Json::successlayui($data);
    }

    /**关联资料
     * @param int $id
     */
    public function download($id=0,$relationship=4){
        if(!$id) Json::fail('缺少参数');
        $this->assign(['id'=>$id, 'relationship' => $relationship]);
        return $this->fetch('special/task/download');
    }

    /**
     * 获取资料列表
     */
    public function getDataDownloadList()
    {
        $where = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['data_id', 0],
            ['relationship', 4],
            ['is_show', 1],
            ['title', '']
        ]);
        $data =[];
        if(isset($where['data_id']) && $where['data_id'] && $where['relationship']){
            $data = Relation::setWhere($where['relationship'],$where['data_id'])->column('relation_id');
        }
        $download = DataDownload::dataDownloadLists($where,$data);
        if (isset($download['data']) && $download['data']) {
            foreach ($download['data'] as $k => $v) {
                $download['data'][$k]['is_check'] = 0;
            }
        }
        return Json::successlayui($download);
    }
}
