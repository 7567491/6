<?php
// 简单的数据库连接检查
try {
    // 读取数据库配置
    $db_config = include __DIR__ . '/application/database.php';
    
    echo "=== 数据库配置 ===\n";
    echo "主机名: " . $db_config['hostname'] . "\n";
    echo "数据库: " . $db_config['database'] . "\n";
    echo "用户名: " . $db_config['username'] . "\n";
    echo "前缀: " . $db_config['prefix'] . "\n\n";
    
    // 创建PDO连接
    $dsn = "mysql:host={$db_config['hostname']};dbname={$db_config['database']};charset={$db_config['charset']}";
    $pdo = new PDO($dsn, $db_config['username'], $db_config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== 数据库连接状态 ===\n";
    echo "连接成功！\n\n";
    
    // 查询关键系统配置
    $stmt = $pdo->prepare("SELECT menu_name, value FROM {$db_config['prefix']}system_config WHERE menu_name IN (?, ?, ?)");
    $stmt->execute(['pc_on_display', 'site_name', 'img_domain']);
    $configs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    echo "=== 系统配置 ===\n";
    echo "PC端显示开关 (pc_on_display): " . ($configs['pc_on_display'] ?? '未设置') . "\n";
    echo "网站名称 (site_name): " . ($configs['site_name'] ?? '未设置') . "\n"; 
    echo "图片域名 (img_domain): " . ($configs['img_domain'] ?? '未设置') . "\n\n";
    
    // 检查表是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE '{$db_config['prefix']}system_config'");
    $table_exists = $stmt->fetchColumn();
    echo "system_config表存在: " . ($table_exists ? "是" : "否") . "\n";
    
} catch (Exception $e) {
    echo "错误: " . $e->getMessage() . "\n";
    echo "文件: " . $e->getFile() . ":" . $e->getLine() . "\n";
}