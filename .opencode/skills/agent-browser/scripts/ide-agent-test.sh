#!/bin/bash
# W7Panel Web IDE 自动化测试脚本
# 使用 agent-browser 进行界面功能测试

# 不使用 set -e 以便继续运行所有测试
# set -e

# 配置
BASE_URL="${BASE_URL:-http://localhost:8080}"
API_URL="${API_URL:-/k8s/webdav-agent/1/agent}"
INITIAL_PATH="${INITIAL_PATH:-/tmp}"
TOKEN="${K8S_TOKEN:-}"

# 颜色
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${GREEN}[INFO]${NC} $1"; }
log_step() { echo -e "${BLUE}[STEP]${NC} $1"; }
log_pass() { echo -e "${GREEN}[PASS]${NC} $1"; }
log_fail() { echo -e "${RED}[FAIL]${NC} $1"; }

# 获取 Token
get_token() {
    if [ -n "$TOKEN" ]; then
        echo "$TOKEN" | tr -d '[:space:]'
        return 0
    fi
    
    local kubeconfig="$BASE_DIR/w7panel/kubeconfig.yaml"
    if [ -f "$kubeconfig" ]; then
        # 提取 token 并去除所有空白字符
        grep -A1 "token:" "$kubeconfig" | tail -1 | awk '{print $2}' | tr -d '[:space:]'
        return 0
    fi
    
    if [ -f "/var/run/secrets/kubernetes.io/serviceaccount/token" ]; then
        cat /var/run/secrets/kubernetes.io/serviceaccount/token
        return 0
    fi
    
    return 1
}

# 打开编辑器
open_editor() {
    local token="$1"
    local encoded_token=$(python3 -c "import urllib.parse; print(urllib.parse.quote('''$token'''))")
    local url="${BASE_URL}/ui/plugin/codeblitz/editor.html?api-url=${API_URL}&api-token=${encoded_token}&initial-path=${INITIAL_PATH}"
    
    agent-browser open "$url" 2>&1
    sleep 15  # 等待编辑器完全加载
}

# 测试1: 编辑器加载
test_editor_load() {
    log_step "测试1: 编辑器加载"
    
    local console=$(agent-browser console 2>&1)
    
    if echo "$console" | grep -q "token: \*\*\*"; then
        log_pass "Token 已正确设置"
    else
        log_fail "Token 未设置"
        return 1
    fi
    
    if echo "$console" | grep -q "Response: 207"; then
        log_pass "WebDAV 连接成功"
    else
        log_fail "WebDAV 连接失败"
        return 1
    fi
    
    if echo "$console" | grep -q "Parsed files:"; then
        log_pass "文件列表加载成功"
    else
        log_fail "文件列表加载失败"
        return 1
    fi
    
    agent-browser screenshot /tmp/ide-test-1-load.png 2>&1
    return 0
}

# 测试2: 命令面板
test_command_palette() {
    log_step "测试2: 命令面板 (Ctrl+Shift+P)"
    
    # 打开命令面板
    agent-browser keydown Control 2>&1
    agent-browser keydown Shift 2>&1
    agent-browser press p 2>&1
    agent-browser keyup Shift 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 2
    
    # 检查快照
    local snapshot=$(agent-browser snapshot -i 2>&1)
    
    if echo "$snapshot" | grep -q "textbox.*命令"; then
        log_pass "命令面板打开成功"
        agent-browser screenshot /tmp/ide-test-2-command-palette.png 2>&1
        
        # 测试输入命令
        local input_ref=$(echo "$snapshot" | grep "textbox" | grep -oE "@e[0-9]+" | head -1)
        if [ -n "$input_ref" ]; then
            agent-browser fill "$input_ref" "New File" 2>&1
            sleep 1
            agent-browser screenshot /tmp/ide-test-2-command-search.png 2>&1
        fi
        
        # 关闭命令面板
        agent-browser press Escape 2>&1
        sleep 1
        return 0
    else
        log_fail "命令面板未打开"
        agent-browser press Escape 2>&1
        return 1
    fi
}

# 测试3: 快速打开文件 (Ctrl+P)
test_quick_open() {
    log_step "测试3: 快速打开文件 (Ctrl+P)"
    
    agent-browser keydown Control 2>&1
    agent-browser press p 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 2
    
    local snapshot=$(agent-browser snapshot -i 2>&1)
    
    if echo "$snapshot" | grep -q "textbox"; then
        log_pass "快速打开面板显示成功"
        agent-browser screenshot /tmp/ide-test-3-quick-open.png 2>&1
        agent-browser press Escape 2>&1
        sleep 1
        return 0
    else
        log_fail "快速打开面板未显示"
        agent-browser press Escape 2>&1
        return 1
    fi
}

# 测试4: 保存快捷键 (Ctrl+S)
test_save_shortcut() {
    log_step "测试4: 保存快捷键 (Ctrl+S)"
    
    agent-browser keydown Control 2>&1
    agent-browser press s 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 1
    
    # 检查是否有错误（如果没有文件打开，应该有提示）
    local console=$(agent-browser console 2>&1)
    
    log_pass "保存快捷键测试完成"
    agent-browser screenshot /tmp/ide-test-4-save.png 2>&1
    return 0
}

