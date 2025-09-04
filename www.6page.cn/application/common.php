<?php



// 应用公共文件
use service\SystemConfigService;
use service\HttpService;
use think\cache\driver\Redis;
use think\Config;

define('IMG_DOMAIN', Config::get('img_domain'));
/**
 * 敏感词过滤
 *
 * @param  string
 * @return string
 */
function sensitive_words_filter($str)
{
    header('content-type:text/html;charset=utf-8');
    if (!$str) return '';
    $file = ROOT_PATH . 'public/static/plug/censorwords/CensorWords';
    $words = file($file);
    foreach ($words as $word) {
        $word = str_replace(array("\r\n", "\r", "\n", " "), '', $word);
        if (!$word) continue;
        $ret = @preg_match("/$word/", $str, $match);
        if ($ret) {
            return $match[0];
        }
    }
    return '';
}

function getController()
{
    return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', think\Request::instance()->controller()));
}

function getModule()
{
    return strtolower(preg_replace('/((?<=[a-z])(?=[A-Z]))/', '_', think\Request::instance()->module()));
}

function processingData($browse_count)
{
    if($browse_count > 9999){
        $browse_count =bcdiv($browse_count,10000,1).'W';
    }
    return $browse_count;
}

/**
 * 获取图片库链接地址
 * @param $key
 * @return string
 */
function get_image_Url($key)
{
    return think\Url::build('admin/widget.images/index', ['fodder' => $key]);
}


/**
 * 获取链接对应的key
 * @param $value
 * @param bool $returnType
 * @param string $rep
 * @return array|string
 * @throws \think\db\exception\DataNotFoundException
 * @throws \think\db\exception\ModelNotFoundException
 * @throws \think\exception\DbException
 */
function get_key_attr($value, $returnType = true, $rep = '')
{
    if (!$value) return '';
    $inif = \app\admin\model\system\SystemAttachment::where('att_dir', $value)->find();
    if ($inif) {
        return [
            'key' => $inif->name,
            'pic' => $value,
        ];
    } else {
        if ($returnType) {
            return [
                'key' => '',
                'pic' => $value,
            ];
        } else {
            return [
                'key' => '',
                'pic' => '',
            ];
        }
    }
}

/**
 * 获取系统配置内容
 * @param $name
 * @param string $default
 * @return string
 */
function get_config_content($name, $default = '')
{
    try {
        return \app\admin\model\system\SystemConfigContent::getValue($name);
    } catch (\Throwable $e) {
        return $default;
    }
}

/**
 * 打印日志
 * @param $name
 * @param $data
 * @param int $type
 */
function live_log($name, $data, $type = 8)
{
    file_put_contents($name . '.txt', '[' . date('Y-m-d H:i:s', time()) . ']' . print_r($data, true) . "\r\n", $type);
}

/**获取当前登录用户的角色信息
 * @return mixed
 */
function get_login_role() {
    $role['role_id'] = \think\Session::get("adminInfo")['roles'];
    $role['role_sign'] = \think\Session::get("adminInfo")['role_sign'];
    return $role;
}

/**获取登录用户账户信息
 * @return mixed
 */
function get_login_id() {
    $admin['admin_id'] = \think\Session::get("adminId");
    return $admin;
}


function money_rate_num($money, $type) {
    if (!$money) $money = 0;
    if (!$type) return \service\JsonService::fail('非法参数2');
    switch ($type) {
        case "gold":
            $goldRate = \service\SystemConfigService::get("gold_rate");
                $num = bcmul($money,$goldRate,0);
            return $num;
        default:
            return \service\JsonService::fail('汇率类型缺失');

    }
}

function getUrlToDomain() {
    $site_url = \service\SystemConfigService::get('site_url');
    if($site_url=='') $site_url=$_SERVER['PHP_SELF'];
    $arr = parse_url($site_url);
    if (!isset($arr['host'])) $arr['host'] = $arr['path'];
    $array=explode('.',$arr['host']);
    return implode('_',$array);
}

if (!function_exists('filter_emoji')) {

    // 过滤掉emoji表情
    function filter_emoji($str)
    {
        preg_match_all('/[\x{4e00}-\x{9fff}\d\w\s[:punct:]]+/u',$str,$result);
        return join('',$result[0]);
    }
}

function lightTypeNmae($light_type)
{
    switch ($light_type) {
        case 1:
            $type='图文';
            break;
        case 2:
            $type='音频';
            break;
        case 3:
            $type='视频';
            break;
    }
    return $type;
}
//读取版本号
function getversion($default = '1.0.0'){
    try {
        $version = parse_ini_file(dirname(__DIR__).'/.version');
        return $version['version'] ?? $default;
    } catch (\Throwable $e) {
        return $default;
    }
}


// 获取凡星免流量系统url
function get_fxdisk_full_path($path){
    $fx_disk_domain = SystemConfigService::get('fx_disk_domain');
    $fx_disk_username = SystemConfigService::get('fx_disk_username');
    $fx_disk_password = SystemConfigService::get('fx_disk_password');
    if (!$fx_disk_domain || !$fx_disk_username || !$fx_disk_password) {
        return array('status' => false, 'msg' => '免流系统未配置');
    }
    $redisModel = new Redis();
    if ($redisModel->has('fx_disk_token')) {
        $token = $redisModel->get('fx_disk_token');
    } else {
        $auth = HttpService::postRequestJson($fx_disk_domain . '/api/auth/login', json_encode(array('username' => $fx_disk_username, 'password' => $fx_disk_password)));
        $authJson = json_decode($auth);
        if ($authJson->code != 200) {
            $redisModel->rm('fx_disk_token');
            return array('status' => false, 'msg' => '免流系统登录失败');
        }
        $token = $authJson->data->token;
        $tres = $redisModel->set('fx_disk_token', $token, 170000);
    }
    // 获取文件信息
    $postData = array(
        'password' => '',
        'path' => $path,
        'method' => 'video_preview'
    );
    $file = HttpService::postRequestJson($fx_disk_domain . '/api/fs/other', json_encode($postData), array('Authorization: ' . $token));
    $fileJson = json_decode($file);
    if ($fileJson->code != 200) {
        $redisModel->rm('fx_disk_token');
        return array('status' => false, 'msg' => $fileJson->message);
    }
    $live_transcoding_task_list = $fileJson->data->video_preview_play_info->live_transcoding_task_list;
    $res = [];
    foreach ($live_transcoding_task_list as $k => $v) {
        if (isset($v->url) && $v->url) {
            $res[$v->template_id] = $v->url;
        }
    }

    if (isset($res['FHD'])) {
        $path = json_encode(['FHD' => $res['FHD']]);
    } else if (isset($res['HD'])){
        $path = json_encode(['HD' => $res['HD']]);
    } else {
        $path = json_encode(['SD' => $res['SD']]);
    }


    return array('status' => true, 'msg' => '获取成功', 'path' => $path);

}

// 获取图片资源域名
function get_image_src($src){
    $img_domain = IMG_DOMAIN;
    if ($img_domain && strstr($src, 'http') === false) {
        return $img_domain . $src;
    } else {
        return $src;
    }
}

// 获取图片资源域名，小程序
function get_image_src_mp($src){
    if (strpos($src, 'http') === 0) {
        return $src;
    }
    $img_domain = IMG_DOMAIN;
    if ($img_domain) {
        return $img_domain . $src;
    }
    $site_url = SystemConfigService::get('site_url');
    return $site_url . $src;
}