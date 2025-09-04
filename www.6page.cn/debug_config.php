<?php
// 简单的配置检查脚本
define('APP_PATH', __DIR__ . '/application/');
define('RUNTIME_PATH', __DIR__ . '/runtime/');
define('ROOT_PATH', __DIR__ . '/');
define('EXTEND_PATH', __DIR__ . '/extend/');
define('VENDOR_PATH', __DIR__ . '/vendor/');

require_once __DIR__ . '/thinkphp/start.php';

// 检查关键系统配置
try {
    $db = \think\Db::connect();
    
    // 查询关键配置
    $configs = $db->table('wy_system_config')->where('menu_name', 'IN', [
        'pc_on_display',
        'site_name', 
        'img_domain'
    ])->column('value', 'menu_name');
    
    echo "=== 系统配置检查 ===\n";
    echo "pc_on_display: " . ($configs['pc_on_display'] ?? '未设置') . "\n";
    echo "site_name: " . ($configs['site_name'] ?? '未设置') . "\n";
    echo "img_domain: " . ($configs['img_domain'] ?? '未设置') . "\n";
    
    // 测试数据库连接
    $result = $db->query('SELECT 1 as test');
    echo "数据库连接: " . ($result ? "正常" : "失败") . "\n";
    
    // 检查是否是移动端访问
    $is_mobile = request()->isMobile();
    echo "是否移动端访问: " . ($is_mobile ? "是" : "否") . "\n";
    
    echo "当前URL: " . request()->url() . "\n";
    echo "User-Agent: " . request()->header('User-Agent') . "\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "错误文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}