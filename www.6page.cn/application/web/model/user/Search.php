<?php


namespace app\web\model\user;

use app\web\model\special\Special;
use service\SystemConfigService;
use think\cache\driver\Redis;
use traits\ModelTrait;
use basic\ModelBasic;
use think\Db;

/**搜索表
 * Class Search
 * @package app\wap\model
 */
class Search extends ModelBasic
{
    use ModelTrait;

    public static function getHostSearch()
    {
        return self::order('add_time desc')->column('name');
    }

}
