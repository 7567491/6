<?php



namespace app\wap\controller;

use app\wap\model\store\StoreService;
use service\SystemConfigService;
use app\wap\model\user\User;
use service\JsonService;
use service\UtilService;
use think\Request;

/**客服控制器
 * Class Service
 * @package app\wap\controller
 */
class Service extends AuthController
{
    /**微信客服列表
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service_list()
    {
        $customer_service_configuration=SystemConfigService::get('customer_service_configuration');
        $service_url=SystemConfigService::get('service_url');
        $this->assign([
            'service_configuration'=>$customer_service_configuration,
            'service_url'=>$service_url,
            'userInfo'=>json_encode($this->userInfo),
            'kefu_token'=>SystemConfigService::get('kefu_token'),
            'kefu_interval'=>SystemConfigService::get('kefu_interval'),
        ]);
        return $this->fetch();
    }

    /**
     * 获取微信客服
     */
    public function get_service_list()
    {
        $where = UtilService::getMore([
            ['page', 1],
            ['limit', 10]
        ]);
        $list = StoreService::field('uid,avatar,nickname')->where('status',1)->page($where['page'],$where['limit'])->order('id desc')->select();
        $list=count($list) > 0 ? $list->toArray() : [];
        return JsonService::successful($list);
    }

    /**
     * fanstar客服token
     */
    public function get_kefu_token()
    {
        $kefu_token=SystemConfigService::get('kefu_token');
        $data['kefu_token']=$kefu_token;
        return JsonService::successful($data);
    }

    /**聊天
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function service_ing(Request $request)
    {
        $params = Request::instance()->param();
        $to_uid = $params['to_uid'];
        if(!isset($to_uid) || empty($to_uid))$this->failed('未获取到接收用户信息！');
        if($this->uid == $to_uid)$this->failed('您不能进行自言自语！');

        //发送用户信息
        $now_user = StoreService::where(['uid'=>$this->uid])->find();
        if(!$now_user)$now_user = $this->userInfo;
        $this->assign('user',$now_user);

        //接收用户信息
        $to_user = StoreService::where(['uid'=>$to_uid])->find();
        if(!$to_user)$to_user = User::getUserInfo($to_uid);
        $this->assign(['to_user'=>$to_user]);
        return $this->fetch();
    }

    /**聊天记录
     * @return mixed
     */
    public function service_new()
    {
        return $this->fetch();
    }
}
