<?php


namespace app\web\controller;

use Api\AliyunLive as ApiAliyunLive;
use app\web\model\live\LiveBarrage;
use app\web\model\live\LiveGoods;
use app\web\model\live\LiveHonouredGuest;
use app\web\model\live\LiveReward;
use app\web\model\live\LiveStudio;
use app\web\model\live\LivePlayback;
use app\web\model\live\LiveUser;
use app\web\model\live\LiveGift;
use app\web\model\special\SpecialBuy;
use app\web\model\special\Special;
use app\web\model\special\SpecialContent;
use app\web\model\special\Lecturer;
use app\web\model\user\User;
use app\web\model\user\UserBill;
use service\SystemConfigService;
use service\UtilService;
use think\Config;
use service\JsonService;
use think\Cookie;
use think\Url;

/**直播间控制器
 * Class Live
 * @package app\web\controller
 */
class Live extends AuthController
{

    /**
     * 白名单
     * */
    public static function WhiteList()
    {
        return [
            'get_live_record_list',
            'special_live'
        ];
    }

    /**
     * 阿里云直播句柄
     * @var \Api\AliyunLive
     */
    protected $aliyunLive;

    protected function _initialize()
    {
        parent::_initialize();
        $this->aliyunLive = ApiAliyunLive::instance([
            'AccessKey' => SystemConfigService::get('accessKeyId'),
            'AccessKeySecret' => SystemConfigService::get('accessKeySecret'),
            'OssEndpoint' => SystemConfigService::get('aliyun_live_end_point'),
            'OssBucket' => SystemConfigService::get('aliyun_live_oss_bucket'),
            'appName' => SystemConfigService::get('aliyun_live_appName'),
            'payKey' => SystemConfigService::get('aliyun_live_play_key'),
            'key' => SystemConfigService::get('aliyun_live_push_key'),
            'playLike' => SystemConfigService::get('aliyun_live_playLike'),
            'rtmpLink' => SystemConfigService::get('aliyun_live_rtmpLink'),
        ]);
    }


