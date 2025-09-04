#!/bin/bash
# 数据传输脚本 - 从源服务器传输备份数据到本地

set -e

# 载入工具函数
source "./sh/utils/logger.sh"
source "./sh/utils/status.sh"
source "./sh/config/servers.conf"

log_step_start "数据传输到目标服务器"
update_stage_status "transfer" "running" 0

log_info "开始从源服务器传输数据..."

# 检查并创建本地目录
prepare_local_dirs() {
    log_info "准备本地目录..."
    
    local dirs=("$TRANSFER_DIR" "$TRANSFER_DIR/configs" "$TRANSFER_DIR/ssl_certs")
    for dir in "${dirs[@]}"; do
        mkdir -p "$dir"
        log_info "目录已创建: $dir"
    done
    
    update_stage_status "transfer" "running" 10
}

# 传输数据库备份
transfer_databases() {
    log_info "传输数据库备份文件..."
    
    # 使用rsync以支持断点续传和进度显示
    sshpass -p "$SOURCE_SERVER_PASS" rsync -avz --progress \
        "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/*.sql" \
        "$TRANSFER_DIR/" 2>/dev/null || {
        
        # 如果rsync失败，回退到scp
        log_warn "rsync失败，使用scp传输数据库文件"
        sshpass -p "$SOURCE_SERVER_PASS" scp -o StrictHostKeyChecking=no \
            "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/*.sql" \
            "$TRANSFER_DIR/"
    }
    
    update_stage_status "transfer" "running" 25
    log_success "数据库文件传输完成"
}

# 传输网站文件
transfer_websites() {
    log_info "传输网站文件备份..."
    
    local files=("websites_backup.tar.gz" "uploads_backup.tar.gz")
    
    for file in "${files[@]}"; do
        log_info "传输文件: $file"
        
        # 检查文件是否存在
        if sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
           "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "test -f $BACKUP_DIR/$file"; then
            
            sshpass -p "$SOURCE_SERVER_PASS" rsync -avz --progress \
                "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/$file" \
                "$TRANSFER_DIR/" || {
                
                # rsync失败时使用scp
                sshpass -p "$SOURCE_SERVER_PASS" scp -o StrictHostKeyChecking=no \
                    "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/$file" \
                    "$TRANSFER_DIR/"
            }
            
            log_success "文件传输完成: $file"
        else
            log_warn "文件不存在，跳过: $file"
        fi
    done
    
    update_stage_status "transfer" "running" 50
    log_success "网站文件传输完成"
}

# 传输Redis备份
transfer_redis() {
    log_info "传输Redis备份..."
    
    if sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
       "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "test -f $BACKUP_DIR/redis_backup.rdb"; then
        
        sshpass -p "$SOURCE_SERVER_PASS" scp -o StrictHostKeyChecking=no \
            "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/redis_backup.rdb" \
            "$TRANSFER_DIR/"
        
        log_success "Redis备份传输完成"
    else
        log_warn "Redis备份文件不存在，跳过"
    fi
    
    update_stage_status "transfer" "running" 65
}

# 传输配置文件
transfer_configs() {
    log_info "传输配置文件..."
    
    if sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
       "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "test -d $BACKUP_DIR/configs_backup"; then
        
        sshpass -p "$SOURCE_SERVER_PASS" rsync -avz --progress \
            "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/configs_backup/" \
            "$TRANSFER_DIR/configs/" || {
            
            # rsync失败时使用scp
            sshpass -p "$SOURCE_SERVER_PASS" scp -r -o StrictHostKeyChecking=no \
                "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/configs_backup/" \
                "$TRANSFER_DIR/configs/"
        }
        
        log_success "配置文件传输完成"
    else
        log_warn "配置文件目录不存在，跳过"
    fi
    
    update_stage_status "transfer" "running" 80
}

# 传输SSL证书
transfer_ssl() {
    log_info "传输SSL证书..."
    
    if sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
       "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "test -d $BACKUP_DIR/ssl_certs_backup"; then
        
        sshpass -p "$SOURCE_SERVER_PASS" rsync -avz --progress \
            "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/ssl_certs_backup/" \
            "$TRANSFER_DIR/ssl_certs/" || {
            
            # rsync失败时使用scp
            sshpass -p "$SOURCE_SERVER_PASS" scp -r -o StrictHostKeyChecking=no \
                "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/ssl_certs_backup/" \
                "$TRANSFER_DIR/ssl_certs/"
        }
        
        log_success "SSL证书传输完成"
    else
        log_warn "SSL证书目录不存在，跳过"
    fi
    
    update_stage_status "transfer" "running" 90
}

