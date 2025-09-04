<?php


namespace app\admin\model\special;

use app\admin\model\live\LiveGoods;
use app\admin\model\live\LiveStudio;
use app\admin\model\order\StoreOrder;
use app\admin\model\special\SpecialSource;
use app\admin\model\system\RecommendRelation;
use app\admin\model\system\WebRecommendRelation;
use app\admin\model\system\MpRecommendRelation;
use think\cache\driver\Redis;
use traits\ModelTrait;
use basic\ModelBasic;
use app\admin\model\special\SpecialBuy;

/**
 * Class Special 课程
 * @package app\admin\model\special
 */
class Special extends ModelBasic
{
    use ModelTrait;

    protected static function init()
    {
        self::afterUpdate(function () {
            $subjectUrl = getUrlToDomain();
            del_redis_hash($subjectUrl."wap_index_has","recommend_list");
            del_redis_hash($subjectUrl."web_index_has","recommend_list");
        });
    }
    public function profile()
    {
        return $this->hasOne('SpecialContent', 'special_id', 'id')->field('content');
    }

    public function singleProfile()
    {
        return $this->hasOne('SpecialContent', 'special_id', 'id')->field('is_try,try_content,try_time,link,videoId,video_type,file_name,file_type,content');
    }

    public static function PreWhere($alert = '')
    {
        $alert = $alert ? $alert . '.' : '';
        return self::where([$alert . 'is_show' => 1, $alert . 'is_del' => 0]);
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
    //end
    //设置
    public static function getBannerKeyAttr($banner)
    {
        if (is_string($banner)) $banner = json_decode($banner, true);
        if ($banner === false) return [];
        $value = [];
        foreach ($banner as $item) {
            $value[] = [
                'is_show' => false,
                'pic' => $item
            ];
        }
        return $value;
    }

    //获取单个课程
    public static function getOne($id, $is_live = false)
    {
        $special = self::get($id);
        if (!$special) return false;
        if ($special->is_del) return false;
        $special->banner = self::getBannerKeyAttr($special->banner);
        $special->profile->content = htmlspecialchars_decode($special->profile->content);
        if ($is_live) {
            $liveInfo = LiveStudio::where('special_id', $special->id)->find();
            if (!$liveInfo) return self::setErrorInfo('暂未查到直播间');
            if ($liveInfo->is_del) return self::setErrorInfo('直播间已删除无法编辑');
            $liveInfo->live_duration = (strtotime($liveInfo->stop_play_time) - strtotime($liveInfo->start_play_time)) / 60;
            $liveInfo = $liveInfo->toArray();
        } else
            $liveInfo = [];
        return [$special->toArray(), $liveInfo];
    }

    //获取单个精简课
    public static function getsingleOne($id)
    {
        $special = self::get($id);
        if (!$special) return false;
        if ($special->is_del) return false;
        $special->singleProfile->content = htmlspecialchars_decode($special->singleProfile->content);
        return $special->toArray();
    }

    //设置条件
    public static function setWhere($where, $alert = '', $model = null)
    {
        $model = $model === null ? new self() : $model;
        if ($alert) $model = $model->alias($alert);
        $alert = $alert ? $alert . '.' : '';
        if (isset($where['order']) && $where['order'])
            $model = $model->order($alert . self::setOrder($where['order']));
        else
            $model = $model->order($alert.'sort desc,'.$alert.'id desc');
        if (isset($where['subject_id']) && $where['subject_id']) $model = $model->where($alert . 'subject_id', $where['subject_id']);
        if (isset($where['store_name']) && $where['store_name']!='') $model = $model->where($alert . 'title|' . $alert . 'abstract|' . $alert . 'phrase|'. $alert . 'id', "LIKE", "%$where[store_name]%");
        if (isset($where['is_show']) && $where['is_show'] !== '') $model = $model->where($alert . 'is_show', $where['is_show']);
        if (isset($where['type']) && $where['type'] && $where['type']<7) {
            $model = $model->where($alert . 'type', $where['type']);
        }else if(isset($where['type']) && $where['type'] && $where['type']==7){
            $where['is_light']=1;
        }
        if (isset($where['is_light']) && $where['is_light']) $model = $model->where($alert . 'is_light', $where['is_light']);
        if (isset($where['special_type']) && $where['special_type'] !== '') {
            $model = $model->where($alert . 'type', $where['special_type']);
        }
        if (isset($where['admin_id']) && $where['admin_id']) $model = $model->where($alert . 'admin_id', $where['admin_id']);
        if (isset($where['start_time']) && $where['start_time'] && isset($where['end_time']) && $where['end_time']) $model = $model->whereTime($alert . 'add_time', 'between', [strtotime($where['start_time']), strtotime($where['end_time'])]);
        return $model->where($alert .'is_del', 0);
    }

    /**拼团课程列表
     * @param $where
     */
    public static function getPinkList($where)
    {
        $data = self::setWhere($where, 'A')->field('A.*,T.content,S.name as subject_name')
            ->join('__SPECIAL_CONTENT__ T', 'T.special_id=A.id','LEFT')->join('__SPECIAL_SUBJECT__ S', 'S.id=A.subject_id','LEFT')
            ->page((int)$where['page'], (int)$where['limit'])->where('A.is_pink',1)->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            $item['recommend'] = RecommendRelation::where('a.link_id', $item['id'])->where('a.type', 'in', [0, 2,8])->alias('a')
                ->join('__RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
            $item['pink_end_time'] = $item['pink_end_time'] ? strtotime($item['pink_end_time']) : 0;
            $item['sales'] = StoreOrder::where(['paid' => 1, 'cart_id' => $item['id'], 'refund_status' => 0])->count();
            $liveGoods = LiveGoods::getOne(['special_id' => $item['id'], 'is_delete' => 0]);
            if($item['type']==SPECIAL_COLUMN){
                $item['sum'] =SpecialSource::alias('c')->where('c.special_id',$item['id'])->join('__SPECIAL__ s', 'c.source_id=s.id')->where('s.is_show',1)->count();
            }else{
                $item['sum'] =SpecialSource::where('special_id',$item['id'])->count();
            }

            $item['is_live_goods'] = 0;
            $item['live_goods_id'] = 0;
            if ($liveGoods) {
                $item['live_goods_id'] = $liveGoods->id;
                if ($liveGoods->is_show == 1) {
                    $item['is_live_goods'] = 1;
                }
            }
            //查看拼团状态,如果已结束关闭拼团
            if ($item['is_pink'] && $item['pink_end_time'] < time()) {
                self::update(['is_pink' => 0], ['id' => $item['id']]);
                $item['is_pink'] = 0;
            }
            if(!$item['is_pink']){
                $item['pink_money'] = 0;
            }
            $item['stream_name'] = LiveStudio::where('special_id', $item['id'])->value('stream_name');
            $item['live_id'] = LiveStudio::where('special_id', $item['id'])->value('id');
            $item['is_play'] =0;
            if ($item['live_id']) {
                $item['online_num'] = LiveStudio::where('id', $item['live_id'])->value('online_num');
                $item['is_play'] = LiveStudio::where('id', $item['live_id'])->value('is_play') ? 1 : 0;
            }
            $item['buy_user_num'] = StoreOrder::where(['paid' => 1, 'cart_id' => $item['id']])->count('id');
            $item['start_play_time'] = LiveStudio::where('special_id', $item['id'])->value('start_play_time');
        }
        $count = self::setWhere($where)->where('is_pink',1)->count();
        return compact('data', 'count');
    }
    //查找课程列表
    public static function getSpecialList($where)
    {
        $data = self::setWhere($where, 'A')->field('A.*,T.content,S.name as subject_name')
            ->join('__SPECIAL_CONTENT__ T', 'T.special_id=A.id','LEFT')->join('__SPECIAL_SUBJECT__ S', 'S.id=A.subject_id','LEFT')
            ->page((int)$where['page'], (int)$where['limit'])->select();
        $data = count($data) ? $data->toArray() : [];
        foreach ($data as &$item) {
            $item['recommend'] = RecommendRelation::where('a.link_id', $item['id'])->where('a.type', 'in', [0, 2,8])->alias('a')
                ->join('__RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
            $item['web_recommend'] = WebRecommendRelation::where('a.link_id', $item['id'])->where('a.type', 'in', [0, 1])->alias('a')
                ->join('__WEB_RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
            $item['mp_recommend'] = MpRecommendRelation::where('a.link_id', $item['id'])->where('a.type', 'in', [0, 1])->alias('a')
                ->join('__MP_RECOMMEND__ r', 'a.recommend_id=r.id')->column('a.id,r.title');
            $item['pink_end_time'] = $item['pink_end_time'] ? strtotime($item['pink_end_time']) : 0;
            $item['sales'] = StoreOrder::where(['paid' => 1, 'cart_id' => $item['id'], 'refund_status' => 0])->count();
            $liveGoods = LiveGoods::getOne(['special_id' => $item['id'], 'is_delete' => 0]);

            if($item['type']==SPECIAL_COLUMN){
                $item['sum'] =SpecialSource::alias('c')->where('c.special_id',$item['id'])->join('__SPECIAL__ s', 'c.source_id=s.id')->where('s.is_show',1)->count();
            }else{
                $item['sum'] =SpecialSource::where('special_id',$item['id'])->count();
            }

            $item['is_live_goods'] = 0;
            $item['live_goods_id'] = 0;
            if ($liveGoods) {
                $item['live_goods_id'] = $liveGoods->id;
                if ($liveGoods->is_show == 1) {
                    $item['is_live_goods'] = 1;
                }
            }
            //查看拼团状态,如果已结束关闭拼团
            if ($item['is_pink'] && $item['pink_end_time'] < time()) {
                self::update(['is_pink' => 0], ['id' => $item['id']]);
                $item['is_pink'] = 0;
            }
            if(!$item['is_pink']){
                $item['pink_money'] = 0;
            }
            if ($where['type'] == 4) $item['stream_name'] = LiveStudio::where('special_id', $item['id'])->value('stream_name');
            $item['live_id'] = LiveStudio::where('special_id', $item['id'])->value('id');
            $item['is_play'] =0;
            if ($item['live_id']) {
                $item['online_num'] = LiveStudio::where('id', $item['live_id'])->value('online_num');
                $item['is_play'] = LiveStudio::where('id', $item['live_id'])->value('is_play') ? 1 : 0;
            }
            $item['buy_user_num'] = StoreOrder::where(['paid' => 1, 'cart_id' => $item['id']])->count('id');
            $item['start_play_time'] = LiveStudio::where('special_id', $item['id'])->value('start_play_time');
        }
        $count = self::setWhere($where)->count();
        return compact('data', 'count');
    }

    /**获取试题关联的课程
     * @param $relation_ids
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getKnowledgePoints($relation_ids)
    {
        $data = self::PreWhere()->where('id','in',$relation_ids)->select();
        $data=count($data)>0 ? $data->toArray() : [];
        $count = self::PreWhere()->where('id','in',$relation_ids)->count();
        return compact('data', 'count');
    }

    public static function getUserWhere($where)
    {
        return self::alias('s')->join('SpecialBuy b','s.id=b.special_id')
            ->where(['b.uid'=>$where['uid'],'s.is_del'=>0,'b.is_del'=>0,'b.type'=>3])
            ->field('s.title,s.type,s.id,s.image');
    }

    /**已获得课程
     * @param $where
     * @return array
     * @throws \think\Exception
     */
    public static function getUserSpecialList($where)
    {
        $data=self::getUserWhere($where)->page($where['page'],$where['limit'])->select();
        $count = self::getUserWhere($where)->count();
        return compact('data', 'count');
    }

    public static function getUserSpecialLists($where,$special_source,$type)
    {
        if ($type == 0){
            $data = Special::setWhere($where)->where('id','not in',$special_source)->whereIn('type',[SPECIAL_IMAGE_TEXT, SPECIAL_AUDIO, SPECIAL_VIDEO,SPECIAL_COLUMN,SPECIAL_OTHER])->page((int)$where['page'], (int)$where['limit'])->select();
            $data = count($data) ? $data->toArray() : [];
            $count = Special::setWhere($where)->where('id','not in',$special_source)->whereIn('type',[SPECIAL_IMAGE_TEXT, SPECIAL_AUDIO, SPECIAL_VIDEO,SPECIAL_COLUMN,SPECIAL_OTHER])->count();
        }elseif ($type == 1){
            $data = Special::setWhere($where)->where('id','not in',$special_source)->whereIn('type',[SPECIAL_LIVE])->page((int)$where['page'], (int)$where['limit'])->select();
            $data = count($data) ? $data->toArray() : [];
            $count = Special::setWhere($where)->whereIn('type',[SPECIAL_LIVE])->count();

        }
        return compact('data', 'count');
    }
}