    /**
     * 直播间主页
     * @param string $stream_name
     * @return mixed|void
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($stream_name = '', $record_id = 0)
    {
        $this->assign(['stream_name' => $stream_name, 'record_id' => $record_id]);
        return $this->fetch();
    }

    /**直播间信息
     * @param string $stream_name
     * @param int $record_id
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function live_studio_index($stream_name = '', $record_id = 0)
    {
        if (!$stream_name) return JsonService::fail('缺少在直播间号！');
        $liveInfo = LiveStudio::where('stream_name', $stream_name)->find();
        if (!$liveInfo) return JsonService::fail('直播间不存在');
        if ($liveInfo->is_del) return JsonService::fail('直播间已被删除');
        $userInfo = LiveUser::setLiveUser($this->uid, $liveInfo->id);
        if ($userInfo === false) return JsonService::fail(LiveUser::getErrorInfo('用户写入不成功'));
        $specialLive = Special::where(['is_show' => 1, 'is_del' => 0, 'id' => $liveInfo->special_id])->find();
        if (!$specialLive) return JsonService::fail('课程不存在或者已被删除');
        $user_level = !$this->uid ? 0 : $this->userInfo;
        if ($specialLive->pay_type == 1 && !SpecialBuy::PaySpecial($specialLive->id, $this->uid)) {
            if ($specialLive->member_pay_type == 1 || ($user_level['level'] <= 0 && $specialLive->member_pay_type == 0)) {
                return JsonService::fail('您还没有支付请支付后再进行观看');
            }
        }
        if ($specialLive->pay_type == 2) {
            $cookie_value = Cookie::get($stream_name . "studio_pwd");
            if (!$cookie_value || $cookie_value != $liveInfo['studio_pwd']) {
                return JsonService::fail('您需要先获得密码后再进行观看');
            }
        }
        $AliyunLive = $this->aliyunLive;
        if ($liveInfo->is_play)
            $PullUrl = $AliyunLive->getPullSteam($liveInfo->stream_name);
        else {
            $record_id = $record_id ? $record_id : $liveInfo->playback_record_id;
            if ($liveInfo->is_playback && $record_id) {
                $livePlayBack = LivePlayback::where(['RecordId' => $record_id, 'stream_name' => $liveInfo->stream_name])->find();
                $PullUrl = $livePlayBack ? $livePlayBack->playback_url : false;
            } else
                $PullUrl = false;
        }
        if ($PullUrl) {
            $scheme = $this->request->scheme() . '://';
            if ($scheme == 'https://') {
                $PullUrl = str_replace("http://", $scheme, $PullUrl);
            }
        }
        $live_status = 0;
        $datatime = strtotime($liveInfo->start_play_time);
        $endTime = strtotime($liveInfo->stop_play_time);
        if ($datatime < time() && $endTime > time())
            $live_status = 1; //正在直播
        else if ($datatime < time() && $endTime < time())
            $live_status = 2; //直播结束
        else if ($datatime > time())
            $live_status = 0; //尚未直播
        $user_type = LiveHonouredGuest::where(['uid' => $this->uid, 'live_id' => $liveInfo->id])->value('type');
        if (is_null($user_type)) $user_type = 2;
        $lecturer = [];
        if ($specialLive['lecturer_id']) {
            $lecturer = Lecturer::where('id', $specialLive['lecturer_id'])->field('lecturer_name,lecturer_head')->find();
            if(!$lecturer) $lecturer = [];
        }
        $uids = LiveHonouredGuest::where(['live_id' => $liveInfo->id])->column('uid');
        $content = SpecialContent::where('special_id', $specialLive['id'])->value('content');
        $liveInfo['content'] = htmlspecialchars_decode($content);
        // 处理虚拟直播视频源
        if ($liveInfo['is_fake'] && $liveInfo['video_type'] == 4) {
            $fxdisk_full_path = get_fxdisk_full_path($liveInfo['link']);
            if (!$fxdisk_full_path['status']) {
                return JsonService::fail($fxdisk_full_path['msg']);
            }
            $liveInfo['link'] = $fxdisk_full_path['path'];
        }
        // 获取服务器当前时间，用于计算播放器进度
        // 时间转换成年月日时分秒
        $liveInfo['current_time'] = date('Y-m-d H:i:s', time());

        $data['goldInfo'] = SystemConfigService::more("gold_name,gold_rate,gold_image");
        $data['liveInfo'] = $liveInfo;
        $data['lecturer'] = $lecturer;
        $data['UserSum'] = bcadd(LiveUser::where(['live_id' => $liveInfo->id, 'is_open_ben' => 0, 'is_online' => 1])->sum('visit_num'), $liveInfo->online_num, 0);
        $data['live_title'] = $liveInfo->live_title;
        $data['PullUrl'] = $PullUrl;
        $data['is_ban'] = $userInfo->is_ban;
        $data['room'] = $liveInfo->id;
        $data['datatime'] = $datatime;
        $data['workerman'] = Config::get('workerman.chat', []);
        $data['phone_type'] = UtilService::getDeviceType();
        $data['live_status'] = $live_status;
        $data['user_type'] = $user_type;
        $data['OpenCommentCount'] = LiveBarrage::where(['live_id' => $liveInfo->id, 'is_show' => 1])->count();
        $data['OpenCommentTime'] = LiveBarrage::where(['live_id' => $liveInfo->id, 'is_show' => 1])->order('add_time asc')->value('add_time');
        $data['CommentCount'] = LiveBarrage::where(['live_id' => $liveInfo->id, 'is_show' => 1])->where('uid', 'in', $uids)->count();
        $data['CommentTime'] = LiveBarrage::where(['live_id' => $liveInfo->id, 'is_show' => 1])->where('uid', 'in', $uids)->order('add_time asc')->value('add_time');
        return JsonService::successful($data);
    }

    /**
     * 获取助教评论
     */
    public function get_comment_list()
    {
        list($page, $limit, $live_id, $add_time) = UtilService::getMore([
            ['page', 0],
            ['limit', 20],
            ['live_id', 0],
            ['add_time', 0],
        ], $this->request, true);
        if (!$live_id) return JsonService::fail('缺少参数!');
        $uids = LiveHonouredGuest::where(['live_id' => $live_id])->column('uid');
        if (!$uids) {
            $ystemConfig = SystemConfigService::more(['site_name', 'home_logo']);
            $data = [
                'nickname' => $ystemConfig['site_name'],
                'avatar' => $ystemConfig['home_logo'],
                'user_type' => 2,
                'content' => LiveStudio::where('id', $live_id)->value('auto_phrase'),
                'id' => 0,
                'type' => 1,
                'uid' => 0
            ];
            return JsonService::successful(['list' => [$data], 'page' => 0]);
        }
        return JsonService::successful(LiveBarrage::getCommentList($uids, $live_id, $page, $limit, $add_time));
    }

