#!/bin/bash
# 验证和检查工具

source "./sh/utils/logger.sh"
source "./sh/config/servers.conf"

# 验证网络连接
validate_connectivity() {
    local target="$1"
    local port="${2:-80}"
    
    if timeout 10 bash -c "echo >/dev/tcp/$target/$port" 2>/dev/null; then
        return 0
    else
        return 1
    fi
}

# 验证服务状态
validate_service() {
    local service="$1"
    
    if systemctl is-active --quiet "$service"; then
        log_success "服务运行正常: $service"
        return 0
    else
        log_error "服务未运行: $service"
        return 1
    fi
}

# 验证数据库连接
validate_database() {
    local db_name="$1"
    local db_user="${2:-$DB_USER}"
    local db_pass="${3:-$DB_PASS}"
    
    if mysql -u "$db_user" -p"$db_pass" -e "USE $db_name; SELECT 1;" >/dev/null 2>&1; then
        log_success "数据库连接正常: $db_name"
        return 0
    else
        log_error "数据库连接失败: $db_name"
        return 1
    fi
}

# 验证网站访问
validate_website() {
    local domain="$1"
    local expected_status="${2:-200}"
    
    local actual_status=$(curl -s -o /dev/null -w "%{http_code}" "http://$domain" || echo "000")
    
    if [[ "$actual_status" == "$expected_status" ]] || [[ "$actual_status" == "301" ]] || [[ "$actual_status" == "302" ]]; then
        log_success "网站访问正常: $domain (HTTP $actual_status)"
        return 0
    else
        log_error "网站访问异常: $domain (HTTP $actual_status)"
        return 1
    fi
}

# 验证SSL证书
validate_ssl() {
    local domain="$1"
    
    if echo | openssl s_client -connect "$domain:443" -servername "$domain" 2>/dev/null | openssl x509 -noout -dates >/dev/null 2>&1; then
        log_success "SSL证书正常: $domain"
        return 0
    else
        log_error "SSL证书异常: $domain"
        return 1
    fi
}

# 综合验证函数
run_validation() {
    local validation_type="$1"
    
    case "$validation_type" in
        "services")
            log_info "验证系统服务..."
            validate_service "nginx" && \
            validate_service "mysql" && \
            validate_service "redis-server" && \
            validate_service "php7.4-fpm"
            ;;
        "database")
            log_info "验证数据库连接..."
            validate_database "$DB_NAME"
            ;;
        "websites")
            log_info "验证网站访问..."
            validate_website "$PRIMARY_DOMAIN" && \
            validate_website "$VOD_DOMAIN"
            ;;
        "ssl")
            log_info "验证SSL证书..."
            validate_ssl "$PRIMARY_DOMAIN" && \
            validate_ssl "$VOD_DOMAIN"
            ;;
        "all")
            log_info "执行完整验证..."
            run_validation "services" && \
            run_validation "database" && \
            run_validation "websites" && \
            run_validation "ssl"
            ;;
        *)
            log_error "未知验证类型: $validation_type"
            echo "用法: $0 {services|database|websites|ssl|all}"
            exit 1
            ;;
    esac
}

# 如果直接运行此脚本
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    run_validation "${1:-all}"
fi