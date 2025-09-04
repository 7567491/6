#!/bin/bash
# 初始化环境和检查脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "环境初始化检查"

# 初始化状态跟踪
init_status

log_info "开始环境初始化和检查..."

# 检查必要的命令
check_commands() {
    local commands=("scp" "ssh" "rsync")
    local missing=()
    
    for cmd in "${commands[@]}"; do
        if ! command -v "$cmd" >/dev/null 2>&1; then
            missing+=("$cmd")
        fi
    done
    
    if [[ ${#missing[@]} -gt 0 ]]; then
        log_error "缺少必要命令: ${missing[*]}"
        echo "请先安装缺少的命令"
        exit 1
    fi
    
    # 检查可选命令（后续安装）
    local optional_commands=("mysql" "nginx" "php" "redis-cli")
    for cmd in "${optional_commands[@]}"; do
        if command -v "$cmd" >/dev/null 2>&1; then
            log_info "命令已安装: $cmd"
        else
            log_info "命令将在后续安装: $cmd"
        fi
    done
    
    log_success "基础命令检查通过"
}

# 检查网络连通性
check_connectivity() {
    log_info "检查与源服务器的网络连通性..."
    
    if ping -c 3 "$SOURCE_SERVER_IP" >/dev/null 2>&1; then
        log_success "源服务器网络连通性正常"
    else
        log_error "无法连接到源服务器 $SOURCE_SERVER_IP"
        exit 1
    fi
    
    # 检查SSH连接
    if sshpass -p "$SOURCE_SERVER_PASS" ssh -o ConnectTimeout=10 -o StrictHostKeyChecking=no \
       "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "echo 'SSH连接测试成功'" 2>/dev/null; then
        log_success "SSH连接测试成功"
    else
        log_error "SSH连接失败，请检查用户名密码"
        exit 1
    fi
}

# 检查本地权限
check_permissions() {
    log_info "检查本地权限..."
    
    # 检查sudo权限
    if sudo -n true 2>/dev/null; then
        log_success "sudo权限检查通过"
    else
        log_error "需要sudo权限"
        exit 1
    fi
    
    # 检查目录权限
    local dirs=("./logs" "./snapshots" "./backups" "/tmp")
    for dir in "${dirs[@]}"; do
        if [[ -w "$dir" ]] || mkdir -p "$dir" 2>/dev/null; then
            log_info "目录权限正常: $dir"
        else
            log_error "目录权限不足: $dir"
            exit 1
        fi
    done
}

# 检查磁盘空间
check_disk_space() {
    log_info "检查磁盘空间..."
    
    local available=$(df /home/6page | awk 'NR==2 {print $4}')
    local required=$((10 * 1024 * 1024))  # 10GB in KB
    
    if [[ $available -gt $required ]]; then
        log_success "磁盘空间充足 ($(( available / 1024 / 1024 ))GB 可用)"
    else
        log_warn "磁盘空间不足 (仅$(( available / 1024 / 1024 ))GB 可用，建议至少10GB)"
    fi
}

# 安装必要的软件包
install_dependencies() {
    log_info "安装必要的依赖包..."
    
    local packages=("sshpass" "jq" "pv" "htop" "curl" "wget")
    local to_install=()
    
    for pkg in "${packages[@]}"; do
        if ! dpkg -l | grep -q "^ii  $pkg "; then
            to_install+=("$pkg")
        fi
    done
    
    if [[ ${#to_install[@]} -gt 0 ]]; then
        log_info "安装软件包: ${to_install[*]}"
        sudo apt update -q
        sudo apt install -y "${to_install[@]}"
        log_success "依赖包安装完成"
    else
        log_success "所有依赖包已安装"
    fi
}

# 创建配置快照
create_config_snapshot() {
    local snapshot_file="./snapshots/config_$(date +%Y%m%d_%H%M%S).json"
    
    log_info "创建配置快照: $snapshot_file"
    
    cat > "$snapshot_file" <<EOF
{
    "timestamp": "$(date -Iseconds)",
    "migration_id": "$(date +%Y%m%d_%H%M%S)",
    "source_server": {
        "ip": "$SOURCE_SERVER_IP",
        "user": "$SOURCE_SERVER_USER"
    },
    "target_server": {
        "ip": "$TARGET_SERVER_IP",
        "user": "$TARGET_SERVER_USER",
        "home": "$TARGET_SERVER_HOME"
    },
    "domains": {
        "primary": "$PRIMARY_DOMAIN",
        "vod": "$VOD_DOMAIN"
    },
    "system_info": {
        "os": "$(lsb_release -d 2>/dev/null | cut -f2 || echo 'Unknown')",
        "kernel": "$(uname -r)",
        "arch": "$(uname -m)",
        "user": "$(whoami)",
        "hostname": "$(hostname)"
    }
}
EOF
    
    log_success "配置快照已创建"
}

# 主执行流程
main() {
    update_stage_status "initialization" "running" 0
    
    check_commands
    update_stage_status "initialization" "running" 20
    
    install_dependencies
    update_stage_status "initialization" "running" 40
    
    check_permissions
    update_stage_status "initialization" "running" 60
    
    check_connectivity
    update_stage_status "initialization" "running" 80
    
    check_disk_space
    create_config_snapshot
    update_stage_status "initialization" "running" 100
    
    # 创建初始回滚点
    create_rollback_point "initialization" "环境初始化完成"
    
    update_stage_status "initialization" "completed" 100
    log_step_end "环境初始化检查" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "  环境初始化检查完成"
    echo "======================="
    echo "所有检查项目都已通过，可以开始迁移流程。"
    echo ""
    echo "下一步执行: ./sh/01_backup_source.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "环境初始化检查" "ERROR"; update_stage_status "initialization" "failed" 0; exit 1' ERR

main "$@"