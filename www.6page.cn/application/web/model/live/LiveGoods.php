<?php


namespace app\web\model\live;

use app\web\model\special\StoreOrder;
use basic\ModelBasic;
use service\SystemConfigService;
use traits\ModelTrait;
use app\web\model\user\User;
use app\web\model\special\Special;

/**直播带货
 * Class LiveGoods
 * @package app\web\model\live
 */
class LiveGoods extends ModelBasic
{

    use ModelTrait;


    public static function getLiveGoodsList($where,$is_member=0,$page = 0,$limit = 10)
    {
        $model = self::alias('g');
        $model = $model->where(['g.is_delete'=>0,'g.type'=>0]);
        if ($where['is_show'] != "" && isset($where['is_show'])){
            $model = $model->where('g.is_show',$where['is_show']);
        }
        if ($where['live_id'] != 0 && isset($where['live_id'])){
            $model = $model->where('g.live_id',$where['live_id']);
        }
        $model = $model->field('s.id,s.browse_count,s.image,s.money,s.is_mer_visible,s.label,s.is_pink,s.is_show,s.pink_end_time,s.title,s.is_light,s.member_pay_type,s.member_money,g.id as live_goods_id,g.special_id, g.sort as gsort, g.fake_sales as gfake_sales,g.type as gfake_type, g.is_show as gis_show, g.sales as gsales');
        $model = $model->join('Special s','g.special_id=s.id')->where(['s.is_del'=>0,'s.is_show'=>1]);
        if(!$is_member) $model = $model->where(['s.is_mer_visible' => 0]);
        $model = $model->order('g.sort desc');
        if($page && $limit){
            $list = $model->page((int)$page,(int)$limit)->select();
        }else{
            $list = $model->select();
        }
        $list = count($list) ? $list->toArray() : [];
        foreach ($list as $key=>&$item){
            $item['pink_end_time'] = $item['pink_end_time'] ? strtotime($item['pink_end_time']) : 0;
            $item['sales'] = StoreOrder::where(['paid' => 1, 'cart_id' => $item['id'], 'refund_status' => 0,'type'=>0])->count();
            //查看拼团状态,如果已结束关闭拼团
            if ($item['is_pink'] && $item['pink_end_time'] < time()) {
                self::update(['is_pink' => 0], ['id' => $item['live_goods_id']]);
                $item['is_pink'] = 0;
            }
        }
        $page++;
        return ['list'=>$list,'page'=> $page];
    }


}