# 传输备份清单
transfer_manifest() {
    log_info "传输备份清单..."
    
    if sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
       "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "test -f $BACKUP_DIR/backup_manifest.txt"; then
        
        sshpass -p "$SOURCE_SERVER_PASS" scp -o StrictHostKeyChecking=no \
            "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP:$BACKUP_DIR/backup_manifest.txt" \
            "$TRANSFER_DIR/"
        
        log_success "备份清单传输完成"
    else
        log_warn "备份清单不存在，跳过"
    fi
}

# 验证传输完整性
verify_transfer() {
    log_info "验证传输完整性..."
    
    echo "传输文件清单:" | tee "$TRANSFER_DIR/transfer_manifest.txt"
    echo "传输时间: $(date)" | tee -a "$TRANSFER_DIR/transfer_manifest.txt"
    echo "目标目录: $TRANSFER_DIR" | tee -a "$TRANSFER_DIR/transfer_manifest.txt"
    echo "" | tee -a "$TRANSFER_DIR/transfer_manifest.txt"
    
    ls -la "$TRANSFER_DIR"/ | tee -a "$TRANSFER_DIR/transfer_manifest.txt"
    
    # 检查关键文件
    local critical_files=("*.sql")
    local missing_files=()
    
    for pattern in "${critical_files[@]}"; do
        if ! ls $TRANSFER_DIR/$pattern >/dev/null 2>&1; then
            missing_files+=("$pattern")
        fi
    done
    
    if [[ ${#missing_files[@]} -gt 0 ]]; then
        log_error "关键文件缺失: ${missing_files[*]}"
        add_error "传输验证失败：关键文件缺失"
        return 1
    fi
    
    log_success "传输完整性验证通过"
}

# 清理源服务器临时文件 (可选)
cleanup_source() {
    if [[ "${CLEANUP_SOURCE:-no}" == "yes" ]]; then
        log_info "清理源服务器临时文件..."
        
        sshpass -p "$SOURCE_SERVER_PASS" ssh -o StrictHostKeyChecking=no \
            "$SOURCE_SERVER_USER@$SOURCE_SERVER_IP" "
            echo '清理临时备份文件...'
            rm -f $BACKUP_DIR/*.sql $BACKUP_DIR/*.tar.gz $BACKUP_DIR/*.rdb
            rm -rf $BACKUP_DIR/configs_backup/ $BACKUP_DIR/ssl_certs_backup/
            rm -f $BACKUP_DIR/backup_manifest.txt
            echo '源服务器清理完成'
        "
        
        log_success "源服务器临时文件清理完成"
    else
        log_info "跳过源服务器清理 (设置 CLEANUP_SOURCE=yes 启用)"
    fi
}

# 主执行流程
main() {
    log_info "开始数据传输，目标目录: $TRANSFER_DIR"
    
    prepare_local_dirs
    transfer_databases
    transfer_websites
    transfer_redis
    transfer_configs
    transfer_ssl
    transfer_manifest
    
    update_stage_status "transfer" "running" 95
    
    verify_transfer
    cleanup_source
    
    update_stage_status "transfer" "completed" 100
    
    # 创建回滚点
    create_rollback_point "transfer_completed" "数据传输完成"
    
    log_step_end "数据传输到目标服务器" "SUCCESS"
    
    echo ""
    echo "======================="
    echo "    数据传输完成"
    echo "======================="
    echo "所有备份数据已传输到本地目录: $TRANSFER_DIR"
    echo ""
    echo "传输的文件包括:"
    echo "- 数据库备份 (*.sql)"
    echo "- 网站文件 (websites_backup.tar.gz)"
    echo "- Redis备份 (redis_backup.rdb)"
    echo "- 配置文件 (configs/)"
    echo "- SSL证书 (ssl_certs/)"
    echo ""
    echo "下一步执行: ./sh/02_setup_environment.sh"
    echo ""
}

# 错误处理
trap 'log_step_end "数据传输到目标服务器" "ERROR"; update_stage_status "transfer" "failed" 0; exit 1' ERR

main "$@"