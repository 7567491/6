<?php

namespace app\captcha\controller;

use Fastknife\Exception\ParamException;
use Fastknife\Service\ClickWordCaptchaService;
use Fastknife\Service\BlockPuzzleCaptchaService;
use Fastknife\Service\Service;
use think\Controller;
use think\exception\HttpResponseException;
use think\Response;

/**
 * 该文件位于controller目录下
 * Class Index
 * @package app\controller
 */
class Index extends Controller
{
    public function get()
    {
        try {
            $service = $this->getCaptchaService();
            $data = $service->get();
        } catch (\Exception $e) {
            $this->cerror($e->getMessage());
        }
        $this->csuccess($data);
    }

    /**
     * 一次验证
     */
    public function check()
    {
        $data = request()->post();
        try {
//            $this->validate($data);
            $service = $this->getCaptchaService();
            $service->check($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            $this->cerror($e->getMessage());
        }
        $this->csuccess([]);
    }

    /**
     * 二次验证
     */
    public function verification()
    {
        $data = request()->post();
        try {
//            $this->validate($data);
            $service = $this->getCaptchaService();
            $service->verification($data['token'], $data['pointJson']);
        } catch (\Exception $e) {
            return array('error' => true, 'msg' => $e->getMessage());
        }
        return array('error' => false, 'msg' => '验证成功');
    }

    protected function getCaptchaService()
    {
        $captchaType = request()->post('captchaType', null);
        $config = config('captcha');
        switch ($captchaType) {
            case "clickWord":
                $service = new ClickWordCaptchaService($config);
                break;
            case "blockPuzzle":
                $service = new BlockPuzzleCaptchaService($config);
                break;
            default:
                throw new ParamException('captchaType参数不正确！');
        }
        return $service;
    }

//    protected function validate($data)
//    {
//        $rules = [
//            'token' => ['require'],
//            'pointJson' => ['require']
//        ];
//        $validate = Validate::rule($rules)->failException(true);
//        $validate->check($data);
//    }

    protected function csuccess($data)
    {
        $response = [
            'error' => false,
            'repCode' => '0000',
            'repData' => $data,
            'repMsg' => null,
            'success' => true,
        ];
        throw new HttpResponseException(Response::create($response, 'json'));
    }


    protected function cerror($msg)
    {
        $response = [
            'error' => true,
            'repCode' => '6111',
            'repData' => null,
            'repMsg' => $msg,
            'success' => false,
        ];
        throw new HttpResponseException(Response::create($response, 'json'));
    }


}
