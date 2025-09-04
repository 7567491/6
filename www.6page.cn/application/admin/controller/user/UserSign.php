<?php


namespace app\admin\controller\user;

use app\admin\model\user\UserSign as UserSignModel;
use app\admin\controller\AuthController;
use service\JsonService as Json;
use think\Url;
use think\Request;

/**用户签到
 * Class UserSign
 * @package app\admin\controller\user
 */
class UserSign extends AuthController
{
    public function index()
    {
        return $this->fetch();
    }

    public function getUserSignList()
    {
        $where = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['title', ''],
        ]);
        return Json::successlayui(UserSignModel::getUserSignList($where));
    }

}
