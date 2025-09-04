<?php




return [
    'session'                => [
        // SESSION 前缀
        'prefix'         => 'admin',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
        //有效期
        'expire' => 86400,
    ],
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 视图输出字符串内容替换
    'view_replace_str'       => [
        '{__ADMIN_PATH}'=>PUBLIC_PATH.'system/',//后台
        '{__FRAME_PATH}'=>PUBLIC_PATH.'system/frame/',//H+框架
        '{__PLUG_PATH}'=>PUBLIC_PATH.'static/plug/',//前后台通用
        '{__MODULE_PATH}'=>PUBLIC_PATH.'system/module/',//后台功能模块
        '{__STATIC_PATH}'=>PUBLIC_PATH.'static/',//全站通用
        '{__PUBLIC_PATH}'=>PUBLIC_PATH,//静态资源路径
        '{__PC_KS3}'=>PUBLIC_PATH.'pc/ks3-js-sdk/'
    ],
];
