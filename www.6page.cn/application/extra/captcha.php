<?php
declare(strict_types=1);
/**
 * 请将该文件放置于config目录
 */
return [
    'font_file' => '',
    //文字验证码
    'click_world' => [
        'backgrounds' => []
    ],
    //滑动验证码
    'block_puzzle' => [
        'backgrounds' => [], //背景图片路径， 不填使用默认值
        'templates' => [], //模板图
        'offset' => 10, //容错偏移量
    ],
    //水印
    'watermark' => [
        'fontsize' => 12,
        'color' => '#ffffff',
        'text' => ''
    ],
    'cache' => [
        'constructor' => \think\Cache::class,
        'method' => [
            // 遵守PSR-16规范不需要设置此项（tp6, laravel,hyperf）。如tp5就不支持（tp5缓存方法是rm,所以要配置为"delete" => "rm"）,
            'get' => 'get', //获取
            'set' => 'set', //设置
            'delete' => 'rm',//删除
            'has' => 'has' //key是否存在
        ],
    ]
];
