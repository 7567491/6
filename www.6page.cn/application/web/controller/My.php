<?php


namespace app\web\controller;

use app\wap\model\user\SmsCode;
use app\wap\model\user\WechatUser;
use app\web\model\material\DataDownloadBuy;
use app\web\model\special\Special as SpecialModel;
use app\web\model\topic\CertificateRecord;
use app\web\model\topic\ExaminationWrongBank;
use app\web\model\topic\TestPaperObtain;
use app\web\model\topic\TestPaper;
use app\web\model\user\PhoneUser;
use app\web\model\user\User;
use app\web\model\user\UserBill;
use service\CacheService;
use service\GroupDataService;
use service\JsonService;
use service\SystemConfigService;
use service\UtilService;
use think\Cookie;
use think\Request;
use think\Session;
use think\Url;

/**my控制器
 * Class My
 * @package app\web\controller
 */
class My extends AuthController
{

    /**
     * 白名单
     * */
    public static function WhiteList()
    {
        return [
            'about_us',
        ];
    }

    /**
     * 个人中心
     * @return mixed
     * @throws \think\Exception
     */
    public function index()
    {
        $uid = $this->uid;
        $page = $this->request->get('page');
        // 获取个人中心配置
        $userCenterTop = SystemConfigService::get('pc_user_center_top');
        $userCenterMenus = SystemConfigService::get('pc_user_center_menus');
        $goldName = SystemConfigService::get('gold_name');

        $userCenterNumList = [];
        foreach ($userCenterTop as $userCenterTopItem) {
            if ($userCenterTopItem == "course") {
                // 我的课程数量
                $myCourseCount = SpecialModel::set_my_where($uid)->count();
                $userCenterNumList['我的课程'] = $myCourseCount;
            }
            if ($userCenterTopItem == "exam") {
                // 我的考试数量
                $myExamCount = TestPaperObtain::alias('b')->join('TestPaper t','b.test_id=t.id')
                    ->where(['b.type'=>2,'b.uid'=>$uid,'b.is_del'=>0,'t.is_del'=>0,'t.is_show'=>1])->count();
                $userCenterNumList['我的考试'] = $myExamCount;
            }
            if ($userCenterTopItem == "test") {
                // 我的测试数量
                $myTestCount = TestPaperObtain::alias('b')->join('TestPaper t','b.test_id=t.id')
                    ->where(['b.type'=>1,'b.uid'=>$uid,'b.is_del'=>0,'t.is_del'=>0,'t.is_show'=>1])->count();
                $userCenterNumList['我的测试'] = $myTestCount;
            }
            if ($userCenterTopItem == "wrong") {
                // 我的错题
                $myWrongCount = ExaminationWrongBank::alias('w')->join('Questions q','w.questions_id=q.id')
                    ->join('TestPaper t','w.test_id=t.id')
                    ->where(['w.uid'=>$uid,'q.is_del'=>0,'t.is_del'=>0,'t.is_show'=>1])->count();
                $userCenterNumList['我的错题'] = $myWrongCount;
            }
            if ($userCenterTopItem == "certificate") {
                // 我的证书
                $myCertificateCount = CertificateRecord::setWhere($uid)->count();
                $userCenterNumList['我的证书'] = $myCertificateCount;
            }
            if ($userCenterTopItem == "material") {
                // 我的虚拟资料数量
                $myVirtualCount = DataDownloadBuy::getDataDownloadWhere($uid)->count();
                $userCenterNumList['虚拟资料'] = $myVirtualCount;
            }
            if ($userCenterTopItem == "balance") {
                // 我的余额
                $myMoney = $this->userInfo['now_money'];
                $userCenterNumList['我的余额/元'] = $myMoney;
            }
            if ($userCenterTopItem == "coin") {
                // 我的金币
                $myCoin = $this->userInfo['gold_num'];
                $userCenterNumList['我的金币/个'] = $myCoin;
            }
        }
        // 生成个人中心菜单
        $allMenus = [
            'course' => [
                "name" => '我的课程',
                "value" => 'course',
                "icon" => 'reading'
            ],
            'exam' => [
                "name" => '我的考试',
                "value" => 'exam',
                "icon" => 'document'
            ],
            'test' => [
                "name" => '我的练习',
                "value" => 'test',
                "icon" => 'tickets'
            ],
            'wrong' => [
                "name" => '我的错题',
                "value" => 'wrong',
                "icon" => 'document-delete'
            ],
            'certificate' => [
                "name" => '我的证书',
                "value" => 'certificate',
                "icon" => 'medal'
            ],
            'material' => [
                "name" => '虚拟资料',
                "value" => 'material',
                "icon" => 'folder'
            ],
            'spread' => [
                "name" => '我的推广',
                "value" => 'spread',
                "icon" => 'money'
            ],
            'balance' => [
                "name" => '余额明细',
                "value" => 'balance',
                "icon" => 'bank-card'
            ],
            'coin' => [
                "name" => '我的' . $goldName,
                "value" => 'coin',
                "icon" => 'coin'
            ],
            'favor' => [
                "name" => '我的收藏',
                "value" => 'favor',
                "icon" => 'star-off'
            ],
            'member' => [
                "name" => '开通会员',
                "value" => 'member',
                "icon" => 'trophy'
            ],
            'account' => [
                "name" => '账户资料',
                "value" => 'account',
                "icon" => 'user'
            ]
        ];
        $userCenterMenusList = [
            [
                "name" => '我的课程',
                "value" => 'course',
                "icon" => 'reading'
            ]
        ];
        foreach ($userCenterMenus as $item) {
            $userCenterMenusList[] = $allMenus[$item];
        }
        $this->assign([
            'page' => $page,
            'userCenterNumList' => $userCenterNumList,
            'userCenterMenusList' => json_encode($userCenterMenusList)
        ]);
        return $this->fetch();
    }

