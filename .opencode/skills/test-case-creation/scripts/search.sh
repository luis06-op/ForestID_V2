#!/bin/bash
# 搜索测试用例脚本
# 在项目的 tests/test-cases/ 目录下搜索

set -e

# 用法说明
usage() {
    echo "用法: $0 <关键字> [项目目录] [选项]"
    echo ""
    echo "选项:"
    echo "  --type <api|ui|e2e|stress>    按测试类型筛选"
    echo "  --priority <P0|P1|P2>        按优先级筛选"
    echo "  --tag <tag>                  按标签筛选"
    echo ""
    echo "示例:"
    echo "  $0 login"
    echo "  $0 login /path/to/project"
    echo "  $0 --type api /path/to/project"
    echo "  $0 --priority P0 /path/to/project"
    exit 1
}

# 参数检查
if [ $# -lt 1 ]; then
    usage
fi

# 脚本目录
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# 解析参数
KEYWORD=""
PROJECT_DIR="."
TYPE_FILTER=""
PRIORITY_FILTER=""
TAG_FILTER=""

# 第一个参数是关键字
KEYWORD="$1"

# 检查是否有项目目录参数
if [ -d "$2" ]; then
    PROJECT_DIR="$2"
    shift 2
else
    shift
fi

# 解析剩余选项
while [ $# -gt 0 ]; do
    case "$1" in
        --type)
            TYPE_FILTER="$2"
            shift 2
            ;;
        --priority)
            PRIORITY_FILTER="$2"
            shift 2
            ;;
        --tag)
            TAG_FILTER="$2"
            shift 2
            ;;
        *)
            shift
            ;;
    esac
done

# 测试用例目录
TESTS_DIR="$PROJECT_DIR/tests/test-cases"

if [ ! -d "$TESTS_DIR" ]; then
    echo "测试用例目录不存在: $TESTS_DIR"
    echo "请先创建测试用例目录"
    exit 1
fi

# 搜索函数
search_by_keyword() {
    grep -r "$KEYWORD" "$TESTS_DIR" --include="*.md" -l 2>/dev/null
}

search_by_type() {
    find "$TESTS_DIR" -name "*.md" -exec grep -l "type: $TYPE_FILTER" {} \;
}

search_by_priority() {
    find "$TESTS_DIR" -name "*.md" -exec grep -l "priority: $PRIORITY_FILTER" {} \;
}

search_by_tag() {
    find "$TESTS_DIR" -name "*.md" -exec grep -l "tags:.*$TAG_FILTER" {} \;
}

# 执行搜索
RESULTS=""

if [ -n "$KEYWORD" ]; then
    RESULTS=$(search_by_keyword)
fi

if [ -n "$TYPE_FILTER" ]; then
    RESULTS=$(search_by_type)
fi

if [ -n "$PRIORITY_FILTER" ]; then
    RESULTS=$(search_by_priority)
fi

if [ -n "$TAG_FILTER" ]; then
    RESULTS=$(search_by_tag)
fi

# 输出结果
if [ -z "$RESULTS" ]; then
    echo "未找到匹配的测试用例"
    exit 0
fi

echo "在 $TESTS_DIR 中找到 $(echo "$RESULTS" | wc -l) 个测试用例:"
echo ""

while read -r file; do
    # 读取测试用例标题
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    type=$(grep "^type:" "$file" | head -1 | cut -d: -f2- | xargs)
    priority=$(grep "^priority:" "$file" | head -1 | cut -d: -f2- | xargs)
    
    # 相对路径
    rel_path="${file#$PROJECT_DIR/}"
    
    echo "📄 $rel_path"
    echo "   标题: $title"
    echo "   类型: $type | 优先级: $priority"
    echo ""
done <<< "$RESULTS"
