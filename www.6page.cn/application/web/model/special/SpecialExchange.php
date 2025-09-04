<?php


namespace app\web\model\special;

use traits\ModelTrait;
use basic\ModelBasic;

/**课程兑换
 * Class SpecialExchange
 * @package app\web\model\special
 */
class SpecialExchange extends ModelBasic
{
    use ModelTrait;

    /**提交兑换
     * @param $uid
     * @param $special_id
     * @param $code
     */
    public static function userExchangeSubmit($uid,$special_id,$code)
    {
        if(!$uid) return self::setErrorInfo('参数错误!');
        $exchange=SpecialExchange::where(['exchange_code'=>$code,'special_id'=>$special_id])->find();
        if(!$exchange) return self::setErrorInfo('兑换码不存在或活动和兑换码不匹配!');
        if(!$exchange['status']) return self::setErrorInfo('兑换码已结束!');
        if($exchange['use_uid']>0 || $exchange['use_time']>0) return self::setErrorInfo('兑换码已兑换!');
        $batch=SpecialBatch::where('id',$exchange['card_batch_id'])->find();
        if(!$batch['status']) return self::setErrorInfo('活动已结束!');
        self::beginTrans();
        $res=self::edit(['use_uid'=>$uid,'use_time'=>time()],$exchange['id'],'id');
        if($res && $batch) {
            $res1=SpecialBatch::edit(['use_num'=>bcadd($batch['use_num'],1,0)],$exchange['card_batch_id'],'id');
            if(!$res1) {
                return self::setErrorInfo('数据修改有误!',true);
            }
            $special=Special::PreWhere()->where('id',$special_id)->field('id,is_light,type,money,pay_type,title')->find();
            if(!$special){
                return self::setErrorInfo('兑换码关联的课程不存在!',true);
            }
            if (in_array($special['money'], [0, 0.00]) || in_array($special['pay_type'], [PAY_NO_MONEY, PAY_PASSWORD])) {
                $isPay = 1;
            }else{
                $isPay = (!$uid || $uid == 0) ? false : SpecialBuy::PaySpecial($special_id, $uid);
            }
            if($isPay){
                return self::setErrorInfo('该课程是免费的或者已购买，无需兑换!',true);
            }
            if ($special['type'] == SPECIAL_COLUMN) {
                $special_source = SpecialSource::getSpecialSource($special['id']);
                if ($special_source){
                    foreach($special_source as $k => $v) {
                        $task_special = Special::get($v['source_id']);
                        if ($task_special['is_show'] == 1){
                            SpecialBuy::setBuySpecial('', $uid, $v['source_id'], 4,$special_id);
                        }
                    }
                }
                SpecialBuy::setBuySpecial('', $uid, $special_id, 4);
            }else{
                SpecialBuy::setBuySpecial('', $uid, $special_id, 4);
            }
            self::commitTrans();
            $data['id']=$special['id'];
            $data['is_light']=$special['is_light'];
            $data['title']=$special['title'];
            return $data;
        }else{
            return self::setErrorInfo('数据修改有误!',true);
        }
    }

}