    /**用户信息
     * @return mixed
     */
    public function user_info()
    {
        return $this->fetch();
    }

    /**
     * 验证手机号
     */
    public function validate_code()
    {
        list($phone, $code,) = UtilService::getMore([
            ['phone', ''],
            ['code', ''],
        ], $this->request, true);
        if (!$phone) return JsonService::fail('请输入手机号码');
        if (!$code) return JsonService::fail('请输入验证码');
        $code=md5('is_phone_code'.$code);
        if (!SmsCode::CheckCode($phone, $code)) return JsonService::fail('验证码验证失败');
        SmsCode::setCodeInvalid($phone, $code);
        return JsonService::successful('验证成功');
    }

    /**
     * 用户数据保存
     */
    public function save_user_info()
    {
        $data = UtilService::postMore([
            ['avatar', ''],
            ['full_name', ''],
            ['nickname', '']
        ], $this->request);
        if($data['nickname'] != strip_tags($data['nickname'])){
            $data['nickname'] = htmlspecialchars($data['nickname']);
        }
        if (!$data['nickname']) return JsonService::fail('用户昵称不能为空');
        if (User::update($data, ['uid' => $this->uid]))
            return JsonService::successful('保存成功');
        else
            return JsonService::fail('保存失败');
    }

    // 预检测手机号是否已经注册过h5，用于提醒用户合并用户风险
    public function check_is_h5()
    {
        list($phone) = UtilService::getMore([
            ['phone', '']
        ], $this->request, true);
        if (!$phone) return JsonService::fail('请输入手机号码');
        $user=User::where(['phone' => $phone])->where('is_h5user', '>', 0)->find();
        if ($user) {
            return JsonService::successful('检测到该手机号已在其他渠道注册过，继续绑定会丢失部分数据且无法恢复，请谨慎操作，是否继续绑定？', ['used' => 1]);
        } else {
            return JsonService::successful('手机号未注册过', ['used' => 0]);
        }
    }

    /**
     * 保存新手机号码
     * @return mixed|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function save_phone()
    {
        list($phone, $code, $type) = UtilService::getMore([
            ['phone', ''],
            ['code', ''],
            ['type', 0],
        ], $this->request, true);
        if (!$phone) return JsonService::fail('请输入手机号码');
        if (!$code) return JsonService::fail('请输入验证码');
        $code=md5('is_phone_code'.$code);
        if (!SmsCode::CheckCode($phone, $code)) return JsonService::fail('验证码验证失败');
        SmsCode::setCodeInvalid($phone, $code);
        // 检查手机号是否绑定过
        $user = User::where(['phone' => $phone])->find();
        // 需判断是不是换绑手机号，如果是换绑的话，不允许绑定已经被占用的手机号，因无法保证两个账户购买信息正常合并
        // 如果是微信账号或小程序账号第一次绑定手机号则允许绑定h5占用的手机号，因为账号里肯定没有购买信息
        // 如果现账号已经有了手机号，则说明现在是在换绑手机号
        if($this->userInfo['phone']) {
            if ($user) {
                return JsonService::fail('该手机号已被占用，请更换手机号重试');
            } else {
                $ures = User::update(['phone' => $phone, 'account' => $phone], ['uid' => $this->uid]);
                if ($ures) {
                    return JsonService::successful('绑定成功');
                } else {
                    return JsonService::fail('绑定失败');
                }
            }
        }
        // 如果没绑定过，就直接把手机号写入user表
        if(!$user){
            $currentOpenid = $this->userInfo['openid'];
            $bindData = ['phone' => $phone];
            // 对旧系统做兼容，如果$currentOpenid为空，说明是旧系统用户，需要从wechatUser中获取openid
            if (!$currentOpenid) {
                $currentOpenid = WechatUser::where(['uid' => $this->uid])->value('openid');
                $bindData['openid'] = $currentOpenid;
            }
            $ures = User::update($bindData, ['uid' => $this->uid]);
            if ($ures) {
                return JsonService::successful('绑定成功');
            } else {
                return JsonService::fail('绑定失败');
            }
        }
        if($user['uid'] == $this->uid) {
            return JsonService::fail('不能绑定相同手机号');
        }
        // 如果手机号绑定过，检查该用户是否已经绑定过其他公众号，如果已经绑定了其他公众号那就不让绑
        if ($user['openid']) {
            return JsonService::fail('该手机号已被占用，请更换手机号重试');
        } else {
            // 如果没绑定过其他公众号，说明这个手机号已经在h5或小程序端注册过了，需要把当前openid写到已经绑定过手机的那个账号，并把现有账号删掉，注意需要重新登录；
            $currentOpenid = $this->userInfo['openid'];
            // 对旧系统做兼容，如果$currentOpenid为空，说明是旧系统用户，需要从wechatUser中获取openid
            if (!$currentOpenid) {
                $currentOpenid = WechatUser::where(['uid' => $this->uid])->value('openid');
            }
            // 把当前账号的购买信息合并到已经绑定过手机号的账号
            User::setUserRelationInfos($this->uid, $user['uid']);
            $duser = User::where(['uid' => $this->uid])->delete();
            $ures = User::update(['openid' => $currentOpenid], ['uid' => $user['uid']]);
            $uwres = true;
            // 判断这个手机号所在用户有没有对应的WeChatUser，因为通过h5注册的用户没有WeChatUser
            // 如果没有wechatuser，则保留刚刚生成WeChatuser，并把uid指向原h5账号
            if (!WechatUser::be(['uid' => $user['uid']])) {
                $dwuser = WechatUser::update(['uid' => $user['uid']], ['uid' => $this->uid]);
            } else {
                // 如果原账号已经有了WeChatuser，说明之前已经绑定过小程序，直接删除刚生成的WeChatuser，并把openid写入原有wechatuser
                $dwuser = WechatUser::where(['uid' => $this->uid])->delete();
                $uwres = WechatUser::update(['openid' => $currentOpenid], ['uid' => $user['uid']]);
            }

            if ($duser && $dwuser && $ures && $uwres) {
                return JsonService::successful('绑定成功');
            } else {
                return JsonService::fail('绑定失败');
            }
        }
    }
}
