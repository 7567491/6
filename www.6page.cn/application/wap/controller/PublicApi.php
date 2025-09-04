<?php


namespace app\wap\controller;

use service\JsonService;
use service\SystemConfigService;

class PublicApi
{

    public function wechat_media_id_by_image($mediaIds = '')
    {
        if (!$mediaIds) return JsonService::fail('参数错误');
        try {
            $mediaIds = explode(',', $mediaIds);
            $temporary = \service\WechatService::materialTemporaryService();
            $pathList = [];
            foreach ($mediaIds as $mediaId) {
                if (!$mediaId) continue;
                try {
                    $content = $temporary->getStream($mediaId);
                } catch (\Exception $e) {
                    continue;
                }
                $name = substr(md5($mediaId), 12, 20) . '.jpg';
                $res = \Api\AliyunOss::instance([
                    'AccessKey' => SystemConfigService::get('accessKeyId'),
                    'AccessKeySecret' => SystemConfigService::get('accessKeySecret'),
                    'OssEndpoint' => SystemConfigService::get('end_point'),
                    'OssBucket' => SystemConfigService::get('OssBucket'),
                    'uploadUrl' => SystemConfigService::get('uploadUrl'),
                ])->stream($content, $name);
                if ($res !== false) {
                    $pathList[] = $res['url'];
                }
            }
            return JsonService::successful($pathList);
        } catch (\Exception $e) {
            return JsonService::fail('上传失败', ['msg' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()]);
        }
    }

    /**网站统计
     * @return bool|mixed
     */
    public function get_website_statistics()
    {
        return SystemConfigService::get('website_statistics');
    }

    /**
     * 公用数据
     */
    public function public_data()
    {
        $customer_service = SystemConfigService::get('customer_service_configuration');//客服配置1=微信客服2=fanstar客服3=拨打电话
        $data['customer_service'] = $customer_service;//客服配置1=微信客服2=fanstar客服3=拨打电话
        $data['site_service_phone'] = SystemConfigService::get('site_service_phone');//客服电话
        return JsonService::successful($data);
    }
}
