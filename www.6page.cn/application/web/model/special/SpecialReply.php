<?php


namespace app\web\model\special;


use basic\ModelBasic;
use service\UtilService;
use traits\ModelTrait;
use app\web\model\special\Special;
use app\web\model\user\User;

/**课程评价
 * Class SpecialReply
 * @package app\web\model\special
 */
class SpecialReply extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    protected function setPicsAttr($value)
    {
        return is_array($value) ? json_encode($value) : $value;
    }

    protected function getPicsAttr($value)
    {
        return json_decode($value,true);
    }

    public static function reply($group)
    {
        return self::set($group);
    }

    /**字段过滤
     * @param string $alias
     * @return SpecialReply
     */
    public static function specialValidWhere($alias = '')
    {
        $model = new self;
        if($alias){
            $model->alias($alias);
            $alias .= '.';
        }
        return $model->where("{$alias}is_del",0);
    }

    /**条件处理
     * @param $special_id
     * @return SpecialReply
     */
    public static function get_where_reply($special_id)
    {
        $model = self::specialValidWhere('A')->where('A.special_id',$special_id)->where('A.is_show',1)
            ->field('A.satisfied_score,A.comment,A.pics,A.add_time,A.merchant_reply_content,S.title,A.is_selected,A.uid,A.id')
            ->join('__SPECIAL__ S','A.special_id = S.id');
        return $model;
    }
    /**获取课程的评价列表
     * @param $special_id
     * @param string $order
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSpecialReplyList($special_id,$page = 0,$limit = 8,$order = 'All')
    {
        $model=self::get_where_reply($special_id);
        $baseOrder = 'A.is_selected DESC,A.add_time DESC,A.satisfied_score DESC';
        $model = $model->order($baseOrder);
        $list  = $model->page($page,$limit)->select()->toArray()?:[];
        foreach ($list as $k=>$reply){
            $list[$k] = self::tidySpecialReply($reply);
        }
        $count=self::get_where_reply($special_id)->count();
        return compact('list','count');
    }

    /**用户信息处理
     * @param $uid
     * @param $reply_id
     * @return array|false|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function userData($uid,$reply_id)
    {
        if($uid){
            $user=User::where('uid',$uid)->field('nickname,avatar')->find();
        }else{
            $user=self::getDb('reply_false')->where('reply_id',$reply_id)->field('nickname,avatar')->find();
        }
        return $user;
    }

    /**评价内容处理
     * @param $res
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function tidySpecialReply($res)
    {
        $user=self::userData($res['uid'],$res['id']);
        $res['nickname'] = UtilService::anonymity($user['nickname']);
        $res['avatar'] = $user['avatar'];
        $res['add_time'] = date('Y-m-d H:i',$res['add_time']);
        $res['star'] = $res['satisfied_score'];
        $res['comment'] = $res['comment']?:'此用户没有填写评价';
        return $res;
    }

    /**评论数据
     * @param $special_id
     * @return mixed
     * @throws \think\Exception
     */
    public static function getSpecialReplyData($special_id)
    {
        $data['whole']=self::specialValidWhere()->where('special_id',$special_id)->where('is_show',1)->count();//全部评论
        $score=Special::where('id',$special_id)->value('score');
        if($data['whole']>0 && $score>0){
            $data['score']=$score;
        }else if($data['whole']>0 && $score==0){
            $data['score']=round(self::specialValidWhere()->where('special_id',$special_id)->where('is_show',1)->avg('satisfied_score'),1);
        }else{
            $data['score']=0;
        }
        return $data;
    }

    /**获取评论
     * @param $special_id
     * @return mixed|null
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getRecSpecialReply($special_id)
    {
        $res = self::specialValidWhere('A')->where('A.special_id',$special_id)
            ->field('A.is_selected,A.satisfied_score,A.comment,A.pics,A.add_time,B.nickname,B.avatar,A.merchant_reply_content,S.title')
            ->join('__USER__ B','A.uid = B.uid')
            ->join('__SPECIAL__ S','A.special_id = S.id')->find();
        if(!$res) return null;
        return self::tidySpecialReply($res->toArray());
    }

    /**修改课程评分
     * @param $special_id
     * @return bool
     */
    public static function uodateScore($special_id)
    {
        $score=round(self::specialValidWhere()->where('special_id',$special_id)->avg('satisfied_score'),1);
        $data['score']=$score;
        return Special::edit($data,$special_id,'id');
    }

}
