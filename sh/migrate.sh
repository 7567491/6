#!/bin/bash
# 完整迁移自动化脚本

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"

# 脚本列表
MIGRATION_SCRIPTS=(
    "00_init.sh"
    "01_backup_source.sh"
    "01_transfer_data.sh"
    "02_setup_environment.sh"
    "03_deploy_apps.sh"
    "04_configure_nginx.sh"
    "05_setup_ssl.sh"
    "06_optimize_services.sh"
    "07_setup_monitoring.sh"
    "08_run_tests.sh"
)

log_info "开始完整迁移流程..."

# 显示迁移计划
show_migration_plan() {
    echo ""
    echo "======================================="
    echo "        镜像站点迁移计划"
    echo "======================================="
    echo ""
    echo "迁移脚本执行顺序:"
    for i in "${!MIGRATION_SCRIPTS[@]}"; do
        echo "  $((i+1)). ${MIGRATION_SCRIPTS[i]%.*} - $(get_script_description "${MIGRATION_SCRIPTS[i]}")"
    done
    echo ""
    echo "预计总耗时: 4-10小时"
    echo ""
    
    read -p "确认开始迁移? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "迁移已取消"
        exit 0
    fi
}

# 获取脚本描述
get_script_description() {
    case "$1" in
        "00_init.sh") echo "环境初始化和检查" ;;
        "01_backup_source.sh") echo "源服务器数据备份" ;;
        "01_transfer_data.sh") echo "数据传输到目标服务器" ;;
        "02_setup_environment.sh") echo "目标服务器环境搭建" ;;
        "03_deploy_apps.sh") echo "应用部署和配置" ;;
        "04_configure_nginx.sh") echo "Nginx虚拟主机配置" ;;
        "05_setup_ssl.sh") echo "SSL证书配置" ;;
        "06_optimize_services.sh") echo "服务优化配置" ;;
        "07_setup_monitoring.sh") echo "监控和日志配置" ;;
        "08_run_tests.sh") echo "测试验证" ;;
        *) echo "未知脚本" ;;
    esac
}

# 执行单个脚本
execute_script() {
    local script="$1"
    local script_path="./sh/$script"
    
    if [[ ! -f "$script_path" ]]; then
        log_error "脚本不存在: $script_path"
        return 1
    fi
    
    if [[ ! -x "$script_path" ]]; then
        chmod +x "$script_path"
    fi
    
    log_info "执行脚本: $script"
    
    # 执行脚本并捕获输出
    if "$script_path" 2>&1 | tee -a "./logs/migration_full.log"; then
        log_success "脚本执行成功: $script"
        return 0
    else
        log_error "脚本执行失败: $script"
        return 1
    fi
}

# 处理脚本执行失败
handle_script_failure() {
    local failed_script="$1"
    local script_index="$2"
    
    echo ""
    echo "======================================="
    echo "        脚本执行失败!"
    echo "======================================="
    echo "失败脚本: $failed_script"
    echo "失败位置: 第 $((script_index + 1)) 步"
    echo ""
    echo "可选操作:"
    echo "1. 查看错误日志: tail -50 ./logs/error.log"
    echo "2. 检查迁移状态: ./sh/utils/status.sh show"
    echo "3. 手动修复后继续: ./sh/$failed_script"
    echo "4. 回滚操作: ./sh/99_rollback.sh"
    echo ""
    
    read -p "是否查看错误日志? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo ""
        echo "最近的错误日志:"
        echo "--------------------------------"
        tail -20 "./logs/error.log" 2>/dev/null || echo "错误日志为空"
        echo "--------------------------------"
        echo ""
    fi
    
    read -p "是否尝试回滚? (y/N): " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        log_info "开始回滚操作..."
        if [[ -f "./sh/99_rollback.sh" ]]; then
            chmod +x "./sh/99_rollback.sh"
            "./sh/99_rollback.sh"
        else
            log_error "回滚脚本不存在"
        fi
    fi
}

# 显示迁移进度
show_progress() {
    local current_step="$1"
    local total_steps="${#MIGRATION_SCRIPTS[@]}"
    local progress=$((current_step * 100 / total_steps))
    
    echo ""
    echo "迁移进度: [$current_step/$total_steps] ($progress%)"
    printf "["
    for ((i=0; i<50; i++)); do
        if [[ $i -lt $((progress / 2)) ]]; then
            printf "="
        else
            printf " "
        fi
    done
    printf "] $progress%%\n"
    echo ""
}

# 主执行流程
main() {
    local start_time=$(date +%s)
    
    # 显示迁移计划
    show_migration_plan
    
    # 初始化状态
    init_status
    
    echo ""
    echo "======================================="
    echo "        开始执行迁移流程"
    echo "======================================="
    
    # 执行所有迁移脚本
    for i in "${!MIGRATION_SCRIPTS[@]}"; do
        local script="${MIGRATION_SCRIPTS[i]}"
        
        show_progress $((i + 1))
        
        if execute_script "$script"; then
            log_success "步骤 $((i + 1)) 完成: $script"
        else
            handle_script_failure "$script" "$i"
            exit 1
        fi
        
        # 在关键步骤之间暂停
        if [[ "$script" =~ ^(01_backup_source|01_transfer_data|02_setup_environment)\.sh$ ]]; then
            echo ""
            read -p "按回车键继续下一步..." 
            echo ""
        fi
    done
    
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    echo ""
    echo "======================================="
    echo "        迁移完成!"
    echo "======================================="
    echo "总耗时: $(date -u -d @$duration +%H:%M:%S)"
    echo ""
    echo "迁移结果:"
    echo "- 主站点: https://$PRIMARY_DOMAIN"
    echo "- 点播站点: https://$VOD_DOMAIN"
    echo ""
    echo "后续操作:"
    echo "1. 检查网站功能是否正常"
    echo "2. 更新DNS解析到新服务器"
    echo "3. 监控服务器性能"
    echo "4. 设置定期备份"
    echo ""
    
    # 显示最终状态
    show_status
    
    log_success "完整迁移流程执行完毕"
}

# 错误处理
trap 'echo "迁移过程中断，请检查日志文件"; exit 1' ERR

# 设置脚本为可执行
chmod +x ./sh/*.sh 2>/dev/null || true
chmod +x ./sh/utils/*.sh 2>/dev/null || true

main "$@"