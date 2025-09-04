#!/bin/bash
# Nginx虚拟主机配置脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "Nginx虚拟主机配置"
update_stage_status "configuration" "running" 0

log_info "开始配置Nginx虚拟主机..."

# 备份现有Nginx配置
backup_nginx_config() {
    log_info "备份现有Nginx配置..."
    
    local backup_dir="./backups/nginx_$(date +%Y%m%d_%H%M%S)"
    mkdir -p "$backup_dir"
    
    sudo cp -r /etc/nginx/ "$backup_dir/" 2>/dev/null || {
        log_warn "Nginx配置备份失败"
    }
    
    log_success "Nginx配置已备份到: $backup_dir"
    update_stage_status "configuration" "running" 10
}

# 配置主站点
configure_primary_site() {
    log_info "配置主站点: $PRIMARY_DOMAIN"
    
    sudo tee "/etc/nginx/sites-available/$PRIMARY_DOMAIN" << EOF
# HTTP服务器 - 重定向到HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name $PRIMARY_DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

# HTTPS服务器
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $PRIMARY_DOMAIN;
    
    root /var/www/$PRIMARY_DOMAIN/public;
    index index.php index.html index.htm;
    
    # SSL配置 (将在SSL配置阶段启用)
    # ssl_certificate /etc/ssl/certs/$PRIMARY_DOMAIN.crt;
    # ssl_certificate_key /etc/ssl/private/$PRIMARY_DOMAIN.key;
    
    # SSL安全配置
    # ssl_protocols TLSv1.2 TLSv1.3;
    # ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
    # ssl_prefer_server_ciphers off;
    
    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # PHP处理
    location ~ \\.php\$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
    
    # ThinkPHP URL重写
    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }
    
    # 禁止访问敏感文件
    location ~ ^/(\\.user\\.ini|\\.htaccess|\\.git|\\.env|\\.svn|LICENSE|README\\.md|composer\\.(json|lock)|package\\.json)\$ {
        deny all;
        return 404;
    }
    
    # 静态文件缓存
    location ~* \\.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|tar|zip)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary "Accept-Encoding";
        access_log off;
    }
    
    # 字体文件
    location ~* \\.(woff|woff2|ttf|eot)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
    }
    
    # Gzip压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1000;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml application/xml+rss text/javascript;
    
    # 日志
    access_log /var/log/nginx/sites/$PRIMARY_DOMAIN.access.log;
    error_log /var/log/nginx/sites/$PRIMARY_DOMAIN.error.log;
    
    # 上传大小限制
    client_max_body_size 100M;
    
    # 超时配置
    proxy_connect_timeout 60s;
    proxy_send_timeout 60s;
    proxy_read_timeout 60s;
}
EOF
    
    # 启用站点
    sudo ln -sf "/etc/nginx/sites-available/$PRIMARY_DOMAIN" "/etc/nginx/sites-enabled/"
    
    log_success "主站点配置完成"
    update_stage_status "configuration" "running" 40
}

# 配置点播站点
configure_vod_site() {
    log_info "配置点播站点: $VOD_DOMAIN"
    
    sudo tee "/etc/nginx/sites-available/$VOD_DOMAIN" << EOF
# HTTP服务器 - 重定向到HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name $VOD_DOMAIN;
    return 301 https://\$server_name\$request_uri;
}

# HTTPS服务器
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $VOD_DOMAIN;
    
    root /var/www/$VOD_DOMAIN;
    index index.php index.html index.htm;
    
    # SSL配置 (将在SSL配置阶段启用)
    # ssl_certificate /etc/ssl/certs/$VOD_DOMAIN.crt;
    # ssl_certificate_key /etc/ssl/private/$VOD_DOMAIN.key;
    
    # PHP处理
    location ~ \\.php\$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    
    # URL重写
    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }
    
    # 静态文件缓存
    location ~* \\.(jpg|jpeg|png|gif|ico|css|js|mp4|avi|mov|wmv|flv)\$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # 日志
    access_log /var/log/nginx/sites/$VOD_DOMAIN.access.log;
    error_log /var/log/nginx/sites/$VOD_DOMAIN.error.log;
    
    # 上传大小限制
    client_max_body_size 500M;
}
EOF
    
    # 启用站点
    sudo ln -sf "/etc/nginx/sites-available/$VOD_DOMAIN" "/etc/nginx/sites-enabled/"
    
    log_success "点播站点配置完成"
    update_stage_status "configuration" "running" 60
}

# 禁用默认站点
disable_default_site() {
    log_info "禁用Nginx默认站点..."
    
    sudo rm -f /etc/nginx/sites-enabled/default
    
    log_success "默认站点已禁用"
    update_stage_status "configuration" "running" 70
}

