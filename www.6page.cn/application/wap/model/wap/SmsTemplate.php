<?php


namespace  app\wap\model\wap;

use service\sms\storage\Sms;
use app\admin\model\system\SystemMessage;
use app\wap\model\user\User;
use service\SystemConfigService;

/**
 * 发送短信消息
 * Class SmsTemplate
 * @package app\wap\model\wap
 */
class SmsTemplate
{
    /**发送短信
     * @param $uid
     * @param $data
     * @param $template_const
     * @return bool
     * @throws \Exception
     */
    public static function sendSms($uid,$data,$template_const)
    {
        $sms_platform_selection=SystemConfigService::get('sms_platform_selection');
        if($sms_platform_selection==1) return true;
        $message=SystemMessage::getSystemMessage($template_const);
        if($message['is_sms'] && $message['temp_id']){
            $user=User::where('uid',$uid)->field('phone,nickname')->find();
            $phone=$user['phone'];
            if($template_const=='ORDER_POSTAGE_SUCCESS'){
                $data['nickname']=$user['nickname'];
            }elseif ($template_const=='ORDER_POSTAGE_SUCCESS' || $template_const=='ORDER_TAKE_SUCCESS'){
                if(isset($data['phone']) && $data['phone']!='') {
                    $phone=$data['phone'];
                    unset($data['phone']);
                }
            }
            $smsHandle = new Sms();
            $res=$smsHandle->send($phone,$message['temp_id'],$data);
            if($res['Code']=='OK'){
                return true;
            } else {
                return false;
            }
        }else{
            return true;
        }
    }
}
