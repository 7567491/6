#!/bin/bash
# 测试验证脚本

set -e

source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/utils/validate.sh"
source "./sh/config/servers.conf"

log_step_start "测试验证"
update_stage_status "testing" "running" 0

log_info "开始执行系统测试验证..."

# 基础服务测试
test_services() {
    log_info "测试基础服务..."
    
    if run_validation "services"; then
        log_success "基础服务测试通过"
    else
        log_error "基础服务测试失败"
        return 1
    fi
    
    update_stage_status "testing" "running" 25
}

# 数据库测试
test_database() {
    log_info "测试数据库连接..."
    
    if run_validation "database"; then
        log_success "数据库测试通过"
    else
        log_error "数据库测试失败"
        return 1
    fi
    
    # 测试数据完整性
    log_info "检查数据库表..."
    local table_count=$(mysql -u "$DB_USER" -p"$DB_PASS" -D "$DB_NAME" -e "SHOW TABLES;" 2>/dev/null | wc -l)
    if [[ $table_count -gt 1 ]]; then
        log_success "数据库表数量: $((table_count - 1))"
    else
        log_warn "数据库表为空或连接失败"
    fi
    
    update_stage_status "testing" "running" 50
}

# 网站功能测试
test_websites() {
    log_info "测试网站访问..."
    
    local domains=("$PRIMARY_DOMAIN" "$VOD_DOMAIN")
    local protocols=("http" "https")
    
    for domain in "${domains[@]}"; do
        for protocol in "${protocols[@]}"; do
            log_info "测试: ${protocol}://${domain}"
            
            local status_code=$(curl -s -o /dev/null -w "%{http_code}" "${protocol}://${domain}" --max-time 10 || echo "000")
            
            case $status_code in
                200) log_success "网站正常: ${protocol}://${domain}" ;;
                301|302) log_success "重定向正常: ${protocol}://${domain} (HTTP $status_code)" ;;
                000) log_warn "连接超时: ${protocol}://${domain}" ;;
                *) log_warn "异常响应: ${protocol}://${domain} (HTTP $status_code)" ;;
            esac
        done
    done
    
    update_stage_status "testing" "running" 75
}

# PHP功能测试
test_php_functionality() {
    log_info "测试PHP功能..."
    
    # 创建PHP测试文件
    local test_file="/var/www/$PRIMARY_DOMAIN/public/test_php.php"
    sudo tee "$test_file" << 'EOF'
<?php
echo "PHP测试页面\n";
echo "PHP版本: " . phpversion() . "\n";

// 测试数据库连接
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=6page", "6page_user", "your_secure_password");
    echo "数据库连接: 正常\n";
} catch(PDOException $e) {
    echo "数据库连接: 失败 - " . $e->getMessage() . "\n";
}

// 测试Redis连接
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
        echo "Redis连接: 正常\n";
    } catch (Exception $e) {
        echo "Redis连接: 失败 - " . $e->getMessage() . "\n";
    }
} else {
    echo "Redis扩展: 未安装\n";
}

echo "测试完成\n";
?>
EOF
    
    sudo chown www-data:www-data "$test_file"
    
    # 执行PHP测试
    log_info "执行PHP功能测试..."
    local php_result=$(curl -s "http://$PRIMARY_DOMAIN/test_php.php" || echo "PHP测试失败")
    echo "$php_result" | tee -a "./logs/php_test.log"
    
    if echo "$php_result" | grep -q "测试完成"; then
        log_success "PHP功能测试通过"
    else
        log_warn "PHP功能测试异常"
    fi
    
    # 清理测试文件
    sudo rm -f "$test_file"
    
    update_stage_status "testing" "running" 90
}

# 性能基准测试
performance_benchmark() {
    log_info "执行性能基准测试..."
    
    # 简单的并发测试
    log_info "测试网站响应时间..."
    for i in {1..5}; do
        local response_time=$(curl -o /dev/null -s -w "%{time_total}\n" "http://$PRIMARY_DOMAIN")
        log_info "第${i}次响应时间: ${response_time}秒"
    done
    
    # 系统负载检查
    local load_avg=$(uptime | awk -F'load average:' '{print $2}' | awk '{print $1}' | sed 's/,//')
    log_info "当前系统负载: $load_avg"
    
    # 内存使用检查
    local mem_usage=$(free | grep Mem | awk '{printf "%.1f", $3/$2 * 100.0}')
    log_info "内存使用率: ${mem_usage}%"
    
    log_success "性能基准测试完成"
    update_stage_status "testing" "running" 95
}