    /**
     * 获取助教，讲师，普通人的评论
     */
    public function get_open_comment_list()
    {
        list($page, $limit, $live_id, $add_time) = UtilService::getMore([
            ['page', 0],
            ['limit', 20],
            ['live_id', 0],
            ['add_time', 0],
        ], $this->request, true);
        if (!$live_id) return JsonService::fail('缺少参数!');
        return JsonService::successful(LiveBarrage::getCommentList(false, $live_id, $page, $limit, $add_time));
    }
    /**
     * 获取直播间下的录制内容
     * @param string $record_id
     * @param string $stream_name
     * @param string $start_time
     * @param string $end_time
     * @param int $page
     * @param int $limit
     */
    public function get_live_record_list($special_id = '', $start_time = '', $end_time = '', $page = 1, $limit = 10)
    {
        if (!$special_id) return JsonService::fail('参数缺失');
        $specialInfo = Special::get($special_id);
        if (!$specialInfo) return JsonService::fail('直播课程不存在');
        $liveStudio = LiveStudio::where(['special_id' => $specialInfo['id']])->find();
        if (!$liveStudio) return JsonService::fail('缺少直播间');
        if (!$liveStudio['stream_name']) return JsonService::fail('缺少直播间id');
        if ($liveStudio['is_playback'] == 1) {
            $where['stream_name'] = $liveStudio['stream_name'];
            $where['page'] = $page;
            $where['limit'] = $limit;
            $where['start_time'] = $start_time;
            $where['end_time'] = $end_time;
            $data = LivePlayback::getLivePlaybackList($where);
        } else {
            $data = [];
            $count = 0;
            $data = compact('data', 'count');
        }
        return JsonService::successful($data);
    }

    /**
     * 打赏礼物列表
     */
    public function live_gift_list()
    {
        $live_gift = LiveGift::liveGiftList();
        return JsonService::successful($live_gift);
    }

    /**打赏接口
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function live_reward()
    {
        list($uid, $live_gift_id, $live_gift_num, $stream_name, $special_id) = UtilService::postMore([
            ['uid', 0],
            ['live_gift_id', 0],
            ['live_gift_num', 0],
            ['stream_name', 0],
            ['special_id', 0],
        ], $this->request, true);
        if (!$uid) return JsonService::fail('用户id缺失');
        if (!$live_gift_id || !is_numeric($live_gift_id)) return JsonService::fail('礼物id缺失');
        if (!$stream_name || !is_numeric($stream_name)) return JsonService::fail('直播间号缺失');
        if (!$special_id || !is_numeric($special_id)) return JsonService::fail('直播课程ID缺失');
        $user_info = $this->userInfo;
        if ($uid != $user_info['uid']) return JsonService::fail('非法用户');
        if (!$live_gift_num || !is_numeric($live_gift_num) || $live_gift_num < 0) return JsonService::fail('请选择礼物数量');
        //获取礼物配置列表
        $live_gift = LiveGift::liveGiftOne($live_gift_id);
        if (!$live_gift) return JsonService::fail('礼物不存在');
        //查看直播间是否存在
        $live_studio = LiveStudio::where(['stream_name' => $stream_name])->find();
        if (!$live_studio) return JsonService::fail('直播间不存在');
        $live_studio = $live_studio->toarray();
        if ($live_studio['special_id'] != $special_id) return JsonService::fail('直播课程有误');
        //看金币是否足够
        $gift_price = $live_gift['live_gift_price'] * $live_gift_num;
        $gold_name = SystemConfigService::get('gold_name');
        if ($user_info['gold_num'] <= 0 || $gift_price > $user_info['gold_num']) return JsonService::fail('您当前' . $gold_name . '不够，请充值', [], 406);
        try {
            User::beginTrans();
            //插入打赏数据
            $add_gift['uid'] = $uid;
            $add_gift['live_id'] = $live_studio['id'];
            $add_gift['nickname'] = $user_info['nickname'];
            $add_gift['gift_id'] = $live_gift_id;
            $add_gift['gift_name'] = $live_gift['live_gift_name'];
            $add_gift['gift_price'] = $live_gift['live_gift_price'];
            $add_gift['total_price'] = $gift_price;
            $add_gift['gift_num'] = $live_gift_num;
            $add_gift['add'] = time();
            $live_reward_id = LiveReward::insertLiveRewardData($add_gift);
            //插入聊天记录
            $add_barrage['uid'] = $uid;
            $add_barrage['to_uid'] = 0;
            $add_barrage['type'] = 4; //礼物
            $add_barrage['barrage'] = $live_gift_id; //礼物ID
            $add_barrage['live_id'] = $live_reward_id;
            $add_barrage['is_show'] = 1;
            LiveBarrage::set($add_barrage);
            //插入虚拟货币支出记录（资金监管记录表）
            $gold_name = SystemConfigService::get("gold_name");
            $mark = '用户赠送' . $stream_name . "号直播间" . $live_gift_num . '个' . $live_gift["live_gift_name"] . ',扣除' . $gold_name . $gift_price . '金币';
            UserBill::expend('用户打赏扣除金币', $uid, 'gold_num', 'live_reward', $gift_price, 0, $this->userInfo['gold_num'], $mark);
            User::bcDec($uid, 'gold_num', $gift_price, 'uid');
            User::commitTrans();
            return JsonService::successful($mark);
        } catch (\Exception $e) {
            User::rollbackTrans();
            return JsonService::fail('网路异常，打赏失败');
        }
    }

    /**
     * 带货商品列表
     */
    public function live_goods_list()
    {
        list($live_id) = UtilService::getMore([
            ['live_id', 0],
        ], $this->request, true);
        $is_member=isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        return JsonService::successful(LiveGoods::getLiveGoodsList(['is_show' => 1, 'live_id' => $live_id],$is_member, 0, 0));
    }

