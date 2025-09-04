<?php


namespace app\wap\model\user;

use service\CanvasService;
use think\Url;
use traits\ModelTrait;
use basic\ModelBasic;
use service\SystemConfigService;

/**签到海报
 * Class SignPoster
 * @package app\wap\model\user
 */
class SignPoster extends ModelBasic
{
    use ModelTrait;

    /**获取前端海报的数据
     * @param $uid
     * @return bool|mixed
     */
    public static function todaySignPoster($uid){
        $urls=SystemConfigService::get('site_url').'/';
        $url = $urls .'m/my-sign?spread_uid=' . $uid;
        $sign_info = self::todaySignInfo();
        if(!$sign_info['poster'] || !$sign_info) return false;
        $sign_info['url']=$url;
        return $sign_info;
    }

    /**获取设置海报
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function todaySignInfo(){
        $time=strtotime(date('Y-m-d',time()));
        $signPoster=self::order('sort DESC,id DESC')->select();
        $signPoster=count($signPoster)>0 ? $signPoster->toArray() :[];
        $poster=SystemConfigService::get('sign_default_poster');
        if(count($signPoster)>0){
            foreach ($signPoster as $key=>$value){
                if($value['sign_time']==$time){
                    $poster=$value['poster'];
                    $sign_talk=$value['sign_talk'];
                    break;
                }
            }
        }
        $sign_info['poster'] = isset($poster) ? $poster : '';
        $sign_info['sign_talk'] = isset($sign_talk) ? $sign_talk : '';
        return $sign_info;
    }

}