# 生成测试报告
generate_test_report() {
    log_info "生成测试报告..."
    
    local report_file="./logs/test_report_$(date +%Y%m%d_%H%M%S).txt"
    
    cat > "$report_file" << EOF
==========================================
           迁移测试验证报告
==========================================
生成时间: $(date)
服务器IP: $TARGET_SERVER_IP
测试域名: $PRIMARY_DOMAIN, $VOD_DOMAIN

==========================================
            服务状态检查
==========================================
$(systemctl status nginx mysql redis-server php7.4-fpm --no-pager -l)

==========================================
            网络连接测试
==========================================
$(netstat -tlnp | grep -E "(80|443|3306|6379)")

==========================================
            磁盘空间检查  
==========================================
$(df -h)

==========================================
            内存使用情况
==========================================
$(free -h)

==========================================
            系统负载
==========================================
$(uptime)

==========================================
            近期错误日志
==========================================
$(tail -20 ./logs/error.log 2>/dev/null || echo "无错误日志")

==========================================
            测试结论
==========================================
EOF
    
    # 添加测试结论
    if systemctl is-active --quiet nginx mysql php7.4-fpm; then
        echo "✓ 基础服务运行正常" >> "$report_file"
    else
        echo "✗ 部分基础服务异常" >> "$report_file"
    fi
    
    if mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" >/dev/null 2>&1; then
        echo "✓ 数据库连接正常" >> "$report_file"
    else
        echo "✗ 数据库连接异常" >> "$report_file"
    fi
    
    local http_status=$(curl -s -o /dev/null -w "%{http_code}" "http://$PRIMARY_DOMAIN" || echo "000")
    if [[ "$http_status" =~ ^(200|301|302)$ ]]; then
        echo "✓ 网站访问正常" >> "$report_file"
    else
        echo "✗ 网站访问异常 (HTTP $http_status)" >> "$report_file"
    fi
    
    echo "" >> "$report_file"
    echo "详细测试日志请查看: ./logs/" >> "$report_file"
    echo "迁移状态查看: ./sh/utils/status.sh show" >> "$report_file"
    
    log_success "测试报告已生成: $report_file"
    
    # 显示报告摘要
    echo ""
    echo "========== 测试报告摘要 =========="
    tail -15 "$report_file"
    echo "================================="
}

# 主执行流程
main() {
    test_services
    test_database  
    test_websites
    test_php_functionality
    performance_benchmark
    generate_test_report
    
    update_stage_status "testing" "completed" 100
    create_rollback_point "testing_completed" "测试验证完成"
    
    log_step_end "测试验证" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "    测试验证完成"
    echo "======================="
    echo "验证结果:"
    echo "- 基础服务: $(systemctl is-active nginx mysql redis-server php7.4-fpm --quiet && echo "✓ 正常" || echo "✗ 异常")"
    echo "- 数据库: $(mysql -u "$DB_USER" -p"$DB_PASS" -e "SELECT 1;" >/dev/null 2>&1 && echo "✓ 正常" || echo "✗ 异常")"
    echo "- 网站访问: $(curl -s -o /dev/null -w "%{http_code}" "http://$PRIMARY_DOMAIN" | grep -q -E "^(200|301|302)$" && echo "✓ 正常" || echo "✗ 异常")"
    echo ""
    echo "🎉 恭喜！迁移流程全部完成！"
    echo ""
    echo "后续操作建议:"
    echo "1. 更新域名DNS解析指向新服务器"
    echo "2. 监控网站运行状况"
    echo "3. 定期检查备份和日志"
    echo ""
}

# 错误处理
trap 'log_step_end "测试验证" "ERROR"; update_stage_status "testing" "failed" 0; exit 1' ERR

main "$@"