#!/bin/bash
# 目标服务器环境搭建脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "目标服务器环境搭建"
update_stage_status "environment" "running" 0

log_info "开始搭建目标服务器环境..."

# 系统更新
update_system() {
    log_info "更新系统包..."
    
    sudo apt update -q
    sudo apt upgrade -y
    sudo apt install -y wget curl vim git unzip software-properties-common
    
    update_stage_status "environment" "running" 10
    log_success "系统更新完成"
}

# 安装Nginx
install_nginx() {
    log_info "安装Nginx..."
    
    if ! command -v nginx >/dev/null; then
        sudo apt install -y nginx
        sudo systemctl enable nginx
        sudo systemctl start nginx
        log_success "Nginx安装完成"
    else
        log_info "Nginx已安装，检查状态"
        sudo systemctl restart nginx
    fi
    
    update_stage_status "environment" "running" 25
}

# 安装MySQL 8.0
install_mysql() {
    log_info "安装MySQL 8.0..."
    
    if ! command -v mysql >/dev/null; then
        # 预配置MySQL安装
        sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password password '
        sudo debconf-set-selections <<< 'mysql-server mysql-server/root_password_again password '
        
        sudo apt install -y mysql-server mysql-client
        sudo systemctl enable mysql
        sudo systemctl start mysql
        
        log_success "MySQL安装完成"
    else
        log_info "MySQL已安装，检查状态"
        sudo systemctl restart mysql
    fi
    
    update_stage_status "environment" "running" 40
}

# 安装Redis
install_redis() {
    log_info "安装Redis..."
    
    if ! command -v redis-server >/dev/null; then
        sudo apt install -y redis-server
        sudo systemctl enable redis-server
        sudo systemctl start redis-server
        log_success "Redis安装完成"
    else
        log_info "Redis已安装，检查状态"
        sudo systemctl restart redis-server
    fi
    
    update_stage_status "environment" "running" 55
}

# 安装PHP 7.4
install_php() {
    log_info "安装PHP 7.4..."
    
    if ! command -v php7.4 >/dev/null; then
        # 添加PHP仓库
        sudo add-apt-repository ppa:ondrej/php -y
        sudo apt update -q
        
        # 安装PHP 7.4及扩展
        sudo apt install -y php7.4 php7.4-fpm php7.4-mysql php7.4-redis php7.4-gd \
            php7.4-mbstring php7.4-xml php7.4-curl php7.4-zip php7.4-json \
            php7.4-bcmath php7.4-tokenizer php7.4-fileinfo php7.4-ctype php7.4-intl
        
        sudo systemctl enable php7.4-fpm
        sudo systemctl start php7.4-fpm
        
        log_success "PHP 7.4安装完成"
    else
        log_info "PHP 7.4已安装，检查状态"
        sudo systemctl restart php7.4-fpm
    fi
    
    update_stage_status "environment" "running" 70
}

# 安装Composer
install_composer() {
    log_info "安装Composer..."
    
    if ! command -v composer >/dev/null; then
        curl -sS https://getcomposer.org/installer | php
        sudo mv composer.phar /usr/local/bin/composer
        sudo chmod +x /usr/local/bin/composer
        log_success "Composer安装完成"
    else
        log_info "Composer已安装"
        composer --version
    fi
    
    update_stage_status "environment" "running" 80
}

# 创建目录结构
create_directories() {
    log_info "创建网站目录结构..."
    
    # 创建网站目录
    sudo mkdir -p "/var/www/$PRIMARY_DOMAIN"
    sudo mkdir -p "/var/www/$VOD_DOMAIN"
    sudo mkdir -p /var/log/nginx/sites
    
    # 设置权限
    sudo chown -R www-data:www-data /var/www/
    sudo chmod -R 755 /var/www/
    
    log_success "目录结构创建完成"
    update_stage_status "environment" "running" 90
}

# 验证安装
verify_installation() {
    log_info "验证环境安装..."
    
    local services=("nginx" "mysql" "redis-server" "php7.4-fpm")
    local failed_services=()
    
    for service in "${services[@]}"; do
        if systemctl is-active --quiet "$service"; then
            log_success "服务运行正常: $service"
        else
            log_error "服务未运行: $service"
            failed_services+=("$service")
        fi
    done
    
    if [[ ${#failed_services[@]} -gt 0 ]]; then
        log_error "以下服务未正常运行: ${failed_services[*]}"
        return 1
    fi
    
    # 检查端口监听
    local ports=("80:nginx" "443:nginx" "3306:mysql" "6379:redis")
    for port_info in "${ports[@]}"; do
        local port="${port_info%:*}"
        local service="${port_info#*:}"
        
        if netstat -tlnp | grep -q ":$port "; then
            log_success "端口监听正常: $port ($service)"
        else
            log_warn "端口未监听: $port ($service)"
        fi
    done
    
    log_success "环境验证完成"
}

# 主执行流程
main() {
    update_system
    install_nginx
    install_mysql
    install_redis
    install_php
    install_composer
    create_directories
    
    update_stage_status "environment" "running" 95
    
    verify_installation
    
    update_stage_status "environment" "completed" 100
    
    # 创建回滚点
    create_rollback_point "environment_setup" "目标服务器环境搭建完成"
    
    log_step_end "目标服务器环境搭建" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  环境搭建完成"
    echo "======================="
    echo "已安装的服务:"
    echo "- Nginx (Web服务器)"
    echo "- MySQL 8.0 (数据库)"
    echo "- Redis (缓存)"
    echo "- PHP 7.4 + FPM (应用运行环境)"
    echo "- Composer (PHP包管理器)"
    echo ""
    echo "下一步执行: ./sh/03_deploy_apps.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "目标服务器环境搭建" "ERROR"; update_stage_status "environment" "failed" 0; exit 1' ERR

main "$@"