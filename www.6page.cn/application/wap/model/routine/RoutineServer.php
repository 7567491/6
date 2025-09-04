<?php


namespace  app\wap\model\routine;

use service\SystemConfigService;
use think\Db;
use basic\AuthBasic;

/**微信公众号 model
 * Class RoutineServer
 * @package app\wap\model\routine
 */
class RoutineServer extends AuthBasic{
    /**
     * 微信公众号
     * @param string $routineAppId
     * @param string $routineAppSecret
     * @return mixed
     */
    public static function getAccessToken(){
        $routineAppId = SystemConfigService::get('wechat_appid');
        $routineAppSecret = SystemConfigService::get('wechat_appsecret');
        $url  ="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$routineAppId."&secret=".$routineAppSecret;
        return json_decode(parent::curlGet($url),true);
    }

    /**
     * 获取access_token  数据库
     * @return mixed
     */
    public static function get_access_token(){
        $accessToken = Db::name('routine_access_token')->where('id',1)->find();
        if($accessToken && $accessToken['stop_time'] > time()) return $accessToken['access_token'];
        else{
            $accessToken = self::getAccessToken();
            if(isset($accessToken['access_token'])){
                $data['access_token'] = $accessToken['access_token'];
                $data['stop_time'] = bcadd($accessToken['expires_in'],time(),0);
                Db::name('routine_access_token')->where('id',1)->update($data);
            }
            return $accessToken['access_token'];
        }
    }
}
