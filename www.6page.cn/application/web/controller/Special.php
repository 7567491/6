<?php


namespace app\web\controller;

use app\wap\model\topic\CertificateRecord;
use app\wap\model\topic\CertificateRelated;
use app\web\model\live\LiveStudio;
use app\web\model\special\Lecturer;
use app\web\model\special\Special as SpecialModel;
use app\web\model\special\LearningRecords;
use app\web\model\special\SpecialBuy;
use app\web\model\special\SpecialContent;
use app\web\model\special\SpecialCourse;
use app\web\model\special\SpecialRecord;
use app\web\model\special\SpecialRelation;
use app\web\model\special\SpecialSource;
use app\web\model\special\SpecialSubject;
use app\web\model\special\SpecialTask;
use app\web\model\special\SpecialWatch;
use app\web\model\special\SpecialReply;
use app\web\model\special\SpecialExchange;
use app\web\model\special\SpecialBatch;
use app\web\model\special\StoreOrder;
use app\web\model\material\DataDownload;
use app\web\model\special\Relation;
use app\web\model\topic\TestPaper;
use app\web\model\user\User;
use service\VodService;
use service\JsonService;
use service\SystemConfigService;
use service\UtilService;
use think\response\Json;
use think\Session;
use think\Url;
use think\Db;
use think\Request;

/**课程控制器
 * Class Special
 * @package app\web\controller
 */
class Special extends AuthController
{
    /**
     * 白名单
     * */
    public static function WhiteList()
    {
        return [
            'details',
            'single_details',
            'get_special_details',
            'get_special_single_details',
            'get_course_list',
            'play_num',
            'special_cate',
            'get_grade_cate',
            'get_all_special_cate',
            'get_special_list',
            'get_live_special_list',
            'get_cloumn_task',
            'isMember',
            'learningRecords',
            'numberCourses',
            'addLearningRecords',
            'recommended_courses',
            'get_video_playback_credentials',
            'special_reply_list',
            'special_reply_data',
            'special_data_download',
            'teacher_detail',
            'teacher_list',
            'exchange'
        ];
    }

    /**获取视频上传地址和凭证
     * @param string $videoId
     * @param int $type
     */
    public function get_video_playback_credentials($type = 1, $videoId = '')
    {
        $url = VodService::videoUploadAddressVoucher('', $type, $videoId);
        return JsonService::successful($url);
    }

