<?php


namespace app\web\controller;

use service\JsonService;
use service\SystemConfigService;
use service\GroupDataService;
use app\web\model\user\Search;
use app\web\model\recommend\WebRecommend;

/**pc端公共接口
 * Class PublicApi
 * @package app\web\controller
 */
class PublicApi
{

    /**
     * pc公共数据
     */
    public function public_data()
    {
        $data['site_name'] = SystemConfigService::get('site_name');//网站名称
        $data['home_logo'] = SystemConfigService::get('home_pc_logo');//pc首页图标
        $data['site_phone'] = SystemConfigService::get('site_phone');//联系电话
        $data['company_address'] = SystemConfigService::get('company_address');//公司地址
        $data['pc_login_diagram'] = SystemConfigService::get('pc_login_diagram');//PC端登录图
        $data['pc_end_bottom_display']=GroupDataService::getData('pc_end_bottom_display',4);//pc端底部展示
        $customer_service = SystemConfigService::get('customer_service_configuration');//客服配置1=微信客服2=fanstar客服3=拨打电话
        $data['service_url']='';
        $data['kefu_token']='';
        if($customer_service==2){
            $data['service_url']=SystemConfigService::get('service_url');
            $data['kefu_token']=SystemConfigService::get('kefu_token');
        }
        $data['customer_service'] = $customer_service;//客服配置1=微信客服2=fanstar客服3=拨打电话
        $data['site_service_phone'] = SystemConfigService::get('site_service_phone');//客服电话
        $data['keep_on_record'] = SystemConfigService::get('keep_on_record');//网站备案信息
        $data['full_name_the_company'] = SystemConfigService::get('full_name_the_company');//公司全称
        return JsonService::successful($data);
    }
    /**网站统计
     * @return bool|mixed
     */
    public function get_website_statistics()
    {
        return  SystemConfigService::get('website_statistics');
    }
    /**
     * 获取热搜词
     */
    public function get_host_search()
    {
        return JsonService::successful(Search::getHostSearch());
    }

    /**用户协议
     * @return mixed
     */
    public function agree()
    {
        $data['title']=SystemConfigService::get('site_name') . '用户付费协议';
        $data['content']=get_config_content('user_agreement');
        return JsonService::successful($data);
    }

    /**获取pc端头部导航
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_home_navigation()
    {
        return JsonService::successful(WebRecommend::getWebRecommend());
    }

}
