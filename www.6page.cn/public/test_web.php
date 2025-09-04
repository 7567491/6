<?php
// 简单的网站功能测试脚本
require_once '../application/constant.php';

// 设置基本常量（避免警告）
define('THINK_PATH', dirname(__DIR__) . '/thinkphp/');
define('EXT', '.php');
define('DS', DIRECTORY_SEPARATOR);
define('LOG_PATH', dirname(__DIR__) . '/runtime/log/');
define('CACHE_PATH', dirname(__DIR__) . '/runtime/cache/');

echo "<h2>网站修复验证测试</h2>";

// 测试数据库连接配置
$db_config = include '../application/database.php';
echo "<p><strong>数据库配置:</strong></p>";
echo "<ul>";
echo "<li>主机: " . $db_config['hostname'] . "</li>";
echo "<li>数据库: " . $db_config['database'] . "</li>";
echo "<li>用户: " . $db_config['username'] . "</li>";
echo "<li>端口: " . $db_config['hostport'] . "</li>";
echo "</ul>";

// 测试数据库连接
try {
    $pdo = new PDO(
        'mysql:host=' . $db_config['hostname'] . ';dbname=' . $db_config['database'] . ';charset=utf8mb4',
        $db_config['username'],
        $db_config['password']
    );
    echo "<p>✅ <strong>数据库连接成功</strong></p>";
    
    // 测试获取系统配置
    $stmt = $pdo->prepare("SELECT menu_name, value FROM wy_system_config WHERE menu_name IN ('site_name', 'home_logo', 'site_url') LIMIT 3");
    $stmt->execute();
    $configs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>系统配置读取测试:</strong></p>";
    echo "<ul>";
    foreach ($configs as $config) {
        $value = json_decode($config['value'], true);
        if (is_array($value)) {
            $value = $value[0] ?? 'N/A';
        }
        echo "<li>" . $config['menu_name'] . ": " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ <strong>数据库连接失败:</strong> " . $e->getMessage() . "</p>";
}

// 测试配置文件
$app_config = include '../application/config.php';
echo "<p><strong>应用配置:</strong></p>";
echo "<ul>";
echo "<li>img_domain: " . ($app_config['img_domain'] ?: '(空)') . "</li>";
echo "</ul>";

echo "<p><strong>关键路径测试:</strong></p>";
$paths = [
    '../public/uploads' => 'Uploads 目录',
    '../public/pc' => 'PC 静态资源',
    '../runtime' => 'Runtime 目录',
];

echo "<ul>";
foreach ($paths as $path => $desc) {
    if (file_exists($path) && is_readable($path)) {
        echo "<li>✅ $desc 可访问</li>";
    } else {
        echo "<li>❌ $desc 不可访问</li>";
    }
}
echo "</ul>";

echo "<hr>";
echo "<p><strong>现在请测试登录页面:</strong> <a href='/login' target='_blank'>点击访问登录页</a></p>";
echo "<p><strong>主页测试:</strong> <a href='/' target='_blank'>点击访问首页</a></p>";
?>