    /**
     * 课程详情
     * @param $id int 课程id
     * @return
     */
    public function details($id = 0, $link_pay_uid = 0, $link_pay = 0)
    {
        if (!$id) {
            $this->assign([
                'error' =>'缺少必要参数'
            ]);
            return $this->fetch();
        }
        $comment_switch = SystemConfigService::get('special_comment_switch');//课程评论开关
        // 获取课程详情
        $special = $this->get_special_details_render($id);
        if (isset($special['error'])) {
            $this->assign([
                'error' => $special['msg']
            ]);
            return $this->fetch();
        }
        $lecturer_id = $special['special']['lecturer_id'];
        $lecturer=Lecturer::details($lecturer_id);

        //获取推荐课程
        $recommended_courses = $this->recommended_courses_render($id);

        $recommended_courses = count($recommended_courses) ? $recommended_courses : 0;

        // 关联的课件
        $virtual = $this->special_data_download_render($id);
        $virtual = count($virtual) ? $virtual : 0;
        $this->assign([
            'comment_switch' => $comment_switch,
            'special' => $special,
            'course_info' => $special['special'],
            'lecturer' => $lecturer,
            'recommended_courses' => $recommended_courses,
            'virtual' => $virtual,
            'link_pay' => (int)$link_pay,
            'link_pay_uid' => $link_pay_uid
        ]);


        return $this->fetch();
    }
    /**获取课程详情
     * @param int $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_special_details($id = 0)
    {
        $special = SpecialModel::getOneSpecial($this->uid, $id);
        if ($special === false) return JsonService::fail(SpecialModel::getErrorInfo('无法访问'));
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $special_money = SpecialModel::where('id', $id)->field('money,pay_type,member_pay_type,member_money,is_mer_visible,fake_sales')->find();
        if (!$is_member && $special_money['is_mer_visible'] == 1) return JsonService::fail('课程仅会员可以获得，请充值会员');
        $validity = -1;
        if (in_array($special_money['money'], [0, 0.00]) || in_array($special_money['pay_type'], [PAY_NO_MONEY, PAY_PASSWORD])) {
            $isPay = 1;
            $validity = 0;
        } else {
            $isPay = (!$this->uid || $this->uid == 0) ? false : SpecialBuy::PaySpecial($id, $this->uid);
            if ($isPay) $validity = SpecialBuy::getSpecialEndTime($id, $this->uid);
        }
        if (in_array($special_money['member_money'], [0, 0.00]) || in_array($special_money['member_pay_type'], [PAY_NO_MONEY])) {
            if ($validity == -1 && $is_member) {
                $validity = bcsub($this->userInfo['overdue_time'], time(), 0);
            }
        }
        $liveInfo = [];
        if (isset($special['special'])) {
            $specialinfo = $special['special'];
            $specialinfo = is_string($specialinfo) ? json_decode($specialinfo, true) : $specialinfo;
            if ($specialinfo['type'] == SPECIAL_LIVE) {
                $liveInfo = LiveStudio::where('special_id', $specialinfo['id'])->find();
                if (!$liveInfo) return JsonService::fail('直播间尚未查到！');
                if ($liveInfo->is_del) return JsonService::fail('直播间已经删除！');
            }
        }
        $count = SpecialModel::learning_records($id);
        $recordCoujnt = processingData(bcadd($special_money['fake_sales'], $count, 0));
        $isBatch = SpecialBatch::isBatch($id);//课程是否开启兑换活动
        $special['isBatch'] = $isBatch;
        $special['isPay'] = $isPay;
        $special['validity'] = $validity;
        $special['is_member'] = $is_member;
        $special['liveInfo'] = $liveInfo;
        $special['recordCoujnt'] = $recordCoujnt;
        return JsonService::successful($special);
    }

    public function get_special_details_render($id = 0)
    {
        $special = SpecialModel::getOneSpecial($this->uid, $id);
        if ($special === false) return array('error' => 1, 'msg' => SpecialModel::getErrorInfo('无法访问'));
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $special_money = SpecialModel::where('id', $id)->field('money,pay_type,member_pay_type,member_money,is_mer_visible,fake_sales')->find();
        if (!$is_member && $special_money['is_mer_visible'] == 1) return array('error' => 1, 'msg' => '仅付费会员才可报名，请先充值会员');
        $validity = -1;
        if (in_array($special_money['money'], [0, 0.00]) || in_array($special_money['pay_type'], [PAY_NO_MONEY, PAY_PASSWORD])) {
            $isPay = 1;
            $validity = 0;
        } else {
            $isPay = (!$this->uid || $this->uid == 0) ? false : SpecialBuy::PaySpecial($id, $this->uid);
            if ($isPay) $validity = SpecialBuy::getSpecialEndTime($id, $this->uid);
        }
        if (in_array($special_money['member_money'], [0, 0.00]) || in_array($special_money['member_pay_type'], [PAY_NO_MONEY])) {
            if ($validity == -1 && $is_member) {
                $validity = bcsub($this->userInfo['overdue_time'], time(), 0);
            }
        }
        $liveInfo = [];
        if (isset($special['special'])) {
            $specialinfo = $special['special'];
            $specialinfo = is_string($specialinfo) ? json_decode($specialinfo, true) : $specialinfo;
            $specialinfo['subject'] = SpecialSubject::where('id', $specialinfo['subject_id'])->find();
//            dump($specialinfo['subject']->name);
            if ($specialinfo['type'] == SPECIAL_LIVE) {
                $liveInfo = LiveStudio::where('special_id', $specialinfo['id'])->find();
                if (!$liveInfo) return array('error' => 1, 'msg' => '直播间不存在');
                if ($liveInfo->is_del) return  array('error' => 1, 'msg' => '直播间已经删除');
            }
        }
        $count = SpecialModel::learning_records($id);
        $recordCoujnt = processingData(bcadd($special_money['fake_sales'], $count, 0));
        $isBatch = SpecialBatch::isBatch($id);//课程是否开启兑换活动
        $special['isBatch'] = $isBatch;
        $special['isPay'] = $isPay;
        $special['validity'] = $validity;
        $special['is_member'] = $is_member;
        $special['liveInfo'] = $liveInfo;
        $special['recordCoujnt'] = $recordCoujnt;
        return $special;
    }


    /**
     * 精简课详情
     * @param int $id
     */
    public function single_details($id = 0, $link_pay_uid = 0, $link_pay = 0)
    {
        if (!$id) {
            $this->assign([
                'error' =>'缺少必要参数'
            ]);
            return $this->fetch();
        }
        $comment_switch = SystemConfigService::get('special_comment_switch');//课程评论开关
        // 获取课程详情
        $special = $this->get_special_single_details_render($id);
        if (isset($special['error'])) {
            $this->assign([
                'error' => $special['msg']
            ]);
            return $this->fetch();
        }
        $lecturer_id = $special['special']['lecturer_id'];
        $lecturer=Lecturer::details($lecturer_id);

        //获取推荐课程
        $recommended_courses = $this->recommended_courses_render($id);

        $recommended_courses = count($recommended_courses) ? $recommended_courses : 0;

        // 关联的课件
        $virtual = $this->special_data_download_render($id);
        $virtual = count($virtual) ? $virtual : 0;
        $this->assign([
            'uid' => $this->uid,
            'comment_switch' => $comment_switch,
            'special' => $special,
            'course_info' => $special['special'],
            'lecturer' => $lecturer,
            'recommended_courses' => $recommended_courses,
            'virtual' => $virtual,
            'link_pay' => (int)$link_pay,
            'link_pay_uid' => $link_pay_uid
        ]);
        return $this->fetch();
    }

