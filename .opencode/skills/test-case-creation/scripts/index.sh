#!/bin/bash
# 生成测试用例索引脚本
# 在项目的 tests/ 目录下生成索引

set -e

# 用法说明
usage() {
    echo "用法: $0 [项目目录]"
    echo ""
    echo "示例:"
    echo "  $0"
    echo "  $0 /path/to/project"
    exit 1
}

PROJECT_DIR="${1:-.}"

# 测试用例目录
TESTS_DIR="$PROJECT_DIR/tests/test-cases"
INDEX_FILE="$PROJECT_DIR/tests/INDEX.md"

if [ ! -d "$TESTS_DIR" ]; then
    echo "测试用例目录不存在: $TESTS_DIR"
    exit 1
fi

echo "生成测试用例索引..."

# 开始生成索引文件
cat > "$INDEX_FILE" << EOF
# 测试用例索引

> 最后更新: $(date +%Y-%m-%d)

## 目录

- [按测试类型](#按测试类型)
- [按优先级](#按优先级)
- [按功能模块](#按功能模块)

---

## 按测试类型

### API 测试
EOF

# 添加API测试用例
find "$TESTS_DIR" -name "*.md" -exec grep -l "type: API" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "### UI 测试" >> "$INDEX_FILE"

# 添加UI测试用例
find "$TESTS_DIR" -name "*.md" -exec grep -l "type: UI" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "### E2E 测试" >> "$INDEX_FILE"

# 添加E2E测试用例
find "$TESTS_DIR" -name "*.md" -exec grep -l "type: E2E" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "### Stress 测试" >> "$INDEX_FILE"

# 添加Stress测试用例
find "$TESTS_DIR" -name "*.md" -exec grep -l "type: Stress" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "---" >> "$INDEX_FILE"
echo "" >> "$INDEX_FILE"

# 按优先级分类
cat >> "$INDEX_FILE" << 'EOF'
## 按优先级

### P0 - 核心流程
EOF

find "$TESTS_DIR" -name "*.md" -exec grep -l "priority: P0" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "### P1 - 重要功能" >> "$INDEX_FILE"

find "$TESTS_DIR" -name "*.md" -exec grep -l "priority: P1" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "### P2 - 边缘功能" >> "$INDEX_FILE"

find "$TESTS_DIR" -name "*.md" -exec grep -l "priority: P2" {} \; 2>/dev/null | while read -r file; do
    title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
    rel_path="${file#$PROJECT_DIR/}"
    echo "- [$title]($rel_path)" >> "$INDEX_FILE"
done

echo "" >> "$INDEX_FILE"
echo "---" >> "$INDEX_FILE"
echo "" >> "$INDEX_FILE"

# 按功能模块分类 - 动态扫描目录
cat >> "$INDEX_FILE" << 'EOF'
## 按功能模块
EOF

# 扫描子目录
for dir in "$TESTS_DIR"/*/; do
    if [ -d "$dir" ]; then
        dir_name=$(basename "$dir")
        echo "" >> "$INDEX_FILE"
        echo "### $dir_name" >> "$INDEX_FILE"
        
        find "$dir" -name "*.md" 2>/dev/null | while read -r file; do
            title=$(grep "^title:" "$file" | head -1 | cut -d: -f2- | xargs)
            rel_path="${file#$PROJECT_DIR/}"
            echo "- [$title]($rel_path)" >> "$INDEX_FILE"
        done
    fi
done

echo ""
echo "✅ 索引已生成: $INDEX_FILE"
echo ""
echo "测试用例统计:"
echo "  API测试: $(find "$TESTS_DIR" -name "*.md" -exec grep -l "type: API" {} \; 2>/dev/null | wc -l)"
echo "  UI测试: $(find "$TESTS_DIR" -name "*.md" -exec grep -l "type: UI" {} \; 2>/dev/null | wc -l)"
echo "  E2E测试: $(find "$TESTS_DIR" -name "*.md" -exec grep -l "type: E2E" {} \; 2>/dev/null | wc -l)"
echo "  Stress测试: $(find "$TESTS_DIR" -name "*.md" -exec grep -l "type: Stress" {} \; 2>/dev/null | wc -l)"
