<?php



return [
    // 默认控制器名
    'default_controller' => 'Index',
    // 默认操作名
    'default_action' => 'index',
    // 自动搜索控制器
    'controller_auto_search' => true,
    'session' => [
        // SESSION 前缀
        'prefix' => 'web',
        // 驱动方式 支持redis memcache memcached
        'type' => '',
        // 是否自动开启 SESSION
        'auto_start' => true,
        'expire' => 86400,
        'cache_expire' => 86400
    ],
    'template' => [
        // 模板引擎类型 支持 php think 支持扩展
        'type' => 'Think',
        // 模板路径
        'view_path' => APP_PATH . 'web/view/',
        // 模板后缀
        'view_suffix' => 'html',
        // 模板文件名分隔符
        'view_depr' => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin' => '{',
        // 模板引擎普通标签结束标记
        'tpl_end' => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end' => '}',
    ],
    // 视图输出字符串内容替换
    'view_replace_str' => [
        '{__PUBLIC_WEB_PATH}' => PUBLIC_PATH,
        '{__PLUG_WEB_PATH}' => PUBLIC_PATH . 'static/plug/',
    ],

    'exception_handle' => \app\web\controller\WebException::class,
    'empty_controller' => 'AuthController'
];