    /**获取精简课详情
     * @param int $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_special_single_details($id = 0)
    {
        if (!$id) return JsonService::fail('缺少参数,无法访问');
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $special = SpecialModel::getSingleOneSpecial($this->uid, $id);
        if ($special === false) return JsonService::fail(SpecialModel::getErrorInfo('无法访问'));
        $special_money = SpecialModel::where('id', $id)->field('money,pay_type,member_pay_type,member_money,is_mer_visible,fake_sales')->find();
        if (!$is_member && $special_money['is_mer_visible'] == 1) return JsonService::fail('课程仅会员可以获得，请充值会员');
        $validity = -1;
        if (in_array($special_money['money'], [0, 0.00]) || in_array($special_money['pay_type'], [PAY_NO_MONEY, PAY_PASSWORD])) {
            $isPay = 1;
            $validity = 0;
        } else {
            $isPay = (!$this->uid || $this->uid == 0) ? false : SpecialBuy::PaySpecial($id, $this->uid);
            if ($isPay) $validity = SpecialBuy::getSpecialEndTime($id, $this->uid);
        }
        if (in_array($special_money['member_money'], [0, 0.00]) || in_array($special_money['member_pay_type'], [PAY_NO_MONEY])) {
            if ($validity == -1 && $is_member) {
                $validity = bcsub($this->userInfo['overdue_time'], time(), 0);
            }
        }
        $count = SpecialModel::learning_records($id);
        $recordCoujnt = processingData(bcadd($special_money['fake_sales'], $count, 0));
        $isBatch = SpecialBatch::isBatch($id);//课程是否开启兑换活动
        $special['isBatch'] = $isBatch;
        $special['isPay'] = $isPay;
        $special['validity'] = $validity;
        $special['is_member'] = $is_member;
        $special['recordCoujnt'] = $recordCoujnt;
        return JsonService::successful($special);
    }

    public function get_special_single_details_render($id = 0)
    {
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $special = SpecialModel::getSingleOneSpecial($this->uid, $id);
        if ($special === false) return array('error' => 1, 'msg' => SpecialModel::getErrorInfo('无法访问'));
        $special_money = SpecialModel::where('id', $id)->field('money,pay_type,member_pay_type,member_money,is_mer_visible,fake_sales')->find();
        if (!$is_member && $special_money['is_mer_visible'] == 1) return array('error' => 1, 'msg' => '仅付费会员才可报名，请先充值会员');
        $validity = -1;
        if (in_array($special_money['money'], [0, 0.00]) || in_array($special_money['pay_type'], [PAY_NO_MONEY, PAY_PASSWORD])) {
            $isPay = 1;
            $validity = 0;
        } else {
            $isPay = (!$this->uid || $this->uid == 0) ? false : SpecialBuy::PaySpecial($id, $this->uid);
            if ($isPay) $validity = SpecialBuy::getSpecialEndTime($id, $this->uid);
        }
        if (in_array($special_money['member_money'], [0, 0.00]) || in_array($special_money['member_pay_type'], [PAY_NO_MONEY])) {
            if ($validity == -1 && $is_member) {
                $validity = bcsub($this->userInfo['overdue_time'], time(), 0);
            }
        }
        $count = SpecialModel::learning_records($id);
        $recordCoujnt = processingData(bcadd($special_money['fake_sales'], $count, 0));
        $isBatch = SpecialBatch::isBatch($id);//课程是否开启兑换活动
        $special['isBatch'] = $isBatch;
        $special['isPay'] = $isPay;
        $special['validity'] = $validity;
        $special['is_member'] = $is_member;
        $special['recordCoujnt'] = $recordCoujnt;
        $special['special']['subject'] = SpecialSubject::where('id', $special['special']['subject_id'])->find();
        return $special;
    }

    /**课程下课程数量
     * @param $id
     */
    public function numberCourses($id)
    {
        $special = SpecialModel::PreWhere()->find($id);
        $count = SpecialModel::numberChapters($special->type, $id);
        return JsonService::successful($count);
    }

