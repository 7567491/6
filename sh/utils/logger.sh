#!/bin/bash
# 日志记录工具

LOG_DIR="./logs"
LOG_FILE="${LOG_DIR}/migration.log"
ERROR_LOG="${LOG_DIR}/error.log"

# 确保日志目录存在
mkdir -p "$LOG_DIR"

# 日志函数
log_info() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [INFO] $1" | tee -a "$LOG_FILE"
}

log_error() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ERROR] $1" | tee -a "$LOG_FILE" "$ERROR_LOG"
}

log_warn() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [WARN] $1" | tee -a "$LOG_FILE"
}

log_success() {
    echo "$(date '+%Y-%m-%d %H:%M:%S') [SUCCESS] $1" | tee -a "$LOG_FILE"
}

# 步骤开始记录
log_step_start() {
    local step_name="$1"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [STEP] 开始: $step_name" | tee -a "$LOG_FILE"
    echo "$step_name" > "${LOG_DIR}/current_step.txt"
    echo "$(date +%s)" > "${LOG_DIR}/step_start_time.txt"
}

# 步骤结束记录
log_step_end() {
    local step_name="$1"
    local status="$2"  # SUCCESS|ERROR
    local start_time=$(cat "${LOG_DIR}/step_start_time.txt" 2>/dev/null || echo "0")
    local end_time=$(date +%s)
    local duration=$((end_time - start_time))
    
    echo "$(date '+%Y-%m-%d %H:%M:%S') [STEP] 结束: $step_name - $status (耗时: ${duration}秒)" | tee -a "$LOG_FILE"
    echo "COMPLETED" > "${LOG_DIR}/current_step.txt"
    
    # 记录到性能日志
    echo "$(date '+%Y-%m-%d %H:%M:%S'),$step_name,$status,$duration" >> "${LOG_DIR}/performance.csv"
}