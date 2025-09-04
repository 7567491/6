<?php
// 简单的重定向测试页面
echo "<h1>重定向测试页面</h1>";
echo "<p>当前时间: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>访问方式: " . ($_SERVER['REQUEST_METHOD'] ?? '未知') . "</p>";
echo "<p>User-Agent: " . ($_SERVER['HTTP_USER_AGENT'] ?? '未设置') . "</p>";
echo "<p>请求URI: " . ($_SERVER['REQUEST_URI'] ?? '未设置') . "</p>";
echo "<p>是否移动端: " . (preg_match('/Mobile|Android|iPhone/', $_SERVER['HTTP_USER_AGENT'] ?? '') ? '是' : '否') . "</p>";

// 测试链接
echo "<h2>测试链接</h2>";
echo '<p><a href="/index-new">访问新主页 (/index-new)</a></p>';
echo '<p><a href="/web/index/index_new">直接访问控制器方法 (/web/index/index_new)</a></p>';
echo '<p><a href="/">访问首页 (/)</a></p>';

echo "<h2>路由信息</h2>";
if (file_exists(__DIR__ . '/application/route.php')) {
    $routes = file_get_contents(__DIR__ . '/application/route.php');
    if (strpos($routes, 'index-new') !== false) {
        echo "<p>✅ 路由文件中存在 index-new 路由配置</p>";
    } else {
        echo "<p>❌ 路由文件中不存在 index-new 路由配置</p>";
    }
} else {
    echo "<p>❌ 路由文件不存在</p>";
}

// 检查控制器方法是否存在
$controller_file = __DIR__ . '/application/web/controller/Index.php';
if (file_exists($controller_file)) {
    $controller_content = file_get_contents($controller_file);
    if (strpos($controller_content, 'index_new') !== false) {
        echo "<p>✅ 控制器中存在 index_new 方法</p>";
    } else {
        echo "<p>❌ 控制器中不存在 index_new 方法</p>";
    }
} else {
    echo "<p>❌ Index控制器文件不存在</p>";
}
?>