    /**
     * 素材详情
     * @param $task_id 素材ID
     * @return mixed|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function task_info($task_id = 0, $specialId = 0, $try = 0)
    {
        $this->assign(['specialId' => $specialId, 'task_id' => $task_id, 'try' => $try]);
        return $this->fetch();
    }

    /**获取课节内容
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * special_id 课程ID task_id素材ID
     */
    public function getTaskInfo()
    {
        $data = UtilService::PostMore([
            ['special_id', 0],
            ['task_id', 0],
            ['try', 0]
        ], $this->request);
        if (!$this->uid) {
            return JsonService::fail('请先登录');
        }
        $special = SpecialModel::PreWhere()->where('id', $data['special_id'])->field('id,money,pay_type,member_pay_type,is_light,is_mer_visible,link,title,abstract,score,label,image')->find();
        if (!$special) return JsonService::fail('您查看的课程不存在');
        $user_level = !$this->uid ? 0 : $this->userInfo;
        if ($special['is_light']) {
            // 如果是试看
            if ($data['try']) {
                $taskInfo = SpecialModel::getSingleSpecialContent($data['special_id']);
                $taskInfo->content = $taskInfo->singleProfile->try_content;
                unset($taskInfo->profile);
                $isPay = 0;
            } else {
                if (in_array($special['money'], [0, 0.00]) || $special['pay_type'] == 0 || ($user_level['level'] > 0 && $special['member_pay_type'] == 0)) {
                    $isPay = 1;
                } else {
                    $isPay = (!$this->uid || $this->uid == 0) ? false : SpecialBuy::PaySpecial($data['special_id'], $this->uid);
                }
                if (!$isPay) return JsonService::fail('您无法观看该素材，请先购买');
                $taskInfo = SpecialModel::getSingleSpecialContent($data['special_id']);
            }
        } else {
            $taskInfo = SpecialTask::defaultWhere()->where('id', $data['task_id'])->find();
            if (!$taskInfo) return JsonService::fail('课程信息不存在无法观看');

            if ($taskInfo['is_show'] == 0) return JsonService::fail('该课程已经下架');

            $isPay = SpecialBuy::PaySpecial($data['special_id'], $this->uid);

            if ($taskInfo['type'] == 1) {
                $content = htmlspecialchars_decode($taskInfo->content ? $taskInfo->content : "");
            } else {
                $special_content = SpecialContent::where('special_id', $data['special_id'])->value("content");
                $content = htmlspecialchars_decode($taskInfo->detail ? $taskInfo->detail : $special_content);
            }

            $taskInfo->content = $content;

            if ($isPay || $special->pay_type == 0 || ($user_level['level'] > 0 && $special->member_pay_type == 0)) {
                $isPay = true;
            } else {
                $special_source = SpecialSource::where(['special_id' => $data['special_id'], 'source_id' => $data['task_id'], 'pay_status' => 0])->find();
                // 判断试看内容
                if ($taskInfo->is_try) {
                    $taskInfo->content = htmlspecialchars_decode($taskInfo->try_content ? $taskInfo->try_content : "");;
                    $array['taskInfo'] = $taskInfo ? $taskInfo->toArray() : [];
                    $array['specialInfo'] = $special->toArray();
                    $array['isPay'] = $isPay;
                    return JsonService::successful($array);
                }
                if (!$special_source) {
                    return JsonService::fail('该素材需要购买后才能观看');
                } else {
                    $special_source = $special_source->toArray();
                    $taskInfo = SpecialTask::defaultWhere()->where('id', $special_source['source_id'])->find();
                    if (!$taskInfo) return JsonService::fail('该素材需要购买后才能观看');
                    $taskInfo->content = $content;
                }
            }
        }

        $array['taskInfo'] = $taskInfo ? $taskInfo->toArray() : [];
        $array['taskInfo']['watch'] = SpecialWatch::whetherWatch($this->uid, $data['special_id'], $data['task_id']);
        $array['specialInfo'] = $special->toArray();
        $array['isPay'] = $isPay;

        // 对免流视频做处理
        if ($array['specialInfo']['is_light']) {
            if ($taskInfo->singleProfile->video_type == 4) {
                $fxdisk_full_path = get_fxdisk_full_path($taskInfo->singleProfile->link);
                if (!$fxdisk_full_path['status']) {
                    return JsonService::fail($fxdisk_full_path['msg']);
                }
                $array['taskInfo']['link'] = $fxdisk_full_path['path'];
            }
        } else {
            if ($taskInfo->video_type == 4) {
                $fxdisk_full_path = get_fxdisk_full_path($taskInfo->link);
                if (!$fxdisk_full_path['status']) {
                    return JsonService::fail($fxdisk_full_path['msg']);
                }
                $array['taskInfo']['link'] = $fxdisk_full_path['path'];
            }
        }
        return JsonService::successful($array);
    }


