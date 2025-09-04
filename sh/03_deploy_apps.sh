#!/bin/bash
# 应用部署和配置脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "应用部署和配置"
update_stage_status "deployment" "running" 0

log_info "开始应用部署和配置..."

# 恢复网站文件
restore_websites() {
    log_info "恢复网站文件..."
    
    cd "$TRANSFER_DIR"
    
    # 解压网站文件
    if [[ -f "websites_backup.tar.gz" ]]; then
        log_info "解压主要网站文件..."
        sudo tar -xzf "websites_backup.tar.gz" -C /var/www/
        
        # 重命名目录以匹配新域名
        if [[ -d "/var/www/www.6page.cn" ]]; then
            sudo mv "/var/www/www.6page.cn/"* "/var/www/$PRIMARY_DOMAIN/" 2>/dev/null || true
            sudo rmdir "/var/www/www.6page.cn" 2>/dev/null || true
        fi
        
        if [[ -d "/var/www/dianbo.6page.cn" ]]; then
            sudo mv "/var/www/dianbo.6page.cn/"* "/var/www/$VOD_DOMAIN/" 2>/dev/null || true
            sudo rmdir "/var/www/dianbo.6page.cn" 2>/dev/null || true
        fi
        
        log_success "网站文件恢复完成"
    else
        log_warn "网站备份文件不存在，跳过"
    fi
    
    # 恢复上传文件和缓存
    if [[ -f "uploads_backup.tar.gz" ]]; then
        log_info "恢复上传文件和缓存..."
        sudo tar -xzf "uploads_backup.tar.gz" -C "/var/www/$PRIMARY_DOMAIN/" 2>/dev/null || true
        log_success "上传文件恢复完成"
    fi
    
    update_stage_status "deployment" "running" 25
}

# 配置MySQL
configure_mysql() {
    log_info "配置MySQL数据库..."
    
    # 设置root密码（如果需要）
    sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '';" 2>/dev/null || true
    
    # 创建数据库和用户
    sudo mysql -e "
        CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
        GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
        FLUSH PRIVILEGES;
    " 2>/dev/null || {
        log_warn "数据库创建可能失败，继续尝试恢复数据"
    }
    
    # 恢复数据库
    if [[ -f "$TRANSFER_DIR/6page_database.sql" ]]; then
        log_info "恢复6page数据库..."
        mysql -u root < "$TRANSFER_DIR/6page_database.sql" || {
            log_warn "6page数据库恢复失败，尝试其他数据库文件"
        }
    fi
    
    if [[ -f "$TRANSFER_DIR/all_databases.sql" ]]; then
        log_info "恢复所有数据库..."
        mysql -u root < "$TRANSFER_DIR/all_databases.sql" || {
            log_warn "所有数据库恢复失败"
        }
    fi
    
    update_stage_status "deployment" "running" 50
    log_success "MySQL配置完成"
}

# 配置Redis
configure_redis() {
    log_info "配置Redis..."
    
    # 停止Redis服务
    sudo systemctl stop redis-server
    
    # 恢复Redis数据
    if [[ -f "$TRANSFER_DIR/redis_backup.rdb" ]]; then
        log_info "恢复Redis数据..."
        sudo cp "$TRANSFER_DIR/redis_backup.rdb" /var/lib/redis/dump.rdb
        sudo chown redis:redis /var/lib/redis/dump.rdb
        log_success "Redis数据恢复完成"
    else
        log_warn "Redis备份文件不存在，跳过"
    fi
    
    # 启动Redis服务
    sudo systemctl start redis-server
    
    update_stage_status "deployment" "running" 60
    log_success "Redis配置完成"
}

