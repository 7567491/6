#!/bin/bash
# SSL证书配置脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "SSL证书配置"
update_stage_status "ssl_setup" "running" 0

log_info "开始SSL证书配置..."

# 安装Certbot
install_certbot() {
    log_info "安装Certbot..."
    
    if ! command -v certbot >/dev/null; then
        sudo apt update -q
        sudo apt install -y certbot python3-certbot-nginx
        log_success "Certbot安装完成"
    else
        log_info "Certbot已安装"
    fi
    
    update_stage_status "ssl_setup" "running" 20
}

# 临时禁用SSL重定向
disable_ssl_redirect() {
    log_info "临时禁用SSL重定向以申请证书..."
    
    # 创建临时HTTP-only配置
    sudo tee "/etc/nginx/sites-available/$PRIMARY_DOMAIN.temp" << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $PRIMARY_DOMAIN;
    
    root /var/www/$PRIMARY_DOMAIN/public;
    index index.php index.html index.htm;
    
    # 允许Let's Encrypt验证
    location /.well-known/acme-challenge/ {
        root /var/www/$PRIMARY_DOMAIN/public;
        allow all;
    }
    
    location ~ \\.php\$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }
}
EOF

    sudo tee "/etc/nginx/sites-available/$VOD_DOMAIN.temp" << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $VOD_DOMAIN;
    
    root /var/www/$VOD_DOMAIN;
    index index.php index.html index.htm;
    
    location /.well-known/acme-challenge/ {
        root /var/www/$VOD_DOMAIN;
        allow all;
    }
    
    location ~ \\.php\$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location / {
        try_files \$uri \$uri/ /index.php\$is_args\$args;
    }
}
EOF
    
    # 启用临时配置
    sudo ln -sf "/etc/nginx/sites-available/$PRIMARY_DOMAIN.temp" "/etc/nginx/sites-enabled/"
    sudo ln -sf "/etc/nginx/sites-available/$VOD_DOMAIN.temp" "/etc/nginx/sites-enabled/"
    
    sudo nginx -t && sudo systemctl reload nginx
    
    log_success "临时HTTP配置已启用"
    update_stage_status "ssl_setup" "running" 30
}

# 检查DNS解析
check_dns_resolution() {
    log_info "检查DNS解析..."
    
    local domains=("$PRIMARY_DOMAIN" "$VOD_DOMAIN")
    local failed_domains=()
    
    for domain in "${domains[@]}"; do
        log_info "检查域名解析: $domain"
        
        # 检查域名是否解析到当前服务器
        local resolved_ip=$(dig +short "$domain" 2>/dev/null | head -1)
        
        if [[ -n "$resolved_ip" ]]; then
            if [[ "$resolved_ip" == "$TARGET_SERVER_IP" ]]; then
                log_success "域名解析正确: $domain -> $resolved_ip"
            else
                log_warn "域名解析不匹配: $domain -> $resolved_ip (期望: $TARGET_SERVER_IP)"
                log_warn "SSL证书申请可能失败，建议先更新DNS"
            fi
        else
            log_warn "域名无法解析: $domain"
            failed_domains+=("$domain")
        fi
    done
    
    if [[ ${#failed_domains[@]} -gt 0 ]]; then
        log_warn "以下域名无法解析: ${failed_domains[*]}"
        log_info "可以继续尝试申请证书，但可能会失败"
        
        read -p "是否继续SSL证书申请? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            log_info "SSL证书申请已取消"
            return 1
        fi
    fi
    
    update_stage_status "ssl_setup" "running" 45
}

# 申请SSL证书
request_ssl_certificates() {
    log_info "申请SSL证书..."
    
    local domains=("$PRIMARY_DOMAIN" "$VOD_DOMAIN")
    local successful_domains=()
    local failed_domains=()
    
    for domain in "${domains[@]}"; do
        log_info "为域名申请证书: $domain"
        
        # 使用standalone模式申请证书
        if sudo certbot certonly \
            --webroot \
            --webroot-path="/var/www/$domain/public" \
            --email "admin@$domain" \
            --agree-tos \
            --no-eff-email \
            --domains "$domain" \
            --non-interactive; then
            
            log_success "证书申请成功: $domain"
            successful_domains+=("$domain")
        else
            log_error "证书申请失败: $domain"
            failed_domains+=("$domain")
            
            # 尝试使用nginx插件申请
            log_info "尝试使用nginx插件申请: $domain"
            if sudo certbot --nginx -d "$domain" --non-interactive --agree-tos --email "admin@$domain"; then
                log_success "使用nginx插件申请成功: $domain"
                successful_domains+=("$domain")
                failed_domains=("${failed_domains[@]/$domain}")
            fi
        fi
    done
    
    log_info "SSL证书申请结果:"
    log_info "成功: ${successful_domains[*]}"
    [[ ${#failed_domains[@]} -gt 0 ]] && log_warn "失败: ${failed_domains[*]}"
    
    update_stage_status "ssl_setup" "running" 70
}

# 配置SSL
configure_ssl_nginx() {
    log_info "配置Nginx SSL设置..."
    
    # 恢复原始配置并添加SSL
    sudo tee "/etc/nginx/sites-available/$PRIMARY_DOMAIN" << EOF
# HTTP服务器 - 重定向到HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name $PRIMARY_DOMAIN;
    
    # Let's Encrypt验证
    location /.well-known/acme-challenge/ {
        root /var/www/$PRIMARY_DOMAIN/public;
        allow all;
    }
    
    # 重定向到HTTPS
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}

# HTTPS服务器
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $PRIMARY_DOMAIN;
    
    root /var/www/$PRIMARY_DOMAIN/public;
    index index.php index.html index.htm;
EOF

    # 检查证书是否存在并添加SSL配置
    if [[ -f "/etc/letsencrypt/live/$PRIMARY_DOMAIN/fullchain.pem" ]]; then
        sudo tee -a "/etc/nginx/sites-available/$PRIMARY_DOMAIN" << EOF
    
    # SSL证书配置
    ssl_certificate /etc/letsencrypt/live/$PRIMARY_DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$PRIMARY_DOMAIN/privkey.pem;
    
    # SSL安全配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-RSA-CHACHA20-POLY1305;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
EOF
        log_success "SSL证书配置已添加: $PRIMARY_DOMAIN"
    else
        log_warn "证书文件不存在，跳过SSL配置: $PRIMARY_DOMAIN"
    fi

    # 添加其余配置
    sudo tee -a "/etc/nginx/sites-available/$PRIMARY_DOMAIN" << 'EOF'
    
    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # PHP处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
    }
    
    # ThinkPHP URL重写
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    # 禁止访问敏感文件
    location ~ ^/(\.user\.ini|\.htaccess|\.git|\.env|\.svn|LICENSE|README\.md)$ {
        deny all;
        return 404;
    }
    
    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|tar|zip)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # 日志
    access_log /var/log/nginx/sites/$PRIMARY_DOMAIN.access.log;
    error_log /var/log/nginx/sites/$PRIMARY_DOMAIN.error.log;
    
    client_max_body_size 100M;
}
EOF

    # 配置VOD域名
    sudo tee "/etc/nginx/sites-available/$VOD_DOMAIN" << EOF
server {
    listen 80;
    listen [::]:80;
    server_name $VOD_DOMAIN;
    
    location /.well-known/acme-challenge/ {
        root /var/www/$VOD_DOMAIN;
        allow all;
    }
    
    location / {
        return 301 https://\$server_name\$request_uri;
    }
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name $VOD_DOMAIN;
    
    root /var/www/$VOD_DOMAIN;
    index index.php index.html index.htm;
EOF

    if [[ -f "/etc/letsencrypt/live/$VOD_DOMAIN/fullchain.pem" ]]; then
        sudo tee -a "/etc/nginx/sites-available/$VOD_DOMAIN" << EOF
    
    ssl_certificate /etc/letsencrypt/live/$VOD_DOMAIN/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/$VOD_DOMAIN/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES128-GCM-SHA256:ECDHE-RSA-AES256-GCM-SHA384;
EOF
        log_success "SSL证书配置已添加: $VOD_DOMAIN"
    else
        log_warn "证书文件不存在，跳过SSL配置: $VOD_DOMAIN"
    fi

    sudo tee -a "/etc/nginx/sites-available/$VOD_DOMAIN" << 'EOF'
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }
    
    access_log /var/log/nginx/sites/$VOD_DOMAIN.access.log;
    error_log /var/log/nginx/sites/$VOD_DOMAIN.error.log;
    
    client_max_body_size 500M;
}
EOF

    # 启用新配置
    sudo ln -sf "/etc/nginx/sites-available/$PRIMARY_DOMAIN" "/etc/nginx/sites-enabled/"
    sudo ln -sf "/etc/nginx/sites-available/$VOD_DOMAIN" "/etc/nginx/sites-enabled/"
    
    # 删除临时配置
    sudo rm -f "/etc/nginx/sites-enabled/$PRIMARY_DOMAIN.temp"
    sudo rm -f "/etc/nginx/sites-enabled/$VOD_DOMAIN.temp"
    
    update_stage_status "ssl_setup" "running" 85
}

# 设置自动续期
setup_auto_renewal() {
    log_info "设置SSL证书自动续期..."
    
    # 添加到crontab
    (crontab -l 2>/dev/null | grep -v "certbot renew"; echo "0 2 * * * /usr/bin/certbot renew --quiet --post-hook 'systemctl reload nginx'") | crontab -
    
    log_success "SSL证书自动续期已设置"
    update_stage_status "ssl_setup" "running" 90
}

# 测试SSL配置
test_ssl_config() {
    log_info "测试SSL配置..."
    
    # 测试Nginx配置
    if sudo nginx -t; then
        log_success "Nginx配置测试通过"
    else
        log_error "Nginx配置测试失败"
        return 1
    fi
    
    # 重启Nginx
    sudo systemctl reload nginx
    
    # 测试SSL连接
    local domains=("$PRIMARY_DOMAIN" "$VOD_DOMAIN")
    
    for domain in "${domains[@]}"; do
        log_info "测试SSL连接: $domain"
        
        if echo | timeout 10 openssl s_client -connect "$domain:443" -servername "$domain" >/dev/null 2>&1; then
            log_success "SSL连接测试通过: $domain"
        else
            log_warn "SSL连接测试失败: $domain"
        fi
    done
    
    update_stage_status "ssl_setup" "running" 95
}

# 主执行流程
main() {
    install_certbot
    disable_ssl_redirect
    
    # DNS检查（可选，允许跳过）
    check_dns_resolution || {
        log_warn "DNS检查失败，但继续执行"
    }
    
    request_ssl_certificates
    configure_ssl_nginx
    setup_auto_renewal
    test_ssl_config
    
    update_stage_status "ssl_setup" "completed" 100
    
    # 创建回滚点
    create_rollback_point "ssl_configured" "SSL证书配置完成"
    
    log_step_end "SSL证书配置" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  SSL证书配置完成"
    echo "======================="
    echo "SSL证书状态:"
    echo "- $PRIMARY_DOMAIN: $([ -f "/etc/letsencrypt/live/$PRIMARY_DOMAIN/fullchain.pem" ] && echo "✓ 已配置" || echo "✗ 未配置")"
    echo "- $VOD_DOMAIN: $([ -f "/etc/letsencrypt/live/$VOD_DOMAIN/fullchain.pem" ] && echo "✓ 已配置" || echo "✗ 未配置")"
    echo ""
    echo "HTTPS访问测试:"
    echo "- https://$PRIMARY_DOMAIN"
    echo "- https://$VOD_DOMAIN"
    echo ""
    echo "自动续期已设置，证书将在到期前自动更新"
    echo ""
    echo "下一步执行: ./sh/06_optimize_services.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "SSL证书配置" "ERROR"; update_stage_status "ssl_setup" "failed" 0; exit 1' ERR

main "$@"