    /**记录课程浏览人
     * @param $id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addLearningRecords($id)
    {
        $special = SpecialModel::PreWhere()->find($id);
        SpecialModel::where('id', $id)->setInc('browse_count');
        if ($this->uid) {
            SpecialRecord::record($id, $this->uid);
            $time = strtotime('today');
            LearningRecords::recordLearning($id, $this->uid, $time);
            if ($special->lecturer_id) {
                Lecturer::where('id', $special->lecturer_id)->setInc('study');
            }
        }
        return JsonService::successful('ok');
    }

    /**用户课程评价
     * @param int $special_id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function user_comment_special($special_id = 0)
    {
        if (!$special_id) return JsonService::fail('参数错误!');
        if (SpecialReply::be(['special_id' => $special_id, 'uid' => $this->uid])) return JsonService::fail('该课程已评价!');
        $group = UtilService::postMore([
            ['comment', ''], ['pics', []], ['satisfied_score', 5]
        ]);
        if ($group['comment'] == '') return JsonService::fail('请填写评价内容');
        $group['comment'] = htmlspecialchars(trim($group['comment']));
        if (sensitive_words_filter($group['comment'])) return JsonService::fail('请注意您的用词，谢谢！！');
        if ($group['satisfied_score'] < 1) return JsonService::fail('请为课程满意度评分');
        $group = array_merge($group, [
            'uid' => $this->uid,
            'special_id' => $special_id
        ]);
        SpecialReply::beginTrans();
        $res = SpecialReply::reply($group);
        if (!$res) {
            SpecialReply::rollbackTrans();
            return JsonService::fail('评价失败!');
        }
        SpecialReply::uodateScore($special_id);
        SpecialReply::commitTrans();
        return JsonService::successful('评价成功!');
    }

    /**获取课程评价列表
     * @param string $special_id
     * @param int $page
     * @param int $limit
     * @param string $filter
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function special_reply_list($special_id = '', $page = 1, $limit = 8, $filter = 'all')
    {
        if (!$special_id || !is_numeric($special_id)) return JsonService::fail('参数错误!');
        $list = SpecialReply::getSpecialReplyList($special_id, $page, $limit, $filter);
        return JsonService::successful($list);
    }

    /**
     * 评价数据
     */
    public function special_reply_data($special_id = '')
    {
        if (!$special_id || !is_numeric($special_id)) return JsonService::fail('参数错误!');
        $data = SpecialReply::getSpecialReplyData($special_id);
        return JsonService::successful($data);
    }

    /**
     * 课程收藏
     * @param $id int 课程id
     * @return json
     */
    public function collect($id = 0)
    {
        if (!$id) return JsonService::fail('缺少参数');
        if (SpecialRelation::SetCollect($this->uid, $id))
            return JsonService::successful('成功');
        else
            return JsonService::fail('失败');
    }

    /**
     * 获取某个课程的课节列表
     * @return json
     * */
    public function get_course_list()
    {
        list($page, $limit, $special_id) = UtilService::getMore([
            ['page', 1],
            ['limit', 10],
            ['special_id', 0],
        ], null, true);
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        //不登录也能查看
        $task_list = SpecialCourse::getSpecialSourceList($special_id, $limit, $page, $this->uid, $is_member);
        if (!$task_list['list']) return JsonService::successful([]);
        foreach ($task_list['list'] as $k => $v) {
            $task_list['list'][$k]['type_name'] = SPECIAL_TYPE[$v['type']];
            if(!isset($task_list['list'][$k]['special_task'])){
                $task_list['list'][$k]['watch'] = SpecialWatch::whetherWatch($this->uid, $special_id, $v['id']);
            }
        }
        return JsonService::successful($task_list);
    }

    /**
     * 获取专栏套餐 专栏关联的课程
     */
    public function get_cloumn_task()
    {
        list($page, $limit, $special_id, $source_id) = UtilService::getMore([
            ['page', 1],
            ['limit', 10],
            ['special_id', 0],
            ['source_id', 0],
        ], null, true);
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $task_list = SpecialCourse::get_cloumn_special($special_id, $source_id, $limit, $page, $this->uid, $is_member);
        if (!$task_list['list']) return JsonService::successful([]);
        foreach ($task_list['list'] as $k => $v) {
            $task_list['list'][$k]['type_name'] = SPECIAL_TYPE[$v['type']];
        }
        return JsonService::successful($task_list);
    }

    /**
     * 播放数量增加
     * @param int $task_id 任务id
     * @return json
     * */
    public function play_num($task_id = 0, $special_id = 0)
    {
        if ($task_id == 0 || $special_id == 0) return JsonService::fail('缺少参数');
        try {
            $add_task_play_count = SpecialTask::bcInc($task_id, 'play_count', 1);
            if ($add_task_play_count) {
                $special_source = SpecialSource::getSpecialSource((int)$special_id, [$task_id]);
                if ($special_source) {
                    SpecialSource::where(['special_id' => $special_id, 'source_id' => $task_id])->setInc('play_count', 1);
                }
                return JsonService::successful('ok');
            } else {
                return JsonService::fail('err');
            }
        } catch (\Exception $e) {
            return JsonService::fail('err');
        }
    }


    /**
     * 购买失败删除订单
     * @param string $orderId 订单id
     * @return json
     * */
    public function del_order($orderId = '')
    {
        if (StoreOrder::where('order_id', $orderId)->update(['is_del' => 1]))
            return JsonService::successful();
        else
            return JsonService::fail();
    }


    /**
     * 课程分类
     * @return mixed
     */
    public function special_cate($cate_id = 0, $subject_id = 0)
    {
        $data = UtilService::GetMore([
            ['cate_id', 0],
            ['subject_id', 0],
            ['page', 1],
            ['limit', 16],
            ['type', ''],
            ['is_pay', ''],
            ['salesOrder', ''],
            ['scoreOrder', ''],
            ['search', '']
        ], $this->request);
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;

        $field = ['browse_count', 'image', 'title','type','sort','IFNULL(browse_count,0) + IFNULL(fake_sales,0) as sales','is_light','light_type','is_mer_visible', 'money','member_money','subject_id','pay_type', 'label', 'id','fake_sales','add_time'];
        $model = SpecialModel::setWhere($data,$is_member)->field($field);
        $allCourseList = $model->paginate($data['limit']);
        $items = $allCourseList->items();
        foreach ($items as &$item) {
            $item['count']=SpecialModel::numberChapters($item['type'],$item['id']);
            $count=SpecialModel::learning_records($item['id']);
            $item['browse_count']=processingData(bcadd($count,$item['fake_sales'],0));
            if($item['is_light']){
                $item['type']=SpecialModel::lightType($item['light_type']);
            }
        }
        $count = SpecialModel::setWhere($data,$is_member)->count();

        $this->assign([
            'cate_id' => (int)$cate_id,
            'subject_id' => (int)$subject_id,
            'allCourseList' => $allCourseList,
            'count' => $count
        ]);
        return $this->fetch();
    }

    /**
     * 获取课程分类
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_grade_cate()
    {
        $cateogry = SpecialSubject::with('children')->where(['is_show' => 1, 'is_del' => 0])->order('sort desc,id desc')->where('grade_id', 0)->select();
        $cateogry = count($cateogry) > 0 ? $cateogry->toArray() : [];
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        foreach ($cateogry as $key => &$item) {
            $cateId = SpecialSubject::subjectId($item['id']);
            $item['list'] = SpecialModel::cate_special_recommen_list($is_member, $cateId);
        }
        return JsonService::successful($cateogry);
    }

    /**获取分类
     * @param int $cate_id
     * @param int $subject_id
     */
    public function get_all_special_cate()
    {
        $cateogry = SpecialSubject::with('children')->where(['is_show' => 1, 'is_del' => 0])->order('sort desc,id desc')->where('grade_id', 0)->select();
        $cateogry = count($cateogry) > 0 ? $cateogry->toArray() : [];
        $children = SpecialSubject::where(['is_show' => 1, 'is_del' => 0])->order('sort desc,id desc')->where('grade_id', '>', 0)->select();
        $children = count($children) > 0 ? $children->toArray() : [];
        $data['cateogry'] = $cateogry;
        $data['children'] = $children;
        return JsonService::successful($data);
    }

    /**
     * 获取所有课程
     * @param int $grade_id 一级分类ID
     * @param int $subject_id 二级分类ID
     * @param string $search
     * @param int $page
     * @param int $limit
     * @param int $type 课程类型
     * @param int $is_pay 课程性质 0=免费 1=付费
     */
    public function get_special_list()
    {
        $data = UtilService::GetMore([
            ['cate_id', 0],
            ['subject_id', 0],
            ['page', 1],
            ['limit', 12],
            ['type', ''],
            ['is_pay', ''],
            ['salesOrder', ''],
            ['scoreOrder', ''],
            ['search', '']
        ], $this->request);
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        return JsonService::successful(SpecialModel::get_special_list($data, $is_member));
    }

    /**
     * 获取所有直播课
     * @param int $grade_id 一级分类ID
     * @param int $subject_id 二级分类ID
     * @param string $search
     * @param int $page
     * @param int $limit
     * @param int $type 课程类型
     * @param int $is_pay 课程性质 0=免费 1=付费 2=密码
     */
    public function get_live_special_list()
    {
        $data = UtilService::GetMore([
            ['cate_id', 0],
            ['subject_id', 0],
            ['page', 1],
            ['limit', 12],
            ['is_pay', ''],
            ['salesOrder', ''],
            ['search', '']
        ], $this->request);
        $data['type'] = 4;
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        return JsonService::successful(SpecialModel::get_live_special_list_all($data, $is_member));
    }

    /**
     * 学习记录
     * @return mixed
     */
    public function record()
    {
        $this->assign(['homeLogo' => SystemConfigService::get('home_logo')]);
        return $this->fetch();
    }

    /**
     * 是否可以播放
     * @param int $task_id 任务id
     * @return string
     * */
    public function get_task_link($task_id = 0, $special_id = 0)
    {
        if (!$special_id || !$task_id) return JsonService::fail('参数错误');
        $special_source = SpecialSource::getSpecialSource($special_id, [$task_id]);
        $tash = $special_source ? $special_source->toArray() : [];
        if (!$tash) {
            return JsonService::fail('您查看的视频已经下架');
        } else {
            return JsonService::successful($tash);
        }
    }

