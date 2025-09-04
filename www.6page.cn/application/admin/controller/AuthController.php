<?php


namespace app\admin\controller;

use app\admin\model\system\SystemAdmin;
use app\admin\model\system\SystemMenus;
use app\admin\model\system\SystemRole;
use basic\AuthBasic;
use basic\BaseCheck;
use behavior\system\SystemBehavior;
use service\HookService;
use think\Session;
use think\Url;
/**
 * 基类 所有控制器继承的类
 * Class AuthController
 * @package app\admin\controller
 */
class AuthController extends AuthBasic
{
    /**
     * 当前登陆管理员信息
     * @var
     */
    protected $adminInfo;

    /**
     * 当前登陆管理员ID
     * @var
     */
    protected $adminId;

    /**
     * 当前管理员权限
     * @var array
     */
    protected $auth = [];

    protected $bc;
    protected $skipLogController = ['index', 'common'];

    protected function _initialize()
    {
        parent::_initialize();
        if (!SystemAdmin::hasActiveAdmin()) return $this->redirect('Login/index');
        try {
            $adminInfo = SystemAdmin::activeAdminInfoOrFail();
        } catch (\Exception $e) {
            return $this->failed(SystemAdmin::getErrorInfo($e->getMessage()), Url::build('Login/index'));
        }
        $this->adminInfo = $adminInfo;
        $this->adminId = $adminInfo['id'];
        $this->getActiveAdminInfo();
        $this->auth = SystemAdmin::activeAdminAuthOrFail();
        $this->bc = new BaseCheck();
        $this->bc->checkKey();
        $this->adminInfo['level'] === 0 || $this->checkAuth();
        $this->assign('_admin', $this->adminInfo);
        $this->assign('m_home_url', url('/m'));
        $this->assign('fx_ver', $this->bc->options);
        HookService::listen('admin_visit', $this->adminInfo, 'system', false, SystemBehavior::class);
    }

    protected function checkAuth($action = null, $controller = null, $module = null, array $route = [])
    {
        static $allAuth = null;
        if ($allAuth === null) $allAuth = SystemRole::getAllAuth();
        if ($module === null) $module = $this->request->module();
        if ($controller === null) $controller = $this->request->controller();
        if ($action === null) $action = $this->request->action();
        if (!count($route)) $route = $this->request->route();
        if (in_array(strtolower($controller), $this->skipLogController, true)) return true;
        $nowAuthName = SystemMenus::getAuthName($action, $controller, $module, $route);
        $baseNowAuthName = SystemMenus::getAuthName($action, $controller, $module, []);
        if ((in_array($nowAuthName, $allAuth) && !in_array($nowAuthName, $this->auth)) || (in_array($baseNowAuthName, $allAuth) && !in_array($baseNowAuthName, $this->auth)))
            exit($this->authFail('没有权限访问!'));
        return true;
    }


    /**
     * 获得当前用户最新信息
     * @return SystemAdmin
     */
    protected function getActiveAdminInfo()
    {
        $adminId = $this->adminId;
        $adminInfo = SystemAdmin::getValidAdminInfoOrFail($adminId);
        if (!$adminInfo) $this->failed(SystemAdmin::getErrorInfo('请登陆!'));
        $this->adminInfo = $adminInfo;
        SystemAdmin::setLoginInfo($adminInfo->toArray());
        return $adminInfo;
    }
    static function getDataModification($model_type,$id,$field,$value)
    {
        $model_string = ucfirst($model_type);
        $model_path = 'app\\admin\\model\\' . $model_type . '\\' . $model_string;
        if ($model_type == 'product'){
            $model_path = 'app\\admin\\model\\store\\StoreProduct';
        }
        if ($model_type == 'store_cate'){
            $model_path = 'app\\admin\\model\\store\\StoreCategory';
        }
        if ($model_type == 'subject') {
            $model_path = 'app\\admin\\model\\special\\SpecialSubject';
        }
        if ($model_type == 'task_category') {
            $model_path = 'app\\admin\\model\\special\\SpecialTaskCategory';
        }
        if ($model_type == 'questions') {
            $model_path = 'app\\admin\\model\\questions\\Questions';
        }
        if ($model_type == 'categpry') {
            $model_path = 'app\\admin\\model\\questions\\QuestionsCategpry';
        }
        if ($model_type == 'test') {
            $model_path = 'app\\admin\\model\\questions\\TestPaper';
        }
        if ($model_type == 'test_paper') {
            $model_path = 'app\\admin\\model\\questions\\TestPaperCategory';
        }
        if ($model_type == 'task') {
            $model_path = 'app\\admin\\model\\special\\SpecialTask';
        }
        if ($model_type == 'certificate') {
            $model_path = 'app\\admin\\model\\questions\\Certificate';
        }
        if ($model_type == 'lecturer') {
            $model_path = 'app\\admin\\model\\special\\Lecturer';
        }
        if ($model_type == 'event') {
            $model_path = 'app\\admin\\model\\ump\\EventRegistration';
        }
        if ($model_type == 'member_card_batch') {
            $model_path = 'app\\admin\\model\\user\\MemberCardBatch';
        }
        if ($model_type == 'member_card') {
            $model_path = 'app\\admin\\model\\user\\MemberCard';
        }
        if ($model_type == 'play_back') {
            $model_path = 'app\\admin\\model\\live\\LivePlayback';
        }
        if ($model_type == 'record') {
            $model_path = 'app\\admin\\model\\questions\\CertificateRecord';
        }
        if ($model_type == 'classes') {
            $model_path = 'app\\admin\\model\\educational\\Classes';
        }
        if ($model_type == 'student') {
            $model_path = 'app\\admin\\model\\educational\\Student';
        }
        if ($model_type == 'teacher') {
            $model_path = 'app\\admin\\model\\educational\\Teacher';
        }
        if ($model_type == 'teacher_categpry') {
            $model_path = 'app\\admin\\model\\educational\\TeacherCategpry';
        }
        if ($model_type == 'studio') {
            $model_path = 'app\\admin\\model\\live\\LiveStudio';
        }
        if ($model_type == 'live_user') {
            $model_path = 'app\\admin\\model\\live\\LiveUser';
        }
        if ($model_type == 'live_gift') {
            $model_path = 'app\\admin\\model\\live\\LiveGift';
        }
        if ($model_type == 'ship') {
            $model_path = 'app\\admin\\model\\user\\MemberShip';
        }
        $model = model($model_path);
        $res =$model::where('id',$id)->update([$field=>$value]);
        return $res;
    }

    static function switch_model($model_type)
    {
        $model_path = '';
        if ($model_type == 'special'){
            $model_path = 'app\\admin\\model\\special\\Special';
        }
        if ($model_type == 'source') {
            $model_path = 'app\\admin\\model\\special\\SpecialSource';
        }
        if ($model_type == 'task') {
            $model_path = 'app\\admin\\model\\special\\SpecialTask';
        }
        return model($model_path);
    }


    /**
     * 权限错误提醒页面
     * @param string $msg
     * @param int $url
     */
    protected function authFail($msg = '哎呀…亲…您没有权限访问')
    {
        $this->assign(compact('msg'));
        exit($this->fetch('public/auth'));
    }
}
