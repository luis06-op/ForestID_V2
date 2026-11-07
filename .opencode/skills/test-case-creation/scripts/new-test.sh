#!/bin/bash
# 创建新测试用例脚本
# 保存到项目的 tests/test-cases/ 目录下

set -e

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# 用法说明
usage() {
    echo "用法: $0 <类型> <名称> [项目目录]"
    echo ""
    echo "类型:"
    echo "  api         API测试"
    echo "  ui          UI测试"
    echo "  e2e         E2E测试"
    echo "  stress      压力测试"
    echo ""
    echo "示例:"
    echo "  $0 api login"
    echo "  $0 ui dashboard"
    echo "  $0 e2e user-flow"
    echo "  $0 api login /path/to/project"
    exit 1
}

# 检查参数
if [ $# -lt 2 ]; then
    usage
fi

TYPE="$1"
NAME="$2"
PROJECT_DIR="${3:-.}"

# 确定项目tests目录
TESTS_DIR="$PROJECT_DIR/tests/test-cases"

# 技能目录 (用于获取模板)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SKILL_DIR="$SCRIPT_DIR/.."
TEMPLATE_DIR="$SKILL_DIR/templates"

# 模板文件映射
case "$TYPE" in
    api)
        TEMPLATE="$TEMPLATE_DIR/api.md"
        OUTPUT_SUBDIR="api"
        ;;
    ui)
        TEMPLATE="$TEMPLATE_DIR/ui.md"
        OUTPUT_SUBDIR="ui"
        ;;
    e2e)
        TEMPLATE="$TEMPLATE_DIR/e2e.md"
        OUTPUT_SUBDIR="e2e"
        ;;
    stress)
        TEMPLATE="$TEMPLATE_DIR/stress.md"
        OUTPUT_SUBDIR="stress"
        ;;
    *)
        echo -e "${RED}错误: 未知类型 '$TYPE'${NC}"
        usage
        ;;
esac

# 检查模板是否存在
if [ ! -f "$TEMPLATE" ]; then
    echo -e "${RED}错误: 模板不存在 '$TEMPLATE'${NC}"
    exit 1
fi

# 创建输出目录
OUTPUT_PATH="$TESTS_DIR/$OUTPUT_SUBDIR"
mkdir -p "$OUTPUT_PATH"

# 生成文件路径
DATE=$(date +%Y-%m-%d)
OUTPUT_FILE="$OUTPUT_PATH/${NAME}.md"

# 检查文件是否已存在
if [ -f "$OUTPUT_FILE" ]; then
    echo -e "${YELLOW}警告: 文件已存在 '$OUTPUT_FILE'${NC}"
    read -p "是否覆盖? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "已取消"
        exit 0
    fi
fi

# 复制模板并替换占位符
sed "s/{date}/$DATE/g; s/{name}/$NAME/g" "$TEMPLATE" > "$OUTPUT_FILE"

echo -e "${GREEN}✅ 测试用例已创建: $OUTPUT_FILE${NC}"
echo ""
echo "📝 下一步:"
echo "   1. 编辑测试用例: vim $OUTPUT_FILE"
echo "   2. 运行测试: curl 或 agent-browser"
echo "   3. 更新索引: bash $SCRIPT_DIR/index.sh $PROJECT_DIR"
