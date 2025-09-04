<?php


namespace app\web\model\user;

use basic\ModelBasic;
use service\SystemConfigService;
use service\WechatService;
use traits\ModelTrait;
use app\web\model\user\User;
use app\wap\model\user\WechatUser;
use service\AlipayTradeWapService;
use service\HookService;

/**虚拟币充值
 * Class UserRecharge
 * @package app\web\model\user
 */
class UserRecharge extends ModelBasic
{
    use ModelTrait;

    protected $insert = ['add_time'];

    protected function setAddTimeAttr()
    {
        return time();
    }

    /**充值订单
     * @param $uid
     * @param $price
     * @param string $recharge_type
     * @param int $paid
     * @return object
     */
    public static function addRecharge($uid,$price,$recharge_type = 'weixin',$paid = 0)
    {
        $goldNum = money_rate_num($price, 'gold');
        $orderInfo = [
            'uid' => $uid,
            'order_id' => self::getNewOrderId(),
            'price' => $price,
            'recharge_type' => $recharge_type,
            'paid' => $paid,
            'gold_num' => $goldNum
        ];
        return  self::set($orderInfo);
    }

    public static function getNewOrderId()
    {
        $count = (int) self::where('add_time',['>=',strtotime(date("Y-m-d"))],['<',strtotime(date("Y-m-d",strtotime('+1 day')))])->count();
        return 'wx1'.date('YmdHis',time()).(10000+$count+1);
    }

    /**微信js支付
     * @param $orderId
     * @param string $field
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function jsRechargePay($orderId, $field = 'order_id')
    {
        if (is_string($orderId))
            $orderInfo = self::where($field, $orderId)->find();
        else
            $orderInfo = $orderId;
        if (!$orderInfo || !isset($orderInfo['paid'])) exception('支付订单不存在!');
        if ($orderInfo['paid']) exception('支付已支付!');
        if ($orderInfo['price'] <= 0) exception('该订单无需支付!');
        $openid = WechatUser::uidToOpenid($orderInfo['uid']);
        return WechatService::jsPay($openid, $orderInfo['order_id'], $orderInfo['price'], 'recharge', SystemConfigService::get('site_name'));
    }
    /**
     * 微信扫码支付
     * @param $orderId
     * @param string $field
     * @return array|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function nativeRechargePay($orderId,$field = 'order_id')
    {
        if(is_string($orderId))
            $orderInfo = self::where($field,$orderId)->find();
        else
            $orderInfo = $orderId;
        if(!$orderInfo || !isset($orderInfo['paid'])) exception('支付订单不存在!');
        if($orderInfo['paid']) exception('支付已支付!');
        if($orderInfo['price'] <= 0) exception('该支付无需支付!');
        $site_name = SystemConfigService::get('site_name');
        if(!$site_name) exception('支付参数缺少：请前往后台设置->系统设置-> 填写 网站名称');
        return WechatService::nativePay(null,$orderInfo['order_id'],$orderInfo['price'],'recharge',self::getSubstrUTf8($site_name.'-金币充值',30),'','NATIVE');
    }

    /**虚拟币充值阿里云扫码支付
     * @param $orderId
     * @param string $field
     * @return mixed|\SimpleXMLElement|string|\提交表单HTML文本
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function alipayAativeRechargePay($orderId,$field = 'order_id')
    {
        if(is_string($orderId))
            $orderInfo = self::where($field,$orderId)->find();
        else
            $orderInfo = $orderId;
        if(!$orderInfo || !isset($orderInfo['paid'])) exception('支付订单不存在!');
        if($orderInfo['paid']) exception('支付已支付!');
        if($orderInfo['price'] <= 0) exception('该支付无需支付!');
        $goldName = SystemConfigService::get("gold_name");
        return AlipayTradeWapService::init()->AliPayNative($orderInfo['order_id'], $orderInfo['price'], $goldName."充值", 'recharge');

    }
    /**余额支付
     * @param $order_id
     * @param $user_info
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function yuePay($order_id, $user_info)
    {
        $uid = $user_info['uid'];
        $orderInfo = self::where('uid', $uid)->where('order_id', $order_id)->find();
        if (!$orderInfo) return self::setErrorInfo('订单不存在!');
        if ($orderInfo['paid']) return self::setErrorInfo('该订单已支付!');
        if ($orderInfo['recharge_type'] != 'yue') return self::setErrorInfo('该订单不能使用余额支付!');
        if ($user_info['now_money'] < $orderInfo['price'])
            return self::setErrorInfo('余额不足' . floatval($orderInfo['price']));
        self::beginTrans();
        $res1 = self::where('order_id',$order_id)->update(['paid'=>1,'pay_time'=>time()]);
        $goldNum = money_rate_num($orderInfo['price'], 'gold');
        $goldName = SystemConfigService::get("gold_name");
        $res2 = false !== UserBill::income('用户充值'.$goldName,$orderInfo['uid'],'gold_num','recharge',$goldNum,0,$user_info['gold_num'],'用户充值'.$orderInfo['price'].'元人民币获得'.$goldNum.'个'.$goldName);
        $res3 = User::bcInc($orderInfo['uid'],'gold_num',$goldNum,'uid');
        $res4 = User::bcDec($orderInfo['uid'],'now_money',$orderInfo['price'],'uid');
        $res5 = UserBill::expend('充值金币', $uid, 'now_money', 'recharge', $orderInfo['price'], $orderInfo['id'], $user_info['now_money'], '余额支付' . floatval($orderInfo['price']) . '元充值'.$goldName);
        try {
            $res = $res1 && $res2 && $res3 && $res4 && ($res5 ? true : false);
            self::checkTrans($res);
            return $res;
        } catch (\Exception $e) {
            self::rollbackTrans();
            return self::setErrorInfo($e->getMessage());
        }
    }
    /**
     * //TODO用户微信充值成功后
     * @param $orderId
     */
    public static function rechargeSuccess($orderId)
    {
        $order = self::where('order_id',$orderId)->where('paid',0)->find();
        if(!$order) return false;
        $user = User::getUserInfo($order['uid']);
        self::beginTrans();
        $res1 = self::where('order_id',$order['order_id'])->update(['paid'=>1,'pay_time'=>time()]);
        $goldNum = money_rate_num($order['price'], 'gold');
        $goldName = SystemConfigService::get("gold_name");
        $res4=true;
        if($res1 && $order['recharge_type']!='yue') {
            $res4 = UserBill::expend('充值金币', $order['uid'], $order['recharge_type'], 'recharge', $order['price'], $order['id'], 0, '支付' . floatval($order['price']) . '元充值' . $goldName);
        }
        $res2 = UserBill::income('用户充值'.$goldName,$order['uid'],'gold_num','recharge',$goldNum,0,$user['gold_num'],'用户充值'.$order['price'].'元人民币获得'.$goldNum.'个'.$goldName);
        $res3 = User::bcInc($order['uid'],'gold_num',$goldNum,'uid');
        $res = $res1 && $res2 && $res3 && $res4;
        self::checkTrans($res);
        return $res;
    }

}