    /**检测用户身份
     * @throws \Exception
     */
    public function isMember()
    {
        $user_level = !$this->uid ? 0 : $this->userInfo;
        $data['is_member'] = isset($user_level['level']) ? $user_level['level'] : 0;
        $data['now_money'] = isset($user_level['now_money']) ? $user_level['now_money'] : 0;
        return JsonService::successful($data);
    }


    /**
     * 储存素材观看时间
     */
    public function viewing()
    {
        $data = UtilService::PostMore([
            ['special_id', 0],
            ['task_id', 0],
            ['viewing_time', 0],
            ['percentage', 0],
            ['total', 0]
        ], $this->request);
        $res = SpecialWatch::materialViewing($this->uid, $data);
        return JsonService::successful($res);
    }

    /**课程推荐课程
     * @param int $id 课程ID
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recommended_courses($id)
    {
        if (!$id) return JsonService::fail('缺少参数');
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $field = ['browse_count', 'fake_sales', 'IFNULL(browse_count,0) + IFNULL(fake_sales,0) as sales', 'image', 'title', 'type', 'money', 'pink_money', 'is_light', 'light_type', 'is_mer_visible', 'subject_id', 'pay_type', 'label', 'id', 'is_show', 'is_del'];
        $model = SpecialModel::PreWhere()->where('id', '<>', $id)->where(['is_show' => 1]);
        if (!$is_member) $model = $model->where(['is_mer_visible' => 0]);
        $specialList = $model->field($field)->order('sales desc,sort desc')
            ->limit(4)->select();
        $specialList = count($specialList) > 0 ? $specialList->toArray() : [];

        foreach ($specialList as $key => &$item) {
            $item['count'] = SpecialModel::numberChapters($item['type'], $item['id']);
            $count = SpecialModel::learning_records($item['id']);
            $item['sales'] = bcadd($item['fake_sales'], $count, 0);
            $item['browse_count'] = processingData($item['sales']);
        }
        $browse = array_column($specialList, 'sales');
        array_multisort($browse, SORT_DESC, $specialList);
        return JsonService::successful($specialList);
    }
    public function recommended_courses_render($id)
    {
        if (!$id) return JsonService::fail('缺少参数');
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $field = ['browse_count', 'fake_sales', 'IFNULL(browse_count,0) + IFNULL(fake_sales,0) as sales', 'image', 'title', 'type', 'money', 'pink_money', 'is_light', 'light_type', 'is_mer_visible', 'subject_id', 'pay_type', 'label', 'id', 'is_show', 'is_del'];
        $model = SpecialModel::PreWhere()->where('id', '<>', $id)->where(['is_show' => 1]);
        if (!$is_member) $model = $model->where(['is_mer_visible' => 0]);
        $specialList = $model->field($field)->order('sales desc,sort desc')
            ->limit(4)->select();
        $specialList = count($specialList) > 0 ? $specialList->toArray() : [];

        foreach ($specialList as $key => &$item) {
            $item['count'] = SpecialModel::numberChapters($item['type'], $item['id']);
            $count = SpecialModel::learning_records($item['id']);
            $item['sales'] = bcadd($item['fake_sales'], $count, 0);
            $item['browse_count'] = processingData($item['sales']);
        }
        $browse = array_column($specialList, 'sales');
        array_multisort($browse, SORT_DESC, $specialList);
        return $specialList;
    }

    /**课程关联的资料
     * @param int $special_id 课程ID
     */
    public function special_data_download($special_id = 0)
    {
        if (!$special_id) return JsonService::fail('缺少参数,无法访问');
        $data = Relation::getRelationDataDownload(4, $special_id);
        return JsonService::successful($data);
    }
    public function special_data_download_render($special_id = 0)
    {
        if (!$special_id) return JsonService::fail('缺少参数,无法访问');
        $data = Relation::getRelationDataDownload(4, $special_id);
        return $data;
    }

    /**
     * 讲师详情
     * @return mixed
     */
    public function teacher_detail($id = 0)
    {
        if (!$id) {
            return $this->redirect(url('/404'));
        }
        $teacher = Lecturer::details($id);
        $lecturer_id = $id;

        $field = ['browse_count', 'image', 'title','type', 'money', 'pink_money' ,'is_light','light_type' ,'is_mer_visible' ,'member_money', 'is_pink', 'subject_id', 'pay_type', 'label', 'id','is_show','is_del','lecturer_id'];
        $model = SpecialModel::PreWhere();
        $model = $model->where(['is_show'=>1,'lecturer_id'=>$lecturer_id])->order('sort desc,id desc');
        $course_list  = $model->field($field)->paginate(16);
        $items = $course_list->items();
        foreach ($items as &$item) {
            $item['count']=SpecialModel::numberChapters($item['type'],$item['id']);
        }
        $count= SpecialModel::PreWhere()->where(['is_show'=>1,'lecturer_id'=>$lecturer_id])->count();
//        dump($course_list['data']);
        $this->assign([
            'id' => $id,
            'teacher' => $teacher,
            'course_list' => $course_list,
            'count' => $count
        ]);
        return $this->fetch();
    }

