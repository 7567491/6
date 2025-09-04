<?php



namespace app\admin\model\system;

use traits\ModelTrait;
use basic\ModelBasic;

/**
 * 短信 model
 * Class SmsAccessToken
 * @package app\admin\model\system
 */
class SmsAccessToken extends ModelBasic
{
    use ModelTrait;

    /**添加短信token
     * @param $getToken
     * @return object
     */
    public static function smsTokenAdd($getToken)
    {
        $data=[
            'access_token'=>$getToken['access_token'],
            'stop_time'=>bcsub($getToken['expires_in'],300,0),
        ];
        return self::set($data);
    }

    public static function delToken($access_token)
    {
        return self::where('access_token','<>','')->delete();
    }

}