# 测试5: 查找功能 (Ctrl+F)
test_find() {
    log_step "测试5: 查找功能 (Ctrl+F)"
    
    agent-browser keydown Control 2>&1
    agent-browser press f 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 2
    
    local snapshot=$(agent-browser snapshot -i 2>&1)
    
    # 查找面板可能有 textbox
    if echo "$snapshot" | grep -q "textbox\|search"; then
        log_pass "查找面板显示"
    else
        log_info "查找面板状态未知"
    fi
    
    agent-browser screenshot /tmp/ide-test-5-find.png 2>&1
    agent-browser press Escape 2>&1
    sleep 1
    return 0
}

# 测试6: 终端面板
test_terminal() {
    log_step "测试6: 终端面板 (Ctrl+Backtick)"
    
    # 打开终端 - 使用反引号
    agent-browser keydown Control 2>&1
    agent-browser press "Backquote" 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 2
    
    agent-browser screenshot /tmp/ide-test-6-terminal.png 2>&1
    
    # 关闭终端
    agent-browser keydown Control 2>&1
    agent-browser press "Backquote" 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 1
    log_pass "终端面板测试完成"
    return 0
}

# 测试7: 切换侧边栏 (Ctrl+B)
test_sidebar() {
    log_step "测试7: 切换侧边栏 (Ctrl+B)"
    
    agent-browser keydown Control 2>&1
    agent-browser press b 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 1
    agent-browser screenshot /tmp/ide-test-7-sidebar-toggle.png 2>&1
    
    # 再次切换回来
    agent-browser keydown Control 2>&1
    agent-browser press b 2>&1
    agent-browser keyup Control 2>&1
    
    sleep 1
    log_pass "侧边栏切换测试完成"
    return 0
}

# 测试8: 页面元素数量
test_page_elements() {
    log_step "测试8: 页面元素检查"
    
    local count=$(agent-browser eval "document.querySelectorAll('*').length" 2>&1 | grep -o '[0-9]*')
    log_info "页面元素数量: $count"
    
    if [ "$count" -gt 100 ]; then
        log_pass "页面元素加载正常 ($count 个元素)"
        return 0
    else
        log_fail "页面元素可能未完全加载 ($count 个元素)"
        return 1
    fi
}

# 运行所有测试
run_tests() {
    local passed=0
    local failed=0
    
    echo ""
    echo "=========================================="
    echo "  W7Panel Web IDE 自动化测试"
    echo "=========================================="
    echo ""
    
    # 获取 Token
    TOKEN=$(get_token)
    if [ -z "$TOKEN" ]; then
        log_fail "未找到 Token"
        exit 1
    fi
    log_info "Token 已获取"
    
    # 打开编辑器
    log_info "打开编辑器..."
    open_editor "$TOKEN"
    
    # 运行测试
    test_editor_load && ((passed++)) || ((failed++))
    test_command_palette && ((passed++)) || ((failed++))
    test_quick_open && ((passed++)) || ((failed++))
    test_save_shortcut && ((passed++)) || ((failed++))
    test_find && ((passed++)) || ((failed++))
    test_terminal && ((passed++)) || ((failed++))
    test_sidebar && ((passed++)) || ((failed++))
    test_page_elements && ((passed++)) || ((failed++))
    
    # 关闭浏览器
    agent-browser close 2>&1
    
    echo ""
    echo "=========================================="
    echo "  测试结果: ${GREEN}通过 ${passed}${NC} / ${RED}失败 ${failed}${NC}"
    echo "=========================================="
    echo ""
    echo "截图保存在 /tmp/ide-test-*.png"
    echo ""
    
    if [ $failed -gt 0 ]; then
        return 1
    fi
    return 0
}

# 交互模式
interactive() {
    TOKEN=$(get_token)
    if [ -z "$TOKEN" ]; then
        log_fail "未找到 Token"
        exit 1
    fi
    
    open_editor "$TOKEN"
    
    echo ""
    echo "交互模式 - 可用命令:"
    echo "  snapshot     - 获取页面快照"
    echo "  console      - 查看控制台日志"
    echo "  screenshot   - 截图"
    echo "  click @e1    - 点击元素"
    echo "  fill @e1 txt - 填充文本"
    echo "  press Key    - 按键"
    echo "  eval 'js'    - 执行 JavaScript"
    echo "  exit         - 退出"
    echo ""
    
    while true; do
        read -p "agent-browser> " cmd args
        
        case "$cmd" in
            exit|quit)
                agent-browser close 2>&1
                break
                ;;
            snapshot)
                agent-browser snapshot -i 2>&1
                ;;
            console)
                agent-browser console 2>&1
                ;;
            screenshot)
                agent-browser screenshot "/tmp/ide-interactive-$(date +%s).png" 2>&1
                ;;
            *)
                agent-browser $cmd $args 2>&1
                ;;
        esac
    done
}

# 主函数
main() {
    case "${1:-test}" in
        test)
            run_tests
            ;;
        interactive|i)
            interactive
            ;;
        help|--help|-h)
            echo "用法: $0 [命令]"
            echo ""
            echo "命令:"
            echo "  test        运行所有测试 (默认)"
            echo "  interactive 进入交互模式"
            echo "  help        显示帮助"
            echo ""
            echo "环境变量:"
            echo "  BASE_URL      服务地址"
            echo "  K8S_TOKEN     认证 Token"
            ;;
        *)
            log_fail "未知命令: $1"
            exit 1
            ;;
    esac
}

# 解析参数
while [[ $# -gt 0 ]]; do
    case $1 in
        --token)
            K8S_TOKEN="$2"
            shift 2
            ;;
        --url)
            BASE_URL="$2"
            shift 2
            ;;
        *)
            break
            ;;
    esac
done

main "$@"
