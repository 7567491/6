#!/bin/bash
# 状态管理工具

STATUS_FILE="./logs/migration_status.json"
CONFIG_FILE="./sh/config/servers.conf"

# 载入配置
source "$CONFIG_FILE" 2>/dev/null || echo "警告: 无法载入配置文件"
source "./sh/utils/logger.sh"

# 初始化状态文件
init_status() {
    local current_time=$(date '+%Y-%m-%d %H:%M:%S')
    cat > "$STATUS_FILE" <<EOF
{
  "migration_id": "$(date +%Y%m%d_%H%M%S)",
  "start_time": "$current_time",
  "current_stage": "初始化",
  "overall_progress": 0,
  "stages": {
    "backup": {"status": "pending", "progress": 0, "start_time": null, "end_time": null},
    "transfer": {"status": "pending", "progress": 0, "start_time": null, "end_time": null},
    "environment": {"status": "pending", "progress": 0, "start_time": null, "end_time": null},
    "deployment": {"status": "pending", "progress": 0, "start_time": null, "end_time": null},
    "configuration": {"status": "pending", "progress": 0, "start_time": null, "end_time": null},
    "testing": {"status": "pending", "progress": 0, "start_time": null, "end_time": null}
  },
  "errors": [],
  "warnings": [],
  "rollback_points": []
}
EOF
    log_info "状态文件已初始化: $STATUS_FILE"
}

# 更新阶段状态
update_stage_status() {
    local stage="$1"
    local status="$2"  # pending|running|completed|failed
    local progress="$3"
    local current_time=$(date '+%Y-%m-%d %H:%M:%S')
    
    if [[ ! -f "$STATUS_FILE" ]]; then
        init_status
    fi
    
    # 使用jq更新JSON文件 (如果没有jq则使用sed)
    if command -v jq >/dev/null; then
        local temp_file=$(mktemp)
        jq --arg stage "$stage" --arg status "$status" --arg progress "$progress" --arg time "$current_time" '
            .stages[$stage].status = $status |
            .stages[$stage].progress = ($progress | tonumber) |
            if $status == "running" then
                .stages[$stage].start_time = $time |
                .current_stage = $stage
            elif $status == "completed" or $status == "failed" then
                .stages[$stage].end_time = $time
            else . end
        ' "$STATUS_FILE" > "$temp_file" && mv "$temp_file" "$STATUS_FILE"
    else
        # 简单的状态记录
        echo "$(date '+%Y-%m-%d %H:%M:%S'): $stage = $status ($progress%)" >> "${STATUS_FILE}.simple"
    fi
    
    log_info "阶段状态更新: $stage -> $status ($progress%)"
}

# 添加错误记录
add_error() {
    local error_msg="$1"
    local current_time=$(date '+%Y-%m-%d %H:%M:%S')
    
    if command -v jq >/dev/null && [[ -f "$STATUS_FILE" ]]; then
        local temp_file=$(mktemp)
        jq --arg error "$error_msg" --arg time "$current_time" '
            .errors += [{"time": $time, "message": $error}]
        ' "$STATUS_FILE" > "$temp_file" && mv "$temp_file" "$STATUS_FILE"
    fi
    
    log_error "$error_msg"
}

# 创建回滚点
create_rollback_point() {
    local point_name="$1"
    local description="$2"
    local current_time=$(date '+%Y-%m-%d %H:%M:%S')
    
    if command -v jq >/dev/null && [[ -f "$STATUS_FILE" ]]; then
        local temp_file=$(mktemp)
        jq --arg name "$point_name" --arg desc "$description" --arg time "$current_time" '
            .rollback_points += [{"name": $name, "description": $desc, "time": $time}]
        ' "$STATUS_FILE" > "$temp_file" && mv "$temp_file" "$STATUS_FILE"
    fi
    
    log_info "回滚点已创建: $point_name - $description"
}

# 显示当前状态
show_status() {
    if [[ -f "$STATUS_FILE" ]] && command -v jq >/dev/null; then
        echo "=== 迁移状态概览 ==="
        jq -r '
            "迁移ID: " + .migration_id,
            "开始时间: " + .start_time,
            "当前阶段: " + .current_stage,
            "整体进度: " + (.overall_progress | tostring) + "%",
            "",
            "各阶段状态:",
            (.stages | to_entries[] | "  " + .key + ": " + .value.status + " (" + (.value.progress | tostring) + "%)")
        ' "$STATUS_FILE"
        
        echo ""
        echo "错误数量: $(jq '.errors | length' "$STATUS_FILE")"
        echo "警告数量: $(jq '.warnings | length' "$STATUS_FILE")"
        echo "回滚点数量: $(jq '.rollback_points | length' "$STATUS_FILE")"
    else
        echo "状态文件不存在或jq未安装，显示简化状态"
        [[ -f "${STATUS_FILE}.simple" ]] && tail -10 "${STATUS_FILE}.simple"
    fi
}

# 如果直接运行此脚本，显示状态
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    case "${1:-show}" in
        "init") init_status ;;
        "show") show_status ;;
        "update") update_stage_status "$2" "$3" "$4" ;;
        "error") add_error "$2" ;;
        "rollback") create_rollback_point "$2" "$3" ;;
        *) echo "用法: $0 {init|show|update|error|rollback}" ;;
    esac
fi