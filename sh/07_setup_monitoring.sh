#!/bin/bash
# 监控和日志配置脚本

set -e

source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"

log_step_start "监控和日志配置"
update_stage_status "monitoring" "running" 0

# 安装监控工具
install_monitoring_tools() {
    log_info "安装监控工具..."
    
    sudo apt update -q
    sudo apt install -y htop iotop nethogs logrotate
    
    log_success "监控工具安装完成"
    update_stage_status "monitoring" "running" 25
}

# 配置日志轮转
setup_log_rotation() {
    log_info "配置日志轮转..."
    
    sudo tee /etc/logrotate.d/migration << 'EOF'
/home/6page/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 6page 6page
}

/var/log/nginx/sites/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data adm
    postrotate
        systemctl reload nginx
    endscript
}
EOF
    
    log_success "日志轮转配置完成"
    update_stage_status "monitoring" "running" 50
}

# 创建监控脚本
create_monitoring_scripts() {
    log_info "创建监控脚本..."
    
    sudo tee /opt/system_monitor.sh << 'EOF'
#!/bin/bash
# 系统监控脚本

LOG_FILE="/var/log/system_monitor.log"

echo "$(date): 系统监控检查" >> $LOG_FILE

# 检查磁盘使用率
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 80 ]; then
    echo "$(date): 警告 - 磁盘使用率过高: ${DISK_USAGE}%" >> $LOG_FILE
fi

# 检查内存使用率
MEM_USAGE=$(free | grep Mem | awk '{printf "%.0f", $3/$2 * 100.0}')
if [ $MEM_USAGE -gt 80 ]; then
    echo "$(date): 警告 - 内存使用率过高: ${MEM_USAGE}%" >> $LOG_FILE
fi

# 检查服务状态
SERVICES=("nginx" "mysql" "redis-server" "php7.4-fpm")
for service in "${SERVICES[@]}"; do
    if ! systemctl is-active --quiet $service; then
        echo "$(date): 错误 - 服务未运行: $service" >> $LOG_FILE
    fi
done
EOF
    
    sudo chmod +x /opt/system_monitor.sh
    
    # 添加到定时任务
    (crontab -l 2>/dev/null; echo "*/15 * * * * /opt/system_monitor.sh") | crontab -
    
    log_success "监控脚本创建完成"
    update_stage_status "monitoring" "running" 75
}

# 设置迁移状态报告
setup_migration_reporting() {
    log_info "设置迁移状态报告..."
    
    sudo tee /opt/migration_report.sh << 'EOF'
#!/bin/bash
# 迁移状态报告脚本

source /home/6page/sh/utils/status.sh
source /home/6page/sh/utils/validate.sh

echo "========== 迁移状态报告 =========="
echo "生成时间: $(date)"
echo

# 显示迁移状态
show_status

echo
echo "========== 服务验证 =========="
run_validation "services"

echo
echo "========== 网站验证 =========="  
run_validation "websites"

echo
echo "========== 系统资源 =========="
echo "磁盘使用: $(df -h / | awk 'NR==2 {print $5}')"
echo "内存使用: $(free -h | grep Mem | awk '{print $3 "/" $2}')"
echo "负载平均: $(uptime | awk -F'load average:' '{print $2}')"
EOF
    
    sudo chmod +x /opt/migration_report.sh
    
    log_success "迁移状态报告设置完成"
    update_stage_status "monitoring" "running" 100
}

main() {
    install_monitoring_tools
    setup_log_rotation
    create_monitoring_scripts
    setup_migration_reporting
    
    update_stage_status "monitoring" "completed" 100
    create_rollback_point "monitoring_setup" "监控和日志配置完成"
    
    log_step_end "监控和日志配置" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  监控配置完成"
    echo "======================="
    echo "已设置的监控:"
    echo "- 系统资源监控 (每15分钟)"
    echo "- 日志轮转配置"
    echo "- 迁移状态报告"
    echo ""
    echo "查看报告: sudo /opt/migration_report.sh"
    echo ""
    echo "下一步执行: ./sh/08_run_tests.sh"
    echo ""
}

trap 'log_step_end "监控和日志配置" "ERROR"; update_stage_status "monitoring" "failed" 0; exit 1' ERR
main "$@"