#!/bin/bash
# 回滚脚本

set -e

source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"

log_info "开始执行回滚操作..."

# 显示可用回滚点
show_rollback_points() {
    echo "可用的回滚点:"
    if [[ -f "./logs/migration_status.json" ]] && command -v jq >/dev/null; then
        jq -r '.rollback_points[] | "- \(.name): \(.description) (\(.time))"' "./logs/migration_status.json"
    else
        echo "无可用回滚点信息"
    fi
}

# 回滚Nginx配置
rollback_nginx() {
    log_info "回滚Nginx配置..."
    
    local backup_dir=$(ls -t ./backups/nginx_* 2>/dev/null | head -1)
    if [[ -n "$backup_dir" ]]; then
        sudo rm -rf /etc/nginx/sites-enabled/*
        sudo cp -r "$backup_dir"/* /etc/nginx/
        sudo systemctl restart nginx
        log_success "Nginx配置已回滚"
    else
        log_warn "未找到Nginx备份，跳过"
    fi
}

# 回滚服务配置
rollback_services() {
    log_info "回滚服务配置..."
    
    # 恢复PHP-FPM配置
    if [[ -f "/etc/php/7.4/fpm/pool.d/www.conf.backup" ]]; then
        sudo mv /etc/php/7.4/fpm/pool.d/www.conf.backup /etc/php/7.4/fpm/pool.d/www.conf
        sudo systemctl restart php7.4-fpm
    fi
    
    # 恢复MySQL配置
    if [[ -f "/etc/mysql/mysql.conf.d/mysqld.cnf.backup" ]]; then
        sudo mv /etc/mysql/mysql.conf.d/mysqld.cnf.backup /etc/mysql/mysql.conf.d/mysqld.cnf
        sudo systemctl restart mysql
    fi
    
    # 恢复Redis配置
    if [[ -f "/etc/redis/redis.conf.backup" ]]; then
        sudo mv /etc/redis/redis.conf.backup /etc/redis/redis.conf
        sudo systemctl restart redis-server
    fi
    
    log_success "服务配置已回滚"
}

# 清理临时文件
cleanup_temp_files() {
    log_info "清理临时文件..."
    
    rm -rf /home/6page/*.sql /home/6page/*.tar.gz /home/6page/*.rdb 2>/dev/null || true
    sudo rm -f /var/www/*/public/test_php.php 2>/dev/null || true
    
    log_success "临时文件清理完成"
}

# 主执行流程
main() {
    echo ""
    echo "======================================="
    echo "           回滚操作"
    echo "======================================="
    echo ""
    
    show_rollback_points
    echo ""
    
    read -p "确认执行回滚操作? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "回滚操作已取消"
        exit 0
    fi
    
    rollback_nginx
    rollback_services
    cleanup_temp_files
    
    echo ""
    echo "======================================="
    echo "          回滚操作完成"
    echo "======================================="
    echo "已执行的回滚操作:"
    echo "- Nginx配置回滚"
    echo "- 服务配置回滚"
    echo "- 临时文件清理"
    echo ""
    echo "注意: 数据库和网站文件需要手动恢复"
    echo ""
    
    log_success "回滚操作执行完毕"
}

main "$@"