    /**直播礼物排行榜
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_live_reward()
    {
        list($uid, $live_id, $page, $limit) = UtilService::postMore([
            ['uid', 0],
            ['live_id', 0],
            ['page', 1],
            ['limit', 20],
        ], $this->request, true);
        if (!$uid) return JsonService::fail('用户参数缺失');
        $user_info = $this->userInfo;
        if ($uid != $user_info['uid']) return JsonService::fail('非法用户');
        if (!$live_id) return JsonService::fail('直播间参数缺失');
        $live_info = LiveStudio::where('id', $live_id)->find();
        if (!$live_info) return JsonService::fail('直播间不存在');
        $data = LiveReward::getLiveRewardList(['live_id' => $live_id], $page, $limit);
        $now_user_reward = LiveReward::getLiveRewardOne(['live_id' => $live_id, 'uid' => $uid]);
        $data['now_user_reward'] = $now_user_reward;
        return JsonService::successful($data);
    }
    /**
     * 所有直播课
     * @return mixed
     */
    public function special_live()
    {
        $where = UtilService::GetMore([
            ['cate_id', 0],
            ['subject_id', 0],
            ['page', 1],
            ['limit', 16],
            ['is_pay', ''],
            ['salesOrder', ''],
            ['search', '']
        ], $this->request);
        $where['type'] = 4;
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;

        $field = ['s.browse_count', 's.image','IFNULL(s.browse_count,0) + IFNULL(s.fake_sales,0) as sales', 's.title','s.type','s.sort','s.is_light','s.light_type','s.is_mer_visible', 's.money','s.member_money','s.subject_id','s.lecturer_id','s.pay_type', 's.label', 's.id','s.fake_sales','s.add_time','l.is_play','l.playback_record_id', 'l.start_play_time'];
        $model = Special::setWhere($where,$is_member,'s')->field($field);
        $model =$model->join('__LIVE_STUDIO__ l', 's.id=l.special_id', 'LEFT');
        $allCourseList = $model->paginate($where['limit']);
        $items = $allCourseList->items();
        foreach ($items as &$item) {
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
        $count = Special::setWhere($where,$is_member,'s')->join('__LIVE_STUDIO__ l', 's.id=l.special_id', 'LEFT')->count();
        $this->assign([
            'count' => $count,
            'allCourseList' => $allCourseList
        ]);
        return $this->fetch();
    }
}
