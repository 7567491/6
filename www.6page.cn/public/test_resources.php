<?php
/**
 * 资源加载测试脚本
 * 访问: http://your-domain/test_resources.php
 */

// 测试静态资源文件是否存在
$resources = [
    'CSS文件' => [
        'pc/styles/normalize.css',
        'pc/styles/global.min.css',
        'pc/font/iconfont.css'
    ],
    'JS文件' => [
        'pc/requirejs/require.js',
        'pc/vue/dist/vue.min.js',
        'pc/element-ui/lib/index.js'
    ],
    '图片资源' => [
        'pc/images/1.png',
        'pc/images/2.png',
        'pc/images/3.png'
    ]
];

echo "<!DOCTYPE html>\n";
echo "<html lang='zh-CN'>\n";
echo "<head>\n";
echo "    <meta charset='UTF-8'>\n";
echo "    <title>资源加载测试</title>\n";
echo "    <style>\n";
echo "        body { font-family: Arial, sans-serif; padding: 20px; }\n";
echo "        .success { color: #28a745; }\n";
echo "        .error { color: #dc3545; }\n";
echo "        .warning { color: #ffc107; }\n";
echo "        table { width: 100%; border-collapse: collapse; margin: 10px 0; }\n";
echo "        th, td { padding: 8px 12px; text-align: left; border: 1px solid #ddd; }\n";
echo "        th { background-color: #f8f9fa; }\n";
echo "        .test-section { margin: 20px 0; }\n";
echo "    </style>\n";
echo "</head>\n";
echo "<body>\n";

echo "<h1>资源加载测试报告</h1>\n";
echo "<p>测试时间: " . date('Y-m-d H:i:s') . "</p>\n";

// 获取img_domain配置
$configFile = __DIR__ . '/../application/config.php';
if (file_exists($configFile)) {
    $config = include $configFile;
    $img_domain = isset($config['img_domain']) ? $config['img_domain'] : '/';
} else {
    $img_domain = '/';
}

echo "<div class='test-section'>\n";
echo "<h2>配置信息</h2>\n";
echo "<p><strong>img_domain:</strong> <code>" . htmlspecialchars($img_domain) . "</code></p>\n";
echo "<p><strong>文档根目录:</strong> <code>" . __DIR__ . "</code></p>\n";
echo "</div>\n";

foreach ($resources as $category => $files) {
    echo "<div class='test-section'>\n";
    echo "<h2>$category</h2>\n";
    echo "<table>\n";
    echo "<thead><tr><th>文件路径</th><th>状态</th><th>文件大小</th><th>修改时间</th></tr></thead>\n";
    echo "<tbody>\n";
    
    foreach ($files as $file) {
        $fullPath = __DIR__ . '/' . $file;
        $url = ($img_domain === '/' ? '' : rtrim($img_domain, '/')) . '/' . $file;
        
        if (file_exists($fullPath)) {
            $size = filesize($fullPath);
            $modified = date('Y-m-d H:i:s', filemtime($fullPath));
            echo "<tr>\n";
            echo "<td><a href='$url' target='_blank'>$file</a></td>\n";
            echo "<td class='success'>✓ 存在</td>\n";
            echo "<td>" . formatBytes($size) . "</td>\n";
            echo "<td>$modified</td>\n";
            echo "</tr>\n";
        } else {
            echo "<tr>\n";
            echo "<td>$file</td>\n";
            echo "<td class='error'>✗ 不存在</td>\n";
            echo "<td>-</td>\n";
            echo "<td>-</td>\n";
            echo "</tr>\n";
        }
    }
    
    echo "</tbody>\n";
    echo "</table>\n";
    echo "</div>\n";
}

// 测试数据库连接
echo "<div class='test-section'>\n";
echo "<h2>数据库连接测试</h2>\n";

$dbConfigFile = __DIR__ . '/../application/database.php';
if (file_exists($dbConfigFile)) {
    $dbConfig = include $dbConfigFile;
    
    try {
        $dsn = "mysql:host=" . $dbConfig['hostname'] . ";port=" . $dbConfig['hostport'] . ";dbname=" . $dbConfig['database'];
        $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5
        ]);
        
        // 测试查询
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = '" . $dbConfig['database'] . "'");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p class='success'>✓ 数据库连接成功</p>\n";
        echo "<p>数据库: " . htmlspecialchars($dbConfig['database']) . "</p>\n";
        echo "<p>表数量: " . $result['count'] . "</p>\n";
        
    } catch (Exception $e) {
        echo "<p class='error'>✗ 数据库连接失败: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    }
} else {
    echo "<p class='warning'>⚠ 数据库配置文件不存在</p>\n";
}
echo "</div>\n";

// 系统信息
echo "<div class='test-section'>\n";
echo "<h2>系统信息</h2>\n";
echo "<table>\n";
echo "<tr><td>PHP版本</td><td>" . PHP_VERSION . "</td></tr>\n";
echo "<tr><td>服务器软件</td><td>" . ($_SERVER['SERVER_SOFTWARE'] ?? '未知') . "</td></tr>\n";
echo "<tr><td>文档根目录</td><td>" . $_SERVER['DOCUMENT_ROOT'] . "</td></tr>\n";
echo "<tr><td>当前目录</td><td>" . __DIR__ . "</td></tr>\n";
echo "</table>\n";
echo "</div>\n";

echo "<div class='test-section'>\n";
echo "<h2>建议访问</h2>\n";
echo "<ul>\n";
echo "<li><a href='/index-new' target='_blank'>新版主页测试 (/index-new)</a></li>\n";
echo "<li><a href='/index' target='_blank'>原版主页 (/index)</a></li>\n";
echo "</ul>\n";
echo "</div>\n";

echo "</body>\n";
echo "</html>\n";

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
?>