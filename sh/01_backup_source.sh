#!/bin/bash
# 源服务器数据备份脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "源服务器数据备份"
update_stage_status "backup" "running" 0

log_info "开始备份源服务器数据..."

# SSH执行远程命令
ssh_exec() {
    local cmd="$1"
    sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
        "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "$cmd"
}

# 备份数据库
backup_databases() {
    log_info "备份数据库..."
    
    ssh_exec "
        echo '备份MySQL数据库...'
        mysqldump --single-transaction --routines --triggers --all-databases > $BACKUP_DIR/all_databases.sql
        mysqldump --single-transaction --routines --triggers 6page > $BACKUP_DIR/6page_database.sql 2>/dev/null || echo '6page数据库不存在，跳过'
        echo '数据库备份完成'
    "
    
    update_stage_status "backup" "running" 20
    log_success "数据库备份完成"
}

# 备份Redis数据
backup_redis() {
    log_info "备份Redis数据..."
    
    ssh_exec "
        echo '备份Redis数据...'
        if systemctl is-active --quiet redis; then
            redis-cli BGSAVE
            sleep 5
            cp /var/lib/redis/dump.rdb $BACKUP_DIR/redis_backup.rdb 2>/dev/null || \\
            cp /www/server/redis/dump.rdb $BACKUP_DIR/redis_backup.rdb 2>/dev/null || \\
            echo 'Redis数据文件未找到'
        else
            echo 'Redis服务未运行'
        fi
        echo 'Redis备份完成'
    "
    
    update_stage_status "backup" "running" 40
    log_success "Redis备份完成"
}

# 备份网站文件
backup_websites() {
    log_info "备份网站文件..."
    
    ssh_exec "
        echo '备份网站文件...'
        cd /www/wwwroot 2>/dev/null || cd /var/www/html || { echo '网站目录未找到'; exit 1; }
        
        # 查找实际的网站目录
        if [ -d 'www.6page.cn' ]; then
            tar -czf $BACKUP_DIR/websites_backup.tar.gz www.6page.cn/ dianbo.6page.cn/ 2>/dev/null || \\
            tar -czf $BACKUP_DIR/websites_backup.tar.gz www.6page.cn/
        else
            echo '正在搜索网站目录...'
            find . -maxdepth 2 -name '*.php' -o -name 'index.html' | head -10
            tar -czf $BACKUP_DIR/websites_backup.tar.gz . --exclude='*.log' --exclude='cache/*'
        fi
        
        # 单独备份上传文件
        if [ -d 'www.6page.cn/public/uploads' ]; then
            tar -czf $BACKUP_DIR/uploads_backup.tar.gz www.6page.cn/public/uploads/ www.6page.cn/runtime/ 2>/dev/null || true
        fi
        
        echo '网站文件备份完成'
    "
    
    update_stage_status "backup" "running" 60
    log_success "网站文件备份完成"
}

# 备份配置文件
backup_configs() {
    log_info "备份配置文件..."
    
    ssh_exec "
        echo '备份配置文件...'
        mkdir -p $BACKUP_DIR/configs_backup
        
        # Nginx配置
        if [ -d '/www/server/nginx' ]; then
            cp -r /www/server/nginx/conf/ $BACKUP_DIR/configs_backup/nginx/ 2>/dev/null || true
            cp -r /www/server/panel/vhost/ $BACKUP_DIR/configs_backup/vhost/ 2>/dev/null || true
        elif [ -d '/etc/nginx' ]; then
            cp -r /etc/nginx/ $BACKUP_DIR/configs_backup/nginx/ 2>/dev/null || true
        fi
        
        # MySQL配置
        cp /etc/my.cnf $BACKUP_DIR/configs_backup/ 2>/dev/null || \\
        cp /etc/mysql/my.cnf $BACKUP_DIR/configs_backup/ 2>/dev/null || true
        
        # PHP配置
        if [ -d '/www/server/php' ]; then
            cp -r /www/server/php/*/etc/ $BACKUP_DIR/configs_backup/php/ 2>/dev/null || true
        elif [ -d '/etc/php' ]; then
            cp -r /etc/php/ $BACKUP_DIR/configs_backup/php/ 2>/dev/null || true
        fi
        
        # Redis配置
        cp /www/server/redis/redis.conf $BACKUP_DIR/configs_backup/ 2>/dev/null || \\
        cp /etc/redis/redis.conf $BACKUP_DIR/configs_backup/ 2>/dev/null || true
        
        echo '配置文件备份完成'
    "
    
    update_stage_status "backup" "running" 80
    log_success "配置文件备份完成"
}

# 备份SSL证书
backup_ssl() {
    log_info "备份SSL证书..."
    
    ssh_exec "
        echo '备份SSL证书...'
        mkdir -p $BACKUP_DIR/ssl_certs_backup
        
        # 宝塔面板证书
        if [ -d '/www/server/panel/vhost/cert' ]; then
            cp -r /www/server/panel/vhost/cert/ $BACKUP_DIR/ssl_certs_backup/ 2>/dev/null || true
        fi
        
        # Let's Encrypt证书
        if [ -d '/etc/letsencrypt' ]; then
            cp -r /etc/letsencrypt/ $BACKUP_DIR/ssl_certs_backup/ 2>/dev/null || true
        fi
        
        # 其他SSL证书位置
        if [ -d '/etc/ssl/certs' ]; then
            find /etc/ssl/certs -name '*.crt' -o -name '*.pem' | head -10 | xargs -I {} cp {} $BACKUP_DIR/ssl_certs_backup/ 2>/dev/null || true
        fi
        
        echo 'SSL证书备份完成'
    "
    
    update_stage_status "backup" "running" 90
    log_success "SSL证书备份完成"
}

# 生成备份清单
generate_backup_manifest() {
    log_info "生成备份文件清单..."
    
    ssh_exec "
        echo '生成备份清单...'
        cd $BACKUP_DIR
        echo '备份时间: $(date)' > backup_manifest.txt
        echo '服务器: $(hostname) ($(whoami))' >> backup_manifest.txt
        echo '备份文件列表:' >> backup_manifest.txt
        ls -la *.sql *.tar.gz *.rdb 2>/dev/null >> backup_manifest.txt || true
        echo '配置文件:' >> backup_manifest.txt
        find configs_backup/ -type f 2>/dev/null | head -20 >> backup_manifest.txt || true
        echo '证书文件:' >> backup_manifest.txt
        find ssl_certs_backup/ -type f 2>/dev/null | head -10 >> backup_manifest.txt || true
        echo '备份清单生成完成'
        cat backup_manifest.txt
    "
    
    log_success "备份清单生成完成"
}

# 主执行流程
main() {
    log_info "连接到源服务器: $SOURCE_SERVER_IP"
    
    backup_databases
    backup_redis
    backup_websites
    backup_configs
    backup_ssl
    generate_backup_manifest
    
    update_stage_status "backup" "completed" 100
    
    # 创建回滚点
    create_rollback_point "backup_completed" "源服务器数据备份完成"
    
    log_step_end "源服务器数据备份" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  源服务器备份完成"
    echo "======================="
    echo "所有数据已备份到源服务器的 $BACKUP_DIR 目录"
    echo ""
    echo "下一步执行: ./sh/01_transfer_data.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "源服务器数据备份" "ERROR"; update_stage_status "backup" "failed" 0; exit 1' ERR

main "$@"