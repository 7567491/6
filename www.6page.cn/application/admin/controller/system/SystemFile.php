<?php


namespace app\admin\controller\system;

use app\admin\model\system\SystemFile as SystemFileModel;
use app\admin\controller\AuthController;
use service\FormBuilder as Form;
use service\JsonService as Json;
use think\Db;
use think\Request;

/**
 * 文件校验控制器
 * Class SystemFile
 * @package app\admin\controller\system
 *
 */
class SystemFile extends AuthController
{
    public function index()
    {
        $app = $this->getDir('./application');
        $extend = $this->getDir('./extend');
        $public = $this->getDir('./public');
        $arr = array();
        $arr = array_merge($app, $extend);
        $arr = array_merge($arr, $public);
        $fileAll = array();//本地文件
        $cha = array();//不同的文件
        foreach ($arr as $k => $v) {
            $fp = fopen($v, 'r');
            if (filesize($v)) $ct = fread($fp, filesize($v));
            else $ct = null;
            fclose($fp);
            $cthash = md5($ct);
            $update_time = stat($v);
            $fileAll[$k]['cthash'] = $cthash;
            $fileAll[$k]['filename'] = $v;
            $fileAll[$k]['atime'] = $update_time['atime'];
            $fileAll[$k]['mtime'] = $update_time['mtime'];
            $fileAll[$k]['ctime'] = $update_time['ctime'];
        }
        $file = SystemFileModel::all(function ($query) {
            $query->order('atime', 'desc');
        })->toArray();//数据库中的文件
        if (empty($file)) {
            $data_num = array_chunk($fileAll, 10);
            SystemFileModel::beginTrans();
            $res = true;
            foreach ($data_num as $k => $v) {
                $res = $res && SystemFileModel::insertAll($v);
            }
            SystemFileModel::checkTrans($res);
            if ($res) {
                $cha = array();//不同的文件
            } else {
                $cha = $fileAll;
            }
        } else {
            $cha = array();//差异文件
            foreach ($file as $k => $v) {
                foreach ($fileAll as $ko => $vo) {
                    if ($v['filename'] == $vo['filename']) {
                        if ($v['cthash'] != $vo['cthash']) {
                            $cha[$k]['filename'] = $v['filename'];
                            $cha[$k]['cthash'] = $v['cthash'];
                            $cha[$k]['atime'] = $v['atime'];
                            $cha[$k]['mtime'] = $v['mtime'];
                            $cha[$k]['ctime'] = $v['ctime'];
                            $cha[$k]['type'] = '已修改';
                        }
                        unset($fileAll[$ko]);
                        unset($file[$k]);
                    }
                }

            }
            foreach ($file as $k => $v) {
                $cha[$k]['filename'] = $v['filename'];
                $cha[$k]['cthash'] = $v['cthash'];
                $cha[$k]['atime'] = $v['atime'];
                $cha[$k]['mtime'] = $v['mtime'];
                $cha[$k]['ctime'] = $v['ctime'];
                $cha[$k]['type'] = '已删除';
            }
            foreach ($fileAll as $k => $v) {
                $cha[$k]['filename'] = $v['filename'];
                $cha[$k]['cthash'] = $v['cthash'];
                $cha[$k]['atime'] = $v['atime'];
                $cha[$k]['mtime'] = $v['mtime'];
                $cha[$k]['ctime'] = $v['ctime'];
                $cha[$k]['type'] = '新增的';
            }

        }
        $this->assign('cha', $cha);
        return $this->fetch();
    }

    public function filelist()
    {
        $app = $this->getDir('./application');
        print_r($app);
        $extend = $this->getDir('./extend');
        $public = $this->getDir('./public');
        $arr = array();
        $arr = array_merge($app, $extend);
        $arr = array_merge($arr, $public);
        $fileAll = array();//本地文件
        foreach ($arr as $k => $v) {
            $fp = fopen($v, 'r');
            if (filesize($v)) $ct = fread($fp, filesize($v));
            else $ct = null;
            fclose($fp);
            $cthash = md5($ct);
            $update_time = stat($v);
            $fileAll[$k]['cthash'] = $cthash;
            $fileAll[$k]['filename'] = $v;
            $fileAll[$k]['atime'] = $update_time['atime'];
            $fileAll[$k]['mtime'] = $update_time['mtime'];
            $fileAll[$k]['ctime'] = $update_time['ctime'];
        }
        dump($fileAll);
    }

    /**
     * 获取文件夹中的文件 不包括子文件
     * @param $dir
     * @return array
     */
    public function getNextDir()
    {
        $dir = './';
        $list = scandir($dir);
        $dirlist = array();
        $filelist = array();
        foreach ($list as $key => $v) {
            if ($v != '.' && $v != '..') {
                if (is_dir($dir . '/' . $v)) {
                    $dirlist[$key]['name'] = $v;
                    $dirlist[$key]['type'] = 'dir';
                }
                if (is_file($dir . '/' . $v)) {
                    $filelist[$key]['name'] = $v;
                    $filelist[$key]['type'] = 'file';
                }
            }
        }
        $filesarr = array_merge($dirlist, $filelist);
        print_r($filesarr);
    }

    /**
     * 获取文件夹中的文件 包括子文件 不能直接用  直接使用  $this->getDir()方法 P156
     * @param $path
     * @param $data
     */
    public function searchDir($path, &$data)
    {
        if (is_dir($path) && !strpos($path, 'uploads')) {
            $dp = dir($path);
            while ($file = $dp->read()) {
                if ($file != '.' && $file != '..') {
                    $this->searchDir($path . '/' . $file, $data);
                }
            }
            $dp->close();
        }
        if (is_file($path)) {
            $data[] = $path;
        }
    }

    /**
     * 获取文件夹中的文件 包括子文件
     * @param $dir
     * @return array
     */
    public function getDir($dir)
    {
        $data = array();
        $this->searchDir($dir, $data);
        return $data;
    }

    //测试
    public function ceshi()
    {
        //创建form
        $form = Form::create('/save.php', [
            Form::input('goods_name', '商品名称')
            , Form::input('goods_name1', 'password')->type('password')
            , Form::input('goods_name2', 'textarea')->type('textarea')
            , Form::input('goods_name3', 'email')->type('email')
            , Form::input('goods_name4', 'date')->type('date')
            , Form::city('address', 'cityArea',
                '陕西省', '西安市'
            )
            , Form::dateRange('limit_time', 'dateRange',
                strtotime('- 10 day'),
                time()
            )
            , Form::dateTime('add_time', 'dateTime')
            , Form::color('color', 'color', '#ff0000')
            , Form::checkbox('checkbox', 'checkbox', [1])->options([['value' => 1, 'label' => '白色'], ['value' => 2, 'label' => '红色'], ['value' => 31, 'label' => '黑色']])
            , Form::date('riqi', 'date', '2018-03-1')
            , Form::dateTimeRange('dateTimeRange', '区间时间段')
            , Form::year('year', 'year')
            , Form::month('month', 'month')
            , Form::frame('frame', 'frame', '/admin/system.system_attachment/index.html?fodder=frame')
            , Form::frameInputs('frameInputs', 'frameInputs', '/admin/system.system_attachment/index.html?fodder=frameInputs')
            , Form::frameFiles('month1', 'frameFiles', '/admin/system.system_attachment/index.html?fodder=month1')
            , Form::frameImages('fodder1', 'frameImages', '/admin/system.system_attachment/index.html?fodder=fodder1')->maxLength(3)->width('800px')->height('400px')
            , Form::frameImages('fodder11', 'frameImages', '/admin/system.system_attachment/index.html?fodder=fodder11')->icon('images')
            , Form::frameInputOne('month3', 'frameInputOne', '/admin/system.system_attachment/index.html?fodder=month3')->icon('ionic')
            , Form::frameFileOne('month4', 'frameFileOne', '/admin/system.system_attachment/index.html?fodder=month4')
            , Form::frameImageOne('month5', 'frameImageOne', '/admin/system.system_attachment/index.html?fodder=month5')->icon('image')
            , Form::hidden('month6', 'hidden')
            , Form::number('month7', 'number')
//            ,Form::input input输入框,其他type: text类型Form::text,password类型Form::password,textarea类型Form::textarea,url类型Form::url,email类型Form::email,date类型Form::idate
            , Form::radio('month8', 'radio')->options([['value' => 1, 'label' => '白色'], ['value' => 2, 'label' => '红色'], ['value' => 31, 'label' => '黑色']])
            , Form::rate('month9', 'rate')
            , Form::select('month10', 'select')->options([['value' => 1, 'label' => '白色'], ['value' => 2, 'label' => '红色'], ['value' => 31, 'label' => '黑色']])
            , Form::selectMultiple('month11', 'selectMultiple')
            , Form::selectOne('month12', 'selectOne')
            , Form::slider('month13', 'slider', 2)
            , Form::sliderRange('month23', 'sliderRange', 2, 13)
            , Form::switches('month14', '区间时间段')
            , Form::timePicker('month15', '区间时间段')
            , Form::time('month16', '区间时间段')
            , Form::timeRange('month17', '区间时间段')
//            ,Form::upload('month','区间时间段')
//            ,Form::uploadImages('month','区间时间段')
//            ,Form::uploadFiles('month','区间时间段')
//            ,Form::uploadImageOne('month','区间时间段')
//            ,Form::uploadFileOne('month','区间时间段')

        ]);
        $html = $form->setMethod('get')->setTitle('编辑商品')->view();
        echo $html;
    }

    function changedomain(Request $request = null)
    {
        $old_domain = $request->param('old_domain');
        $new_domain = $request->param('new_domain');
        if (!$old_domain || !$new_domain) {
            return Json::fail('请先输入新老域名');
        }
        $dbPrefix = config('database.prefix');
        Db::execute('update `' . $dbPrefix . 'article` set `image_input`=replace(`image_input`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'article_content` set `content`=replace(`content`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'certificate` set `background`=replace(`background`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'certificate` set `qr_code`=replace(`qr_code`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'data_download` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'data_download` set `poster_image`=replace(`poster_image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'data_download` set `abstract`=replace(`abstract`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'event_registration` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'event_registration` set `activity_rules`=replace(`activity_rules`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'event_registration` set `content`=replace(`content`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'event_registration` set `qrcode_img`=replace(`qrcode_img`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'event_sign_up` set `write_off_code`=replace(`write_off_code`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'lecturer` set `lecturer_head`=replace(`lecturer_head`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'lecturer` set `introduction`=replace(`introduction`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'live_barrage` set `barrage`=replace(`barrage`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'live_gift` set `live_gift_show_img`=replace(`live_gift_show_img`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'live_studio` set `live_image`=replace(`live_image`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'member_ship` set `img`=replace(`img`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'phone_user` set `avatar`=replace(`avatar`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'questions` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'questions` set `analysis`=replace(`analysis`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'recommend` set `icon`=replace(`icon`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'mp_recommend` set `icon`=replace(`icon`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'recommend_banner` set `pic`=replace(`pic`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'reply_false` set `avatar`=replace(`avatar`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'sign_poster` set `poster`=replace(`poster`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'special` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special` set `abstract`=replace(`abstract`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special` set `poster_image`=replace(`poster_image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special` set `service_code`=replace(`service_code`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_barrage` set `avatar`=replace(`avatar`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_content` set `content`=replace(`content`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_subject` set `pic`=replace(`pic`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_task` set `content`=replace(`content`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_task` set `detail`=replace(`detail`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_task` set `link`=replace(`link`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_task` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'special_task` set `try_content`=replace(`try_content`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'store_bargain` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'store_bargain` set `images`=replace(`images`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'store_bargain` set `description`=replace(`description`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'store_combination` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'store_combination` set `images`=replace(`images`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'store_combination` set `info`=replace(`info`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'store_combination` set `description`=replace(`description`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'store_pink_false` set `avatar`=replace(`avatar`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'store_product` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'store_product` set `description`=replace(`description`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'store_service` set `avatar`=replace(`avatar`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'student` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'teacher` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'test_paper` set `image`=replace(`image`,"'. $old_domain .'","'. $new_domain .'")');

        Db::execute('update `' . $dbPrefix . 'user` set `avatar`=replace(`avatar`,"'. $old_domain .'","'. $new_domain .'")');
        Db::execute('update `' . $dbPrefix . 'wechat_user` set `headimgurl`=replace(`headimgurl`,"'. $old_domain .'","'. $new_domain .'")');

        // 处理转义
        $pattern = '/^(http:\/\/|https:\/\/)/';
        $old_domain2 = preg_replace($pattern, '', $old_domain);
        $new_domain2 = preg_replace($pattern, '', $new_domain);

        if (strpos($old_domain, "https://") !== false) {
            $old_domain2 = 'https:\\\\/\\\\/' . $old_domain2;
        } else {
            $old_domain2 = 'http:\\\\/\\\\/' . $old_domain2;
        }

        if (strpos($new_domain, "https://") !== false) {
            $new_domain2 = 'https:\\\\/\\\\/' . $new_domain2;
        } else {
            $new_domain2 = 'http:\\\\/\\\\/' . $new_domain2;
        }
        Db::execute('update `' . $dbPrefix . 'special` set `banner`=replace(`banner`,"'. $old_domain2 .'","'. $new_domain2 .'")');
        Db::execute('update `' . $dbPrefix . 'store_product` set `slider_image`=replace(`slider_image`,"'. $old_domain2 .'","'. $new_domain2 .'")');
        Db::execute('update `' . $dbPrefix . 'questions` set `option`=replace(`option`,"'. $old_domain2 .'","'. $new_domain2 .'")');
        Db::execute('update `' . $dbPrefix . 'system_config` set `value`=replace(`value`,"'. $old_domain2 .'","'. $new_domain2 .'")');
        Db::execute('update `' . $dbPrefix . 'system_group_data` set `value`=replace(`value`,"'. $old_domain2 .'","'. $new_domain2 .'")');
        // 莫名其妙出现http://
        Db::execute('update `' . $dbPrefix . 'system_group_data` set `value`=replace(`value`,"'. $old_domain .'","'. $new_domain2 .'")');
        return Json::successful('处理成功');
    }
}
