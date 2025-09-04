<?php


namespace app\web\model\recommend;

use app\web\model\special\Lecturer;
use app\web\model\special\Special;
use app\web\model\article\Article;
use app\web\model\material\DataDownload;
use basic\ModelBasic;
use traits\ModelTrait;

/**推荐内容
 * Class WebRecommendRelation
 * @package app\web\model\recommend
 */
class WebRecommendRelation extends ModelBasic
{
    use ModelTrait;

    /**
     * 获取主页推荐列表下的课程和图文内容
     * @param int $recommend_id 推荐id
     * @param int $type 类型 0=课程,1=直播 2=讲师 3=资料
     * @param int $limit 显示多少条
     * @return array
     * */
    public static function getRelationList($recommend_id, $type,$limit,$is_member)
    {
        $limit = $limit ? $limit : 4;
        if ($type == 0){
            $model = self::where('a.recommend_id', $recommend_id)
                    ->alias('a')->order('a.sort desc,a.add_time desc')
                    ->join("__SPECIAL__ p", 'p.id=a.link_id')
                    ->join('__SPECIAL_SUBJECT__ j', 'j.id=p.subject_id', 'LEFT')
                    ->where(['p.is_show' => 1, 'p.is_del' => 0]);
                    if(!$is_member) $model =$model->where(['p.is_mer_visible' => 0]);
            $list = $model->limit($limit)->field(['p.id','p.pink_money','p.is_light','p.light_type','p.is_mer_visible', 'p.is_pink','p.sort','p.title', 'p.image', 'p.abstract', 'p.label', 'p.image', 'p.money', 'p.pay_type', 'p.type as special_type','a.link_id','a.add_time','p.browse_count','p.member_pay_type', 'p.fake_sales', 'p.member_money'])
                    ->select();
        }elseif ($type == 1){
            $model = self::where('a.recommend_id', $recommend_id)
                    ->alias('a')->order('a.sort desc,a.add_time desc')
                    ->join("__SPECIAL__ p", 'p.id=a.link_id')
                    ->join('__SPECIAL_SUBJECT__ j', 'j.id=p.subject_id', 'LEFT')
                    ->join('__LIVE_STUDIO__ l', 'p.id=l.special_id', 'LEFT')
                    ->where(['p.is_show' => 1,'p.type' => 4, 'p.is_del' => 0]);
                    if(!$is_member) $model =$model->where(['p.is_mer_visible' => 0]);
            $list = $model->limit($limit)->field(['p.id','p.pink_money','p.is_light','p.lecturer_id','p.light_type','p.is_mer_visible', 'p.is_pink','p.sort','p.title', 'p.image', 'p.abstract', 'p.label', 'p.image', 'p.money', 'p.pay_type', 'p.type as special_type', 'a.link_id','a.add_time','p.browse_count','p.member_pay_type','p.fake_sales', 'p.member_money','l.is_play','l.playback_record_id', 'l.start_play_time'])
                    ->select();
        }elseif ($type == 2){
            $list = self::where('a.recommend_id', $recommend_id)
                ->alias('a')->order('a.sort desc,a.add_time desc')->limit($limit)
                ->join("Lecturer l", 'l.id=a.link_id')
                ->where(['l.is_show' => 1, 'l.is_del' => 0])
                ->field(['l.id','l.lecturer_name', 'l.lecturer_head', 'l.label','l.explain','a.link_id','a.add_time'])
                ->select();
        }elseif ($type == 3){
            $list = self::where('a.recommend_id', $recommend_id)
                ->alias('a')->order('a.sort desc,a.add_time desc')->limit($limit)
                ->join("DataDownload d", 'd.id=a.link_id')
                ->where(['d.is_show' => 1, 'd.is_del' => 0])
                ->field(['d.id', 'd.title', 'd.description', 'd.image','d.sales','d.ficti','d.money','d.pay_type', 'd.is_show', 'd.member_pay_type', 'd.member_money', 'd.is_del', 'a.link_id','a.add_time'])
                ->select();
        }elseif ($type == 4){
            $list = self::where('a.recommend_id', $recommend_id)
                ->alias('a')->order('a.sort desc,a.add_time desc')->limit(4)
                ->join("Article d", 'd.id=a.link_id')
                ->where(['d.is_show' => 1, 'd.hide' => 0])
                ->field(['d.*', 'a.link_id','a.add_time'])
                ->select();
        }else if($type == 5){
            $list =[];
        }
        $list = (count($list))>0 ? $list->toArray() : $list;
        foreach ($list as &$item) {
            if($type==0){
                if (!isset($item['money'])) $item['money'] = 0;
                $item['count']=Special::numberChapters($item['special_type'],$item['id']);
                $item['label'] = (isset($item['label']) && $item['label'] && !is_array($item['label'])) ? json_decode($item['label']) : [];
                $special_type_name = "";
                if (isset($item['special_type']) && SPECIAL_TYPE[$item['special_type']] && $item['special_type']!=6) {
                    $special_type_name = explode("课程",SPECIAL_TYPE[$item['special_type']]) ? explode("课程",SPECIAL_TYPE[$item['special_type']])[0] : "";
                }else{
                    if($item['is_light']){
                        $special_type_name=lightTypeNmae($item['light_type']);
                    }
                }
                $item['special_type_name'] = $special_type_name;
                $count=Special::learning_records($item['id']);
                $item['browse_count']=processingData(bcadd($item['fake_sales'],$count,0));
            }else if($type==1){
                if (!isset($item['money'])) $item['money'] = 0;
                $item['count']=0;
                $item['label'] = (isset($item['label']) && $item['label'] && !is_array($item['label'])) ? json_decode($item['label']) : [];
                $item['special_type_name'] = "直播课程";
                if (isset($item['lecturer_id']) && $item['lecturer_id']>0){
                    $item['lecturer'] = Lecturer::where('id',$item['lecturer_id'])->field('lecturer_head,lecturer_name')->find();
                }else{
                    $item['lecturer'] =[];
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
                if ($item['start_play_time']) {
                    $item['start_play_time'] = date('m-d H:i', strtotime($item['start_play_time']));
                }
                $count=Special::learning_records($item['id']);
                $item['browse_count']=processingData(bcadd($item['fake_sales'],$count,0));
            }else if($type==2){
                $item['label'] = (isset($item['label']) && $item['label'] && !is_array($item['label'])) ? json_decode($item['label']) : [];
            }else if($type==3){
                $item['special_type_name']='资料';
                $item['count']=bcadd($item['sales'],$item['ficti'],0);
            }else if($type==4){
                $item['special_type_name']='新闻';
                $item['add_time']=date('Y-m-d',$item['add_time']);
                $item['label'] = (isset($item['label']) && $item['label'] && !is_array($item['label'])) ? json_decode($item['label']) : [];
            }
        }
        return $list;
    }

    /**
     * 获取主页推荐下图文或者课程的总条数
     * @param int $recommend_id 推荐id
     * @param int $type 类型
     * @return int
     * */
    public static function getRelationCount($recommend_id, $type)
    {
        if ($type == 0){
            $count = self::where('a.recommend_id', $recommend_id)->alias('a')->join("__SPECIAL__ p", 'p.id=a.link_id')
                ->join('__SPECIAL_SUBJECT__ j', 'j.id=p.subject_id', 'LEFT')->where(['p.is_show' => 1,'p.is_del' => 0])->count();
        }else if($type == 1){
            $count = self::where('a.recommend_id', $recommend_id)->alias('a')->join("__SPECIAL__ p", 'p.id=a.link_id')
                ->join('__SPECIAL_SUBJECT__ j', 'j.id=p.subject_id', 'LEFT')->where(['p.is_show' => 1,'p.type' => 4,'p.is_del' => 0])->count();
        }else if($type == 2){
            $count = self::where('a.recommend_id', $recommend_id)->alias('a')->join("Lecturer l", 'l.id=a.link_id')
                ->where(['l.is_del' => 0,'l.is_show' => 1,])->count();
        }else if($type == 3){
            $count = self::where('a.recommend_id', $recommend_id)->alias('a')->join("DataDownload d", 'd.id=a.link_id')
                ->where(['d.is_show' => 1,'d.is_del' => 0])->count();
        }else if($type == 4){
            $count = self::where('a.recommend_id', $recommend_id)->alias('a')->join("Article d", 'd.id=a.link_id')
                ->where(['d.is_show' => 1,'d.hide' => 0])->count();
        }else if($type == 5){
            $count=8;
        }else{
            $count=0;
        }
        return $count;
    }
    /**
     *更多推荐
     * @param $where
     * @return array
     */
    public static function getUnifiendList($where,$is_member)
    {
        $ids = self::where(['type' => $where['type'], 'recommend_id' => $where['recommend_id']])->column('link_id');
        switch ((int)$where['type']) {
            case 0:
                $model = Special::set_where_pro($is_member,0);
                $field = ['title', 'abstract','is_light','light_type','is_mer_visible', 'image','type','label', 'pay_type','money', 'id', 'is_pink','fake_sales', 'pink_money'];
                $list = $model->where('id', 'in', $ids)->page($where['page'],$where['limit'])->order('sort desc,id desc')->field($field)->select();
                $count = Special::set_where_pro($is_member,0)->where('id', 'in', $ids)->count();
                break;
            case 1:
                $model = Special::set_where_pro($is_member,4);
                $field = ['title', 'abstract','is_light','light_type','is_mer_visible', 'image','type','label', 'money', 'id', 'is_pink', 'pink_money'];
                $list = $model->where('id', 'in', $ids)->page($where['page'],$where['limit'])->order('sort desc,id desc')->field($field)->select();
                $count = Special::set_where_pro($is_member,4)->where('id', 'in', $ids)->count();
                break;
            case 2:
                $model = Lecturer::where(['is_del'=>0,'is_show'=>1]);
                $field = ['id,lecturer_name,lecturer_head,label,curriculum,explain,study,sort,is_show,is_del'];
                $list = $model->where('id', 'in', $ids)->page($where['page'],$where['limit'])->order('sort desc,id desc')->field($field)->select();
                $count = Lecturer::where(['is_del'=>0,'is_show'=>1])->where('id', 'in', $ids)->count();
                break;
            case 3:
                $model = DataDownload::PreWhere();
                $field = ['id,title,image,pay_type,money,abstract,sort,ficti,sales,is_show,is_del'];
                $list = $model->where('id', 'in', $ids)->page($where['page'],$where['limit'])->order('sort desc,id desc')->field($field)->select();
                $count = DataDownload::PreWhere()->where('id', 'in', $ids)->count();
                break;
            default:
                return ['list' => [],'count' =>0];
                break;
        }
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as &$item) {
            if (!isset($item['money'])) $item['money'] = 0;
            $item['money'] = (float)$item['money'];
            $item['count'] =0;
            if($where['type']==0){
                $item['count']=Special::numberChapters($item['type'],$item['id']);
                $countOne=Special::learning_records($item['id']);
                $item['browse_count']=processingData(bcadd($countOne,$item['fake_sales'],0));
                if($item['is_light']){
                    $item['type']=Special::lightType($item['light_type']);
                }
            }
        }
        return compact('list', 'count');
    }

}