# 优化Nginx主配置
optimize_nginx_config() {
    log_info "优化Nginx主配置..."
    
    # 备份原配置
    sudo cp /etc/nginx/nginx.conf /etc/nginx/nginx.conf.backup
    
    # 更新nginx.conf
    sudo tee /etc/nginx/nginx.conf << 'EOF'
user www-data;
worker_processes auto;
pid /run/nginx.pid;

events {
    worker_connections 2048;
    use epoll;
    multi_accept on;
}

http {
    # 基本设置
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;
    
    # MIME类型
    include /etc/nginx/mime.types;
    default_type application/octet-stream;
    
    # 日志格式
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                   '$status $body_bytes_sent "$http_referer" '
                   '"$http_user_agent" "$http_x_forwarded_for"';
    
    # 日志配置
    access_log /var/log/nginx/access.log main;
    error_log /var/log/nginx/error.log;
    
    # Gzip压缩
    gzip on;
    gzip_vary on;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
    
    # 缓冲区设置
    client_body_buffer_size 128k;
    client_header_buffer_size 1k;
    client_max_body_size 100M;
    large_client_header_buffers 4 4k;
    
    # 超时设置
    client_body_timeout 12;
    client_header_timeout 12;
    keepalive_timeout 15;
    send_timeout 10;
    
    # 包含站点配置
    include /etc/nginx/conf.d/*.conf;
    include /etc/nginx/sites-enabled/*;
}
EOF
    
    log_success "Nginx主配置优化完成"
    update_stage_status "configuration" "running" 85
}

# 测试Nginx配置
test_nginx_config() {
    log_info "测试Nginx配置..."
    
    if sudo nginx -t; then
        log_success "Nginx配置测试通过"
    else
        log_error "Nginx配置测试失败"
        return 1
    fi
    
    # 重启Nginx
    log_info "重启Nginx服务..."
    sudo systemctl restart nginx
    
    if systemctl is-active --quiet nginx; then
        log_success "Nginx重启成功"
    else
        log_error "Nginx重启失败"
        return 1
    fi
    
    update_stage_status "configuration" "running" 95
}

# 创建临时测试页面
create_test_pages() {
    log_info "创建临时测试页面..."
    
    # 主站点测试页面
    if [[ ! -f "/var/www/$PRIMARY_DOMAIN/public/index.php" ]] && [[ ! -f "/var/www/$PRIMARY_DOMAIN/index.php" ]]; then
        sudo mkdir -p "/var/www/$PRIMARY_DOMAIN/public"
        sudo tee "/var/www/$PRIMARY_DOMAIN/public/index.php" << EOF
<?php
echo "<h1>$PRIMARY_DOMAIN - 站点正常运行</h1>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>服务器时间: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>迁移状态: 临时测试页面</p>";
phpinfo();
?>
EOF
        sudo chown www-data:www-data "/var/www/$PRIMARY_DOMAIN/public/index.php"
    fi
    
    # 点播站点测试页面
    if [[ ! -f "/var/www/$VOD_DOMAIN/index.php" ]]; then
        sudo tee "/var/www/$VOD_DOMAIN/index.php" << EOF
<?php
echo "<h1>$VOD_DOMAIN - 点播站点正常运行</h1>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>服务器时间: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>迁移状态: 临时测试页面</p>";
?>
EOF
        sudo chown www-data:www-data "/var/www/$VOD_DOMAIN/index.php"
    fi
    
    log_success "测试页面创建完成"
}

# 主执行流程
main() {
    backup_nginx_config
    configure_primary_site
    configure_vod_site
    disable_default_site
    optimize_nginx_config
    test_nginx_config
    create_test_pages
    
    update_stage_status "configuration" "completed" 100
    
    # 创建回滚点
    create_rollback_point "nginx_configured" "Nginx虚拟主机配置完成"
    
    log_step_end "Nginx虚拟主机配置" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  Nginx配置完成"
    echo "======================="
    echo "已配置的站点:"
    echo "- $PRIMARY_DOMAIN (主站点)"
    echo "- $VOD_DOMAIN (点播站点)"
    echo ""
    echo "临时测试访问:"
    echo "- http://$PRIMARY_DOMAIN (将重定向到HTTPS)"
    echo "- http://$VOD_DOMAIN (将重定向到HTTPS)"
    echo ""
    echo "注意: SSL证书尚未配置，HTTPS暂时不可用"
    echo ""
    echo "下一步执行: ./sh/05_setup_ssl.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "Nginx虚拟主机配置" "ERROR"; update_stage_status "configuration" "failed" 0; exit 1' ERR

main "$@"