    /**
     * 讲师列表
     * @return mixed
     */
    public function teacher_list()
    {
        list($page,$limit) = UtilService::GetMore([
            ['page', 1],
            ['limit', 18]
        ], $this->request, true);
        $teachers=Lecturer::setWhere(['page' => $page])
            ->field('id,lecturer_name,lecturer_head,label,curriculum,explain,study,sort,is_show,is_del')
            ->paginate((int)$limit);
        $items = $teachers->items();
        foreach ($items as $key=>&$value){
            $value['label'] =json_decode($value['label']);
        }
        $count= Lecturer::setWhere()->count();

        $this->assign([
            'teachers' => $teachers,
            'count' => $count
        ]);
        return $this->fetch();
    }

    public function exchange()
    {
        return $this->fetch();
    }

    /**
     * 兑换码提交兑换
     */
    public function exchange_submit()
    {
        list($special_id, $code) = UtilService::PostMore([
            ['special_id', 0],
            ['code', '']
        ], $this->request, true);
        if (!$code) return JsonService::fail('请输入兑换码');
        // 通过code反查出special_id
        $exchange = SpecialExchange::where(['exchange_code' => $code])->find();
        if (!$exchange) return JsonService::fail('兑换码不存在');
        $special_id = $exchange['special_id'];
        $data = SpecialExchange::userExchangeSubmit($this->uid, $special_id, $code);
        if ($data)
            return JsonService::successful($data);
        else
            return JsonService::fail(SpecialExchange::getErrorInfo('兑换失败!'));
    }

    /**
     * 课程检测是否达到领取证书标准
     * $special_id 课程ID
     * $is_light 是否为精简课
     */
    public function inspect($special_id = 0, $is_light = 0, $type=0)
    {
        if (!$this->uid) return JsonService::fail('err');
        // 判断是否购买课程
        $isPay = \app\wap\model\special\SpecialBuy::PaySpecial($special_id, $this->uid);
        $user_level = !$this->uid ? 0 : $this->userInfo;
        $special = \app\wap\model\special\Special::PreWhere()->where('id', $special_id)->field('pay_type,member_pay_type')->find();
        if ($isPay || $special->pay_type == 0 || ($user_level['level'] > 0 && $special->member_pay_type == 0)) {
            $isPay = true;
        }
        if (!$isPay) return JsonService::fail('请先购买课程');
        // 对套餐课特殊处理
        if ($type == 5) {
            $res = CertificateRelated::getCertificateRelatedSys($special_id, $is_light, 1, $this->uid);
            if ($res) {
                return JsonService::successful('ok', $res);
            } else {
                return JsonService::fail('未达到证书领取标准', $res);
            }
        }
        $res = CertificateRelated::getCertificateRelated($special_id, $is_light, 1, $this->uid);
        if ($res) {
            return JsonService::successful('ok');
        } else {
            return JsonService::fail('未达到证书领取标准');
        }
    }

    /**用户领取证书
     * $special_id 课程ID
     */
    public function getTheCertificate($special_id, $type=0)
    {
        if (!$this->uid){
            return JsonService::fail('请先登录');
        }
        if (!$this->userInfo['full_name']){
            return JsonService::fail('请先到个人中心填写真实姓名再领取证书');
        }
        // 判断是否购买课程
        $isPay = \app\wap\model\special\SpecialBuy::PaySpecial($special_id, $this->uid);
        $user_level = !$this->uid ? 0 : $this->userInfo;
        $special = \app\wap\model\special\Special::PreWhere()->where('id', $special_id)->field('pay_type,member_pay_type')->find();
        if ($isPay || $special->pay_type == 0 || ($user_level['level'] > 0 && $special->member_pay_type == 0)) {
            $isPay = true;
        }
        if (!$isPay) return JsonService::fail('请先购买课程');
        $cid=CertificateRelated::where(['related'=>$special_id,'obtain'=>1,'is_show'=>1])->value('cid');
        if(!$cid) return JsonService::fail('该课程没有可领取的证书');

        $record=CertificateRecord::setWhere($this->uid)->where(['source_id'=>$special_id,'obtain'=>1])->find();
        if($record) return JsonService::fail('已领取过该课证书');

        // 对套餐课特殊处理
        if ($type == 5) {
            $res = CertificateRelated::getCertificateRelatedSys($special_id, 0, 1, $this->uid);
            if (!$res) {
                return JsonService::fail('未达到证书领取标准');
            }
        }
        $res = CertificateRecord::getUserTheCertificate($special_id, 1, $this->uid);
        if ($res) return JsonService::successful($res);
        else return JsonService::fail('领取失败，未达到证书领取条件');
    }
}
