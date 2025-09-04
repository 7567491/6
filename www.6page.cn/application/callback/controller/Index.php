<?php


namespace app\callback\controller;

use app\admin\model\live\LiveStudio;
use think\Controller;

class Index extends Controller
{

    /**
     * 直播推流回调
     * */
    public function serve()
    {
        $request = $this->request->request();
        $id = isset($request['id']) ? $request['id'] : '';
        $action = $this->request->request('action', '');
        $is_play = $action == 'publish' ? 1 : 0;
        LiveStudio::setLivePalyStatus($id, $is_play, $action);
    }
}
