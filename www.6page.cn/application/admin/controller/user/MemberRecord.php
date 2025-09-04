<?php


namespace app\admin\controller\user;

use app\admin\controller\AuthController;
use service\JsonService as Json;
use service\FormBuilder as Form;
use think\Url;
use app\admin\model\user\MemberRecord as MemberRecordModel;

/**会员获取记录
 * Class MemberRecord
 * @package app\admin\controller\user
 */
class MemberRecord extends AuthController
{
    public function index()
    {
        return $this->fetch();
    }

    /**
     * 会员获取记录列表
     */
    public function member_record_list()
    {
        $where = parent::getMore([
            ['page', 1],
            ['limit', 20],
            ['title', ''],
            ['type', ''],
        ]);
        return Json::successlayui(MemberRecordModel::getPurchaseRecordList($where));
    }

}
