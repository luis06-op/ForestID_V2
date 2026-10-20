#!/bin/bash
# 运行测试用例脚本
# 保存到项目 tests/scripts/run-test.sh

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 用法说明
usage() {
    echo "用法: $0 <测试用例文件> [选项]"
    echo ""
    echo "选项:"
    echo "  --base-url <url>    设置基础URL"
    echo "  --token <token>    设置认证Token"
    echo "  --ui                使用UI模式(agent-browser)"
    echo "  --api               使用API模式(curl)"
    echo ""
    echo "示例:"
    echo "  $0 test-cases/api/login.md --base-url http://localhost:8080"
    echo "  $0 test-cases/ui/login.md --ui --base-url http://localhost:8080"
    exit 1
}

# 检查参数
if [ $# -lt 1 ]; then
    usage
fi

TEST_FILE="$1"
shift

# 解析选项
MODE="auto"
BASE_URL="http://localhost:8080"
TOKEN=""

while [ $# -gt 0 ]; do
    case "$1" in
        --base-url)
            BASE_URL="$2"
            shift 2
            ;;
        --token)
            TOKEN="$2"
            shift 2
            ;;
        --ui)
            MODE="ui"
            shift
            ;;
        --api)
            MODE="api"
            shift
            ;;
        *)
            echo -e "${RED}未知选项: $1${NC}"
            usage
            ;;
    esac
done

# 检查测试文件
if [ ! -f "$TEST_FILE" ]; then
    echo -e "${RED}错误: 测试用例不存在: $TEST_FILE${NC}"
    exit 1
fi

# 读取测试类型
TEST_TYPE=$(grep "^type:" "$TEST_FILE" | head -1 | cut -d: -f2- | xargs)
TEST_TITLE=$(grep "^title:" "$TEST_FILE" | head -1 | cut -d: -f2- | xargs)

echo -e "${GREEN}开始执行测试用例${NC}"
echo "  文件: $TEST_FILE"
echo "  标题: $TEST_TITLE"
echo "  类型: $TEST_TYPE"
echo "  URL: $BASE_URL"
echo ""

# 自动选择模式
if [ "$MODE" = "auto" ]; then
    if [ "$TEST_TYPE" = "UI" ] || [ "$TEST_TYPE" = "E2E" ]; then
        MODE="ui"
    else
        MODE="api"
    fi
fi

# 执行测试
if [ "$MODE" = "ui" ]; then
    echo "=== 使用UI模式执行 ==="
    
    # 检查agent-browser是否可用
    if ! command -v agent-browser &> /dev/null; then
        echo -e "${RED}错误: agent-browser 未安装${NC}"
        exit 1
    fi
    
    # 从测试文件中提取URL
    PAGE_URL=$(grep -E "^PAGE_URL|^BASE_URL" "$TEST_FILE" | head -1 | cut -d: -f2- | xargs)
    if [ -z "$PAGE_URL" ]; then
        PAGE_URL="$BASE_URL"
    fi
    
    agent-browser open "$PAGE_URL"
    sleep 5
    
    # 获取元素
    agent-browser snapshot -i
    
    echo "测试完成，请查看结果"
    
elif [ "$MODE" = "api" ]; then
    echo "=== 使用API模式执行 ==="
    
    # 检查curl是否可用
    if ! command -v curl &> /dev/null; then
        echo -e "${RED}错误: curl 未安装${NC}"
        exit 1
    fi
    
    # 从测试文件中提取API路径
    API_PATH=$(grep -E "^API_PATH|^API_BASE" "$TEST_FILE" | head -1 | cut -d: -f2- | xargs)
    
    if [ -n "$API_PATH" ]; then
        echo "请求: $BASE_URL$API_PATH"
        
        if [ -n "$TOKEN" ]; then
            curl -s -X GET "$BASE_URL$API_PATH" \
              -H "Authorization: Bearer $TOKEN"
        else
            curl -s -X GET "$BASE_URL$API_PATH"
        fi
    else
        echo -e "${YELLOW}警告: 未找到API路径，跳过API测试${NC}"
    fi
fi

echo ""
echo -e "${GREEN}测试执行完成${NC}"