# 配置PHP应用
configure_php_app() {
    log_info "配置PHP应用..."
    
    local app_dir="/var/www/$PRIMARY_DOMAIN"
    
    if [[ -d "$app_dir" ]]; then
        cd "$app_dir"
        
        # 安装Composer依赖（如果存在composer.json）
        if [[ -f "composer.json" ]]; then
            log_info "安装Composer依赖..."
            sudo -u www-data composer install --no-dev --optimize-autoloader 2>/dev/null || {
                log_warn "Composer依赖安装失败，跳过"
            }
        fi
        
        # 设置目录权限
        log_info "设置应用目录权限..."
        sudo chown -R www-data:www-data "$app_dir"
        sudo chmod -R 755 "$app_dir"
        
        # 设置可写目录权限
        if [[ -d "runtime" ]]; then
            sudo chmod -R 777 "$app_dir/runtime/"
        fi
        
        if [[ -d "public/uploads" ]]; then
            sudo chmod -R 777 "$app_dir/public/uploads/"
        fi
        
        # 更新应用配置中的数据库连接
        update_app_config
        
        log_success "PHP应用配置完成"
    else
        log_warn "应用目录不存在: $app_dir"
    fi
    
    update_stage_status "deployment" "running" 80
}

# 更新应用配置
update_app_config() {
    log_info "更新应用配置文件..."
    
    local app_dir="/var/www/$PRIMARY_DOMAIN"
    local config_files=(
        "application/database.php"
        "config/database.php"
        ".env"
        "config.php"
    )
    
    for config_file in "${config_files[@]}"; do
        local full_path="$app_dir/$config_file"
        
        if [[ -f "$full_path" ]]; then
            log_info "更新配置文件: $config_file"
            
            # 备份原配置文件
            sudo cp "$full_path" "$full_path.backup"
            
            # 更新数据库配置
            sudo sed -i "s/127\.0\.0\.1/127.0.0.1/g" "$full_path"
            sudo sed -i "s/'hostname'[[:space:]]*=>[[:space:]]*'[^']*'/'hostname' => '127.0.0.1'/g" "$full_path"
            sudo sed -i "s/'database'[[:space:]]*=>[[:space:]]*'[^']*'/'database' => '$DB_NAME'/g" "$full_path"
            sudo sed -i "s/'username'[[:space:]]*=>[[:space:]]*'[^']*'/'username' => '$DB_USER'/g" "$full_path"
            sudo sed -i "s/'password'[[:space:]]*=>[[:space:]]*'[^']*'/'password' => '$DB_PASS'/g" "$full_path"
            
            # 更新域名配置
            sudo sed -i "s/www\.6page\.cn/$PRIMARY_DOMAIN/g" "$full_path"
            sudo sed -i "s/dianbo\.6page\.cn/$VOD_DOMAIN/g" "$full_path"
            
            log_success "配置文件更新完成: $config_file"
        fi
    done
}

# 测试应用连接
test_app_connections() {
    log_info "测试应用连接..."
    
    # 测试数据库连接
    if mysql -u "$DB_USER" -p"$DB_PASS" -e "USE $DB_NAME; SELECT 1;" >/dev/null 2>&1; then
        log_success "数据库连接测试通过"
    else
        log_error "数据库连接测试失败"
        return 1
    fi
    
    # 测试Redis连接
    if redis-cli ping >/dev/null 2>&1; then
        log_success "Redis连接测试通过"
    else
        log_warn "Redis连接测试失败"
    fi
    
    # 测试PHP配置
    if php7.4 -v >/dev/null 2>&1; then
        log_success "PHP配置测试通过"
    else
        log_error "PHP配置测试失败"
        return 1
    fi
    
    update_stage_status "deployment" "running" 95
}

# 主执行流程
main() {
    restore_websites
    configure_mysql
    configure_redis
    configure_php_app
    test_app_connections
    
    update_stage_status "deployment" "completed" 100
    
    # 创建回滚点
    create_rollback_point "deployment_completed" "应用部署和配置完成"
    
    log_step_end "应用部署和配置" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  应用部署完成"
    echo "======================="
    echo "已完成的配置:"
    echo "- 网站文件恢复到 /var/www/"
    echo "- 数据库恢复和用户创建"
    echo "- Redis数据恢复"
    echo "- PHP应用配置更新"
    echo "- 目录权限设置"
    echo ""
    echo "下一步执行: ./sh/04_configure_nginx.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "应用部署和配置" "ERROR"; update_stage_status "deployment" "failed" 0; exit 1' ERR

main "$@"