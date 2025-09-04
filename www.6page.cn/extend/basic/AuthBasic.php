<?php
namespace basic;

use service\HttpService;
use think\Controller;
use think\Request;

//extension_loaded('swoole_loader') or die(' Loader ext not installed');

/**
 * Class AuthBasic
 * @package basic
 * @method tokenData() 获取token信息
 * @method user(string $key = null) 获取用户信息
 * @method uid() 获取用户uid
 * @method isAdminLogin() 后台登陆状态
 * @method adminId() 后台管理员id
 * @method adminInfo() 后台管理信息
 * @method kefuId() 客服id
 * @method kefuInfo() 客服信息
 */
class AuthBasic extends Controller
{
    private $SUCCESSFUL_DEFAULT_MSG = 'ok';

    private $FAIL_DEFAULT_MSG = 'no';

    protected function _initialize() {

    }

    public static function postMore($params,Request $request = null,$suffix = false)
    {
        if($request === null) $request = Request::instance();
        $p = [];
        $i = 0;
        foreach ($params as $param){
            if(!is_array($param)) {
                $p[$suffix == true ? $i++ : $param] = $request->post($param);
            }else{
                if(!isset($param[1])) $param[1] = null;
                if(!isset($param[2])) $param[2] = '';
                $name = is_array($param[1]) ? $param[0].'/a' : $param[0];
                $p[$suffix == true ? $i++ : (isset($param[3]) ? $param[3] : $param[0])] = $request->post($name,$param[1],$param[2]);
            }
        }
        return $p;
    }

    public static function getMore($params,Request $request=null,$suffix = false)
    {
        if($request === null) $request = Request::instance();
        $p = [];
        $i = 0;
        foreach ($params as $param){
            if(!is_array($param)) {
                $p[$suffix == true ? $i++ : $param] = $request->get($param);
            }else{
                if(!isset($param[1])) $param[1] = null;
                if(!isset($param[2])) $param[2] = '';
                $name = is_array($param[1]) ? $param[0].'/a' : $param[0];
                $p[$suffix == true ? $i++ : (isset($param[3]) ? $param[3] : $param[0])] = $request->get($name,$param[1],$param[2]);
            }
        }
        return $p;
    }

    /**
     * 映射课节类型
     * @return string
     */
    static function specialTaskType($type) {
        $map = array(
            '1' => '图文',
            '2' => '音频',
            '3' => '视频',
            '4' => '直播课',
            '5' => '套餐课',
            '6' => '精简课'
        );
        return $map[$type];
    }

    /**
     * 获取用户访问端
     * @return array|string|null
     */
    public function getFromType()
    {
        return $this->request->header('Form-type', '');
    }

    /**
     * 当前访问端
     * @param string $terminal
     * @return bool
     */
    public function isTerminal(string $terminal)
    {
        return strtolower($this->getFromType()) === $terminal;
    }

    /**
     * 是否是H5端
     * @return bool
     */
    public function isH5()
    {
        return $this->isTerminal('h5');
    }

    /**
     * 是否是微信端
     * @return bool
     */
    public function isWechat()
    {
        return $this->isTerminal('wechat');
    }

    /**
     * 是否是小程序端
     * @return bool
     */
    public function isRoutine()
    {
        return $this->isTerminal('routine');
    }

    /**
     * 是否是app端
     * @return bool
     */
    public function isApp()
    {
        return $this->isTerminal('app');
    }

    /**
     * 是否是app端
     * @return bool
     */
    public function isPc()
    {
        return $this->isTerminal('pc');
    }

    public function res($code, $msg='', $data=[], $count=0)
    {
        exit(json_encode(compact('code','msg','data','count')));
    }

    public function successful($msg = 'ok', $data=[], $status=200)
    {
        if(false == is_string($msg)){
            $data = $msg;
            $msg = $this->SUCCESSFUL_DEFAULT_MSG;
        }
        return $this->res($status, $msg, $data);
    }

    public function failed($msg, $data=[], $code = false)
    {
        if(true == is_array($msg)){
            $data = $msg;
            $msg = $this->FAIL_DEFAULT_MSG;
        }
        return $this->res($code ? $code : 400, $msg, $data);
    }

    static function curlGet($url, $data = array(), $header = false, $timeout = 10) {
        return HttpService::getRequest($url, $data, $header, $timeout);
    }

    static function curlPost($url, $data = array(), $header = false, $timeout = 10) {
        return HttpService::postRequestJson($url, $data, $header, $timeout);
    }
}
