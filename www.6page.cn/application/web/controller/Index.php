<?php

namespace app\web\controller;

use app\web\model\recommend\WebRecommend;
use app\web\model\special\Special;
use app\web\model\material\DataDownload;
use app\web\model\special\Special as SpecialModel;
use app\web\model\topic\TestPaper;
use app\web\model\user\User;
use service\UtilService;
use service\GroupDataService;
use service\JsonService;
use service\SystemConfigService;
use service\UploadService as Upload;
use app\web\model\recommend\WebRecommendRelation;
use think\Url;
use think\Config;
use think\Exception;

/**首页控制器
 * Class Index
 * @package app\web\controller
 */
class Index extends AuthController
{

    /**
     * 白名单
     * */
    public static function WhiteList()
    {
        return [
            'index',
            'page_404',
            'page_error',
            'get_content_recommend',
            'user_login',
            'get_unifiend_list',
            'get_recommend_info',
            'more_list',
            'index_data',
            'payment',
            'about_us'
        ];
    }
    /**
     * 主页
     * @return mixed
     */
    public function index()
    {
        try {
            // 获取基础配置
            $homeData = $this->getHomeData();
            $this->assign('home_data', $homeData);
            
            // 获取推荐内容
            $recommendData = $this->getRecommendData();
            $this->assign('pc_recommend_list', $recommendData);
            
            // 获取自动推荐内容
            $this->setAutoRecommendData($recommendData);
            
            // 设置资源域名
            $this->setResourceDomain();
            
            return $this->fetch();
        } catch (Exception $e) {
            // 记录错误日志
            \think\Log::error('Index page error: ' . $e->getMessage());
            // 返回错误页面
            return $this->fetch('public/error');
        }
    }
    
    /**
     * 获取主页基础数据
     * @return array
     */
    private function getHomeData()
    {
        return [
            'pc_banner_list' => GroupDataService::getData('pc_rotation_diagram') ?: [],
            'pc_home_ad' => GroupDataService::getData('pc_home_page_advertisement', 4) ?: [],
            'pc_home_bottom_img' => SystemConfigService::get('pc_home_bottom_img') ?: '',
            'pc_home_bottom_figure_link' => SystemConfigService::get('pc_home_bottom_figure_link') ?: '',
            'pc_automatic_recommend' => SystemConfigService::get('pc_automatic_recommend') ?: '',
            'pc_news_recommend' => SystemConfigService::get('pc_news_recommend') ?: ''
        ];
    }
    
    /**
     * 获取推荐数据
     * @return array
     */
    private function getRecommendData()
    {
        $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        $recommend_list = WebRecommend::getContentRecommend($is_member);
        return $recommend_list ?: ['recommend' => []];
    }
    
    /**
     * 设置自动推荐数据
     * @param array $recommendData
     */
    private function setAutoRecommendData($recommendData)
    {
        // 检查是否开启自动推荐
        $hasAutoRecommend = false;
        if (isset($recommendData['recommend'])) {
            foreach ($recommendData['recommend'] as $item) {
                if (isset($item['type']) && $item['type'] == 5) {
                    $hasAutoRecommend = true;
                    break;
                }
            }
        }
        
        if ($hasAutoRecommend) {
            $is_member = isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
            $this->assign([
                'browse_count_list' => SpecialModel::course_ranking_list($is_member, 'browse_count', 8) ?: [],
                'good_recommend_list' => SpecialModel::good_class_recommend_list($is_member, 8) ?: [],
                'newest_list' => SpecialModel::course_ranking_list($is_member, 'add_time', 8) ?: []
            ]);
        }
    }
    
    /**
     * 设置资源域名
     */
    private function setResourceDomain()
    {
        $img_domain = Config::get('img_domain');
        // 确保域名格式正确
        if ($img_domain === '/') {
            $img_domain = '';
        } elseif ($img_domain && !str_ends_with($img_domain, '/')) {
            $img_domain .= '/';
        }
        $this->assign('img_domain', $img_domain);
    }

    public function page_404()
    {
        return $this->fetch();
    }

    public function page_error($msg='')
    {
        $this->assign('msg', $msg);
        return $this->fetch();
    }

    /**
     * 首页固定数据
     */
    public function index_data()
    {
        $data['pc_rotation_diagram']=GroupDataService::getData('pc_rotation_diagram');//pc端首页轮播图
        $data['pc_home_page_advertisement']=GroupDataService::getData('pc_home_page_advertisement',4);//pc端首页广告
        $data['pc_home_bottom_img'] = SystemConfigService::get('pc_home_bottom_img');//pc端首页底部图
        $data['pc_home_bottom_figure_link'] = SystemConfigService::get('pc_home_bottom_figure_link');//pc端首页底部图链接
        $data['pc_automatic_recommend'] = SystemConfigService::get('pc_automatic_recommend');//pc端首页课程自动推荐模块
        $data['pc_news_recommend'] = SystemConfigService::get('pc_news_recommend');//pc端新闻推荐模块
        return JsonService::successful($data);
    }

    /**更多
     * @return mixed
     */
    public function more_list()
    {
        return $this->fetch();
    }
    /**
     * 首页推荐更多
     * */
    public function get_unifiend_list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 10],
            ['recommend_id', 0],
            ['type', 0]
        ]);
        $is_member=isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        return JsonService::successful(WebRecommendRelation::getUnifiendList($where,$is_member));
    }
    /**
     * 上传图片
     * @return \think\response\Json
     */
    public function upload($name = 'file', $link = 'master')
    {
        $res = Upload::image($name, $link);
        $thumbPath = Upload::thumb($res->dir);
        if ($res->status == 200)
            return JsonService::successful('图片上传成功!', ['name' => $res->fileInfo->getSaveName(), 'url' => Upload::pathToUrl($thumbPath)]);
        else
            return JsonService::fail($res->error);
    }

    /**
     * 获取手机号码登录状态
     * */
    public function user_login()
    {
        if($this->phone || $this->openid){
            return JsonService::successful('登录中');
        }else{
            return JsonService::fail('请先登录!');
        }
    }

    /**
     * 用户登录状态
     */
    public function login_user()
    {
        if ($this->uid)
            return JsonService::successful('登录中');
        else
            return JsonService::fail('请先登录!');
    }

    /**
     * 获取主页推荐列表
     * @param int $page
     * @param int $limit
     */
    public function get_content_recommend()
    {
        try {
            //获取推荐列表
            $exists_recommend_reids = $this->redisModel->HEXISTS($this->subjectUrl."web_index_has","recommend_list");
            if (!$exists_recommend_reids) {
                $is_member=isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
                $recommend_list = json_encode(WebRecommend::getContentRecommend($is_member));
                $this->redisModel->hset($this->subjectUrl."web_index_has","recommend_list", $recommend_list);
                $this->redisModel->expire($this->subjectUrl."web_index_has",120);
            }else{
                $recommend_list = $this->redisModel->hget($this->subjectUrl."web_index_has","recommend_list");
            }
            return JsonService::successful(json_decode($recommend_list,true));
        } catch (\Exception $e) {
            return JsonService::fail(parent::serRedisPwd($e->getMessage()));
        }
    }


    /**
     * @param int $recommend_id
     * @throws \think\exception\DbException
     */
    public function get_recommend_info($recommend_id = 0)
    {
        return JsonService::successful(WebRecommend::get($recommend_id));
    }

    /**课程/资料支付数据
     * @param int $id 课程、资料ID
     * @param int $type 1=课程 0=资料
     */
    public function pay_data($id=0,$type=0)
    {
        $is_member=isset($this->userInfo['level']) ? $this->userInfo['level'] : 0;
        if($type==1){
            // 课程
            $data=Special::where('id',$id)->field('title,is_light,money,member_money')->find();
        }else if($type==0){
            // 虚拟物品
            $data=DataDownload::where('id',$id)->field('title,money,member_money')->find();
        } else if ($type == 2) {
            // 考试
            $data=TestPaper::PreExercisesWhere()->field('title,money,member_money')->where('id',$id)->find();
        }
        $data['is_member']=$is_member;
        return JsonService::successful($data);
    }

    /**
     * 清空缓存
     */
    public function date_empty()
    {
        \think\Session::clear();
        \think\Cookie::clear();
    }

    /**
     * 付款页
     */
    public function payment($id=0,$type=0,$is_test=0)
    {
        $this->assign(['id'=>$id,'type'=>$type,'is_test'=>$is_test]);
        return $this->fetch();
    }

    /**
     * 关于我们
     * @return mixed
     */
    public function about_us()
    {
        $this->assign([
            'content' => get_config_content('about_us'),
            'title' => '关于我们'
        ]);
        return $this->fetch();
    }
    
    /**
     * 新版主页 - 用于测试资源加载
     * @return mixed
     */
    public function index_new()
    {
        try {
            // 获取基础配置
            $homeData = $this->getHomeData();
            $this->assign('home_data', $homeData);
            
            // 获取推荐内容
            $recommendData = $this->getRecommendData();
            $this->assign('pc_recommend_list', $recommendData);
            
            // 获取自动推荐内容
            $this->setAutoRecommendData($recommendData);
            
            // 设置资源域名
            $this->setResourceDomain();
            
            // 获取公共数据
            $this->assign('public_data', [
                'site_name' => SystemConfigService::get('site_name') ?: '教育平台',
                'site_logo' => SystemConfigService::get('site_logo') ?: '',
                'site_description' => SystemConfigService::get('site_description') ?: ''
            ]);
            
            // 设置SEO信息
            $this->assign('seo_keywords', SystemConfigService::get('seo_keywords') ?: '');
            $this->assign('seo_description', SystemConfigService::get('seo_description') ?: '');
            $this->assign('site_favicon', SystemConfigService::get('site_favicon') ?: '');
            
            return $this->fetch('index_new');
        } catch (Exception $e) {
            // 记录错误日志
            \think\Log::error('Index new page error: ' . $e->getMessage());
            // 返回错误信息
            return '<h1>页面加载出错</h1><p>错误信息：' . $e->getMessage() . '</p><p>请检查系统配置和数据库连接。</p>';
        }
    }
}
