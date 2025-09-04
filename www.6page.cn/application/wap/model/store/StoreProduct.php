<?php



namespace app\wap\model\store;


use app\admin\model\store\StoreProductAttrValue;
use app\wap\model\store\StoreCart;
use basic\ModelBasic;
use traits\ModelTrait;
use think\Db;

/**商品表
 * Class StoreProduct
 * @package app\wap\model\store
 */
class StoreProduct extends ModelBasic
{
    use  ModelTrait;

    protected function getSliderImageAttr($value)
    {
        return json_decode($value,true)?:[];
    }

    public static function getValidProduct($productId,$field = 'id,store_name,image,slider_image,store_info,keyword,ot_price,description,is_postage,give_gold_num,free_shipping,postage,price,vip_price,stock,IFNULL(sales,0) + IFNULL(ficti,0) as sales')
    {
        return self::where('is_del',0)->where('is_show',1)->where('id',$productId)->field($field)->find();
    }

    public static function validWhere()
    {
        return self::where('is_del',0)->where('is_show',1)->where('mer_id',0);
    }

    /**
     * 新品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getNewProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_new',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    /**
     * 热卖产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getHotProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_hot',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    /**
     * 精品产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBestProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_best',1)->where('is_del',0)->where('mer_id',0)
            ->where('stock','>',0)->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }


    /**
     * 优惠产品
     * @param string $field
     * @param int $limit
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getBenefitProduct($field = '*',$limit = 0)
    {
        $model = self::where('is_benefit',1)
            ->where('is_del',0)->where('mer_id',0)->where('stock','>',0)
            ->where('is_show',1)->field($field)
            ->order('sort DESC, id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function cateIdBySimilarityProduct($cateId,$field='*',$limit = 0)
    {
        $pid = StoreCategory::cateIdByPid($cateId)?:$cateId;
        $cateList = StoreCategory::pidByCategory($pid,'id') ?:[];
        $cid = [$pid];
        foreach ($cateList as $cate){
            $cid[] = $cate['id'];
        }
        $model = self::where('cate_id','IN',$cid)->where('is_show',1)->where('is_del',0)
            ->field($field)->order('sort DESC,id DESC');
        if($limit) $model->limit($limit);
        return $model->select();
    }

    public static function isValidProduct($productId)
    {
        return self::be(['id'=>$productId,'is_del'=>0,'is_show'=>1]) > 0;
    }

    /**获取商品库存
     * @param $productId
     * @param string $uniqueId
     * @return int
     */
    public static function getProductStock($productId,$uniqueId = '')
    {
        if(!$uniqueId) $uniqueId = '';
        return  (string)$uniqueId == '' ?
            self::where('id',$productId)->value('stock')?:0
            : StoreProductAttr::uniqueByStock($uniqueId);
    }

    public static function decProductStock($num,$productId,$unique = '')
    {
        if($unique){
            $res = false !== StoreProductAttrValue::decProductAttrStock($productId,$unique,$num);
            $res = $res && self::where('id',$productId)->setInc('sales',$num);
        }else{
            $res = false !== self::where('id',$productId)->dec('stock',$num)->inc('sales',$num)->update();
        }
        return $res;
    }
    /**
     * 获取单独分销设置
     */
    public static function getIndividualDistributionSettings($oid)
    {
        $product_id=Db::name('store_order_cart_info')->where('oid', $oid)->value('product_id');
        if($product_id){
            $data=self::where('id',$product_id)->field('is_alone,brokerage_ratio,brokerage_two')->find();
            if($data) return $data;
            else return [];
        }else{
            return [];
        }
    }
}
