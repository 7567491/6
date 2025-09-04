#!/bin/bash
# 服务优化配置脚本

set -e

source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "服务优化配置"
update_stage_status "optimization" "running" 0

# PHP-FPM优化
optimize_php_fpm() {
    log_info "优化PHP-FPM配置..."
    
    sudo cp /etc/php/7.4/fpm/pool.d/www.conf /etc/php/7.4/fpm/pool.d/www.conf.backup
    
    sudo tee /etc/php/7.4/fpm/pool.d/www.conf << 'EOF'
[www]
user = www-data
group = www-data
listen = /var/run/php/php7.4-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 10
pm.min_spare_servers = 5
pm.max_spare_servers = 15
pm.max_requests = 1000

slowlog = /var/log/php7.4-fpm.log.slow
request_slowlog_timeout = 10s

php_admin_value[error_log] = /var/log/php7.4-fpm.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path] = /var/lib/php/sessions
php_value[soap.wsdl_cache_dir] = /var/lib/php/wsdlcache
EOF

    sudo systemctl restart php7.4-fpm
    log_success "PHP-FPM优化完成"
    update_stage_status "optimization" "running" 25
}

# MySQL优化
optimize_mysql() {
    log_info "优化MySQL配置..."
    
    sudo cp /etc/mysql/mysql.conf.d/mysqld.cnf /etc/mysql/mysql.conf.d/mysqld.cnf.backup
    
    sudo tee -a /etc/mysql/mysql.conf.d/mysqld.cnf << 'EOF'

# 优化配置
innodb_buffer_pool_size = 512M
max_connections = 200
query_cache_size = 64M
query_cache_type = 1
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 3
EOF

    sudo systemctl restart mysql
    log_success "MySQL优化完成"
    update_stage_status "optimization" "running" 50
}

# Redis优化
optimize_redis() {
    log_info "优化Redis配置..."
    
    sudo cp /etc/redis/redis.conf /etc/redis/redis.conf.backup
    
    sudo tee -a /etc/redis/redis.conf << 'EOF'

# 优化配置
maxmemory 256mb
maxmemory-policy allkeys-lru
save 900 1
save 300 10
save 60 10000
EOF

    sudo systemctl restart redis-server
    log_success "Redis优化完成"
    update_stage_status "optimization" "running" 75
}

# 设置定时任务
setup_cron_jobs() {
    log_info "设置定时任务..."
    
    # 数据库备份脚本
    sudo tee /opt/backup_database.sh << EOF
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
DATE=\$(date +%Y%m%d_%H%M%S)
mkdir -p \$BACKUP_DIR
mysqldump --single-transaction $DB_NAME > \$BACKUP_DIR/${DB_NAME}_\$DATE.sql
find \$BACKUP_DIR -name "*.sql" -mtime +7 -delete
EOF
    
    sudo chmod +x /opt/backup_database.sh
    
    # 添加定时任务
    (crontab -l 2>/dev/null; echo "0 2 * * * /opt/backup_database.sh") | crontab -
    
    log_success "定时任务设置完成"
    update_stage_status "optimization" "running" 100
}

main() {
    optimize_php_fpm
    optimize_mysql  
    optimize_redis
    setup_cron_jobs
    
    update_stage_status "optimization" "completed" 100
    create_rollback_point "services_optimized" "服务优化配置完成"
    
    log_step_end "服务优化配置" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  服务优化完成"
    echo "======================="
    echo "已优化的服务:"
    echo "- PHP-FPM 进程池配置"
    echo "- MySQL 缓冲区和连接数"
    echo "- Redis 内存策略"
    echo "- 自动备份定时任务"
    echo ""
    echo "下一步执行: ./sh/07_setup_monitoring.sh"
    echo ""
}

trap 'log_step_end "服务优化配置" "ERROR"; update_stage_status "optimization" "failed" 0; exit 1' ERR
main "$@"