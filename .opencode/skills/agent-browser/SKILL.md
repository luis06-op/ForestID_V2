---
name: agent-browser
description: Browser automation CLI for AI agents. Use for frontend development, debugging, and automated testing. Supports page navigation, DOM inspection, form interaction, keyboard shortcuts, and screenshot capture.
license: Apache-2.0
---

# agent-browser

Headless browser automation CLI for AI agents. Fast Rust CLI with Node.js fallback.

## Installation

```bash
npm install -g agent-browser
agent-browser install --with-deps
```

## Decision Tree: Choosing Your Approach

```
User task → What do you need?
    ├─ Test page load → open + snapshot + console
    │
    ├─ Interact with elements → snapshot -i + use refs (@e1)
    │
    ├─ Test keyboard shortcuts → keydown + press + keyup
    │
    ├─ Debug issues → console + errors + screenshot
    │
    └─ Verify content → get text + eval JavaScript
```

## Core Workflow

### 1. Open and Inspect
```bash
agent-browser open <url>
sleep 5  # Wait for page load
agent-browser snapshot -i         # Get interactive elements
agent-browser console             # Check for errors
agent-browser screenshot          # Capture current state
```

### 2. Interact Using Refs (Recommended)
```bash
# Refs (@e1, @e2) come from snapshot output
agent-browser click @e1
agent-browser fill @e2 "text content"
agent-browser hover @e3
```

### 3. Keyboard Shortcuts
```bash
# Single key
agent-browser press Enter
agent-browser press Escape

# Modifier + key (Ctrl+P)
agent-browser keydown Control
agent-browser press p
agent-browser keyup Control

# Double modifier (Ctrl+Shift+P)
agent-browser keydown Control && agent-browser keydown Shift && agent-browser press p && agent-browser keyup Shift && agent-browser keyup Control
```

## Essential Commands

| Category | Command | Description |
|----------|---------|-------------|
| **Navigation** | `open <url>` | Navigate to URL |
| | `back` / `forward` | Navigate history |
| | `reload` | Reload page |
| | `get url` / `get title` | Get current URL/title |
| **Inspection** | `snapshot -i` | Interactive elements only |
| | `snapshot -i -C` | Include cursor-interactive |
| | `snapshot --json` | JSON output |
| | `snapshot --full` | All elements (links, buttons, inputs) |
| | `snapshot --nav` | All navigable elements (links) |
| | `eval "js"` | Run JavaScript |
| | `style <selector>` | Get computed CSS styles |
| | `health` | Page health report |
| | `network` | Network request analysis |
| **Interaction** | `click @e1` | Click by ref |
| | `fill @e1 "text"` | Clear and fill input |
| | `type @e1 "text"` | Type without clearing |
| | `press Enter` | Press key |
| | `scroll down 500` | Scroll page |
| **Debug** | `console` | View console messages |
| | `errors` | View page errors |
| | `screenshot [path]` | Take screenshot |
| | `deep-check` | Full page analysis |
| **Control** | `close` | Close browser |

## Snapshot Options

| Flag | Description |
|------|-------------|
| `-i` | Interactive elements only (recommended) |
| `-C` | Include cursor-interactive (onclick, cursor:pointer) |
| `-c` | Compact output |
| `-d N` | Limit depth to N levels |
| `-s "selector"` | Scope to CSS selector |
| `--json` | JSON output for parsing |
| `--full` | ALL elements (links, buttons, inputs, images) |
| `--nav` | All navigation elements (a, area, link) |

## Key Names

| Key | Name |
|-----|------|
| Enter | `Enter` |
| Tab | `Tab` |
| Escape | `Escape` |
| Backspace | `Backspace` |
| Backtick | `Backquote` |
| Arrow Keys | `ArrowUp`, `ArrowDown`, `ArrowLeft`, `ArrowRight` |

## Selector Types

| Type | Example | Notes |
|------|---------|-------|
| **Ref** | `@e1` | Recommended - from snapshot |
| CSS ID | `#submit` | |
| CSS Class | `.button` | |
| Text | `text="Submit"` | |
| XPath | `xpath=//button` | |
| Role | `role=button --name "Submit"` | ARIA role |

---

## 强制检查清单 ⚠️

**每次测试必须执行以下检查，不能只依赖截图：**

### 1. 页面加载检查
```bash
# 基本检查
agent-browser open "$URL"
sleep 5

# ✅ 必须: 获取交互元素
agent-browser snapshot -i

# ✅ 必须: 检查控制台日志
agent-browser console

# ✅ 必须: 检查JS错误
agent-browser errors
```

### 2. 代码层面检查 (不能只看截图)
```bash
# ✅ 必须: 检查脚本加载数量
agent-browser eval "document.scripts.length"

# ✅ 必须: 检查关键DOM元素是否存在
agent-browser eval "document.querySelector('#app') !== null"

# ✅ 必须: 检查网络请求状态
agent-browser network

# ✅ 必须: 检查图片加载状态
agent-browser eval "
 Array.from(document.images).map(img => ({
   src: img.src, 
   complete: img.complete, 
   naturalWidth: img.naturalWidth
 }))
"
```

### 3. 页面健康度检查
```bash
# 综合健康报告
agent-browser health

# 检查输出应包含:
# - JavaScript错误数量
# - 加载失败资源数量
# - 图片加载状态
# - 控制台警告数量
```

### 4. 深度检查模式 (推荐)
```bash
# 一次性执行所有检查
agent-browser deep-check

# 输出包含:
# - 交互元素列表
# - 控制台日志
# - JS错误
# - 网络请求
# - 图片状态
# - DOM结构摘要
```

---

## 页面结构分析

### 问题3解决: 首次获取完整页面结构

**问题**: 首次只找顶部导航，需要多轮才能找到左侧菜单

**解决方案**: 使用 `--full` 或 `--nav` 选项

```bash
# 获取页面所有元素 (links, buttons, inputs, images)
agent-browser snapshot --full

# 获取所有导航元素 (链接)
agent-browser snapshot --nav
```

### 获取所有可导航元素

```bash
# 获取页面所有链接 (包括左侧菜单)
agent-browser snapshot --nav

# 输出示例:
# @n1 <a href="/dashboard">Dashboard</a>
# @n2 <a href="/users">用户管理</a>
# @n3 <a href="/settings">设置</a>
# @n4 <a href="/api/docs">API文档</a>
```

### 获取元素样式

```bash
# 获取元素的Computed Style
agent-browser style ".sidebar"
agent-browser style "nav"
agent-browser style ".menu-item"

# 检查主题相关的CSS属性
agent-browser eval "
  (function() {
    const style = window.getComputedStyle(document.body);
    return {
      backgroundColor: style.backgroundColor,
      color: style.color,
      fontFamily: style.fontFamily
    };
  })()
"
```

### 图片和图形元素检查

```bash
# 检查所有图片加载状态
agent-browser eval "
  (function() {
    const images = Array.from(document.images).map(img => ({
      src: img.src.split('/').pop(),
      complete: img.complete,
      naturalWidth: img.naturalWidth,
      naturalHeight: img.naturalHeight,
      error: img.error
    }));
    return images;
  })()
"

# 检查Canvas元素
agent-browser eval "
  document.querySelectorAll('canvas').length
"

# 检查SVG元素
agent-browser eval "
  Array.from(document.querySelectorAll('svg')).map(s => ({
    width: s.getAttribute('width'),
    height: s.getAttribute('height'),
    viewBox: s.getAttribute('viewBox')
  }))
"
```

### 网络请求分析

```bash
# 获取网络请求状态
agent-browser network

# 手动检查失败请求
agent-browser eval "
  (function() {
    // 需要 Performance API 支持
    if (!window.performance || !window.performance.getEntries) {
      return 'Performance API not supported';
    }
    return window.performance.getEntries().map(entry => ({
      name: entry.name,
      type: entry.initiatorType,
      status: entry.responseStatus,
      duration: entry.duration.toFixed(2)
    }));
  })()
"
```

---

## Common Patterns

### 标准页面加载测试 (含强制检查)

```bash
agent-browser open "http://localhost:8080"
sleep 5

# ✅ 1. 获取交互元素
agent-browser snapshot -i

# ✅ 2. 获取所有链接/导航 (解决问题3)
agent-browser snapshot --nav

# ✅ 3. 检查控制台 (解决问题2)
agent-browser console

# ✅ 4. 检查JS错误 (解决问题2)
agent-browser errors

# ✅ 5. 检查脚本加载
agent-browser eval "document.scripts.length"

# ✅ 6. 检查图片状态 (解决问题1)
agent-browser eval "
  Array.from(document.images).filter(img => !img.complete).length
"

# 截图
agent-browser screenshot
```

### 完整页面分析测试

```bash
# 深度检查 - 一次执行所有检查 (推荐)
agent-browser open "http://localhost:8080"
sleep 5

# 方法1: 使用深度检查命令
agent-browser deep-check

# 方法2: 手动完整检查
agent-browser snapshot -i        # 交互元素
agent-browser snapshot --nav     # 所有链接
agent-browser console           # 控制台
agent-browser errors            # JS错误
agent-browser screenshot        # 截图
```

### Test Page Load
```bash
agent-browser open "http://localhost:8080"
sleep 5
agent-browser snapshot -i
agent-browser console | grep -i error
```

### Test Form
```bash
agent-browser snapshot -i
agent-browser fill @e1 "username"
agent-browser fill @e2 "password"
agent-browser click @e3  # Submit
sleep 2
agent-browser get url    # Verify redirect
```

### Test Keyboard Shortcuts
```bash
# Open command palette
agent-browser keydown Control && agent-browser keydown Shift && agent-browser press p && agent-browser keyup Shift && agent-browser keyup Control
sleep 1
agent-browser snapshot -i
agent-browser press Escape  # Close
```

### Debug Page Issues
```bash
agent-browser errors                 # Check JS errors
agent-browser console                # View all console
agent-browser eval "document.querySelectorAll('*').length"  # Count elements
```

## Sessions

Multiple isolated browser instances:
```bash
agent-browser --session test1 open site-a.com
agent-browser --session test2 open site-b.com
agent-browser session list
```

## Best Practices

1. **Use refs (@e1) instead of CSS selectors** - More reliable after page changes
2. **Always snapshot before interacting** - Get fresh refs
3. **Check console for errors** - `agent-browser console`
4. **Use -i flag** - Only interactive elements, reduces noise
5. **Wait after navigation** - Pages need time to load
6. **Close browser when done** - `agent-browser close`

### 强制检查原则 ⚠️

**必须执行多重验证，不能只依赖截图：**

| 检查类型 | 命令 | 原因 |
|----------|------|------|
| 交互元素 | `snapshot -i` | 了解可点击元素 |
| 所有导航 | `snapshot --nav` | 快速找到目标链接 |
| 控制台 | `console` | 发现潜在问题 |
| JS错误 | `errors` | 发现运行时错误 |
| 脚本加载 | `eval "document.scripts.length"` | 确认JS加载 |
| 图片状态 | `eval "..."` | 发现图片加载失败 |
| 截图 | `screenshot` | 视觉验证 |

**为什么不能只截图：**
- ❌ 截图正常 ≠ 代码没问题 (JS错误可能已发生)
- ❌ 截图正常 ≠ 网络请求成功 (API可能已失败)
- ❌ 截图正常 ≠ 图片已加载 (可能显示占位符)
- ❌ 截图正常 ≠ 无控制台警告 (警告可能被忽略)

## Common Pitfalls

| Issue | Solution |
|-------|----------|
| Elements not found | Use `snapshot -i -C` for cursor-interactive |
| 401 Unauthorized | Verify token in URL is URL-encoded |
| Page not fully loaded | Increase sleep time after `open` |
| Shadow DOM elements | Try `eval` to access shadow roots |

### 针对性解决方案

#### 问题1: 截图无法感知布局和颜色变化

| 症状 | 解决方案 |
|------|----------|
| 图片显示空白 | 检查 `document.images` 加载状态 |
| 样式变化不明显 | 使用 `eval` 获取Computed Style |
| 图表不渲染 | 检查 `canvas` 元素是否存在 |
| 主题切换无效 | 检查 `document.body` 的 computed style |

```bash
# 检查图片状态
agent-browser eval "Array.from(document.images).filter(i => !i.complete).length"

# 检查样式
agent-browser style "body"
agent-browser eval "getComputedStyle(document.body).backgroundColor"
```

#### 问题2: 排查错误不会举一反三

| 症状 | 解决方案 |
|------|----------|
| 截图正常但功能异常 | 必须检查 `console` 和 `errors` |
| 页面白屏 | 检查 JS 脚本加载数量 |
| API 请求失败 | 检查 `network` |

```bash
# 完整检查 (不依赖截图)
agent-browser console
agent-browser errors
agent-browser eval "document.scripts.length"
```

#### 问题3: 首次只找顶部导航

| 症状 | 解决方案 |
|------|----------|
| 找不到左侧菜单 | 使用 `snapshot --nav` 获取所有链接 |
| 找不到目标页面 | 使用 `snapshot --full` 获取全部元素 |

```bash
# 首次就获取所有导航元素
agent-browser snapshot --nav

# 或获取全部元素
agent-browser snapshot --full
```

---

# Universal Testing Patterns

## 测试模式分类

根据项目类型选择合适的测试模式：

```
项目类型
├── Web应用 (SPA/SSR)
│   ├── 登录认证测试
│   ├── 页面导航测试
│   ├── 表单交互测试
│   └── API集成测试
├── 管理后台
│   ├── CRUD操作测试
│   ├── 表格列表测试
│   ├── 弹窗对话框测试
│   └── 权限控制测试
├── 编辑器/Web IDE
│   ├── 文件操作测试
│   ├── 代码编辑测试
│   └── 终端集成测试
└── 响应式/多端
    ├── 布局适配测试
    └── 主题切换测试
```

## Quick Start Templates

### 1. Web应用基础测试模板

```bash
#!/bin/bash
# 通用Web应用测试模板

APP_URL="${APP_URL:-http://localhost:8080}"
WAIT_TIME="${WAIT_TIME:-5}"

echo "=== Web应用基础测试 ==="
echo "测试URL: $APP_URL"

# 1. 打开页面
agent-browser open "$APP_URL"
sleep $WAIT_TIME

# 2. 检查页面加载
agent-browser snapshot -i

# 3. 检查控制台错误
ERRORS=$(agent-browser errors 2>&1)
if echo "$ERRORS" | grep -qi "error"; then
    echo "❌ 发现JS错误: $ERRORS"
fi

# 4. 获取页面标题
agent-browser get title

# 5. 关闭浏览器
agent-browser close
```

### 2. 登录认证测试模板

```bash
#!/bin/bash
# 登录认证测试模板

APP_URL="${APP_URL:-http://localhost:8080}"
USERNAME="${USERNAME:-admin}"
PASSWORD="${PASSWORD:-123456}"

agent-browser open "$APP_URL"
sleep 5

# 获取登录表单元素
agent-browser snapshot -i

# 填写用户名密码并登录
agent-browser fill @e1 "$USERNAME"   # 用户名输入框
agent-browser fill @e2 "$PASSWORD"    # 密码输入框
agent-browser click @e3              # 登录按钮

sleep 3

# 验证登录成功
CURRENT_URL=$(agent-browser get url)
if echo "$CURRENT_URL" | grep -q "login"; then
    echo "❌ 登录失败，停留在登录页"
    agent-browser screenshot /tmp/login-fail.png
else
    echo "✅ 登录成功: $CURRENT_URL"
fi

agent-browser close
```

### 3. 管理后台CRUD测试模板

```bash
#!/bin/bash
# 管理后台CRUD测试模板

# 测试流程: 列表页 → 新建 → 编辑 → 删除

agent-browser open "http://localhost:8080/list"
sleep 5
agent-browser snapshot -i

# === CREATE ===
echo "=== 测试新建功能 ==="
agent-browser click @e1              # 新建按钮
sleep 2
agent-browser snapshot -i
agent-browser fill @e1 "测试数据"     # 填写表单
agent-browser click @e2              # 提交
sleep 2

# === READ ===
echo "=== 测试列表展示 ==="
agent-browser snapshot -i
# 验证数据出现在列表中
agent-browser eval "document.body.innerText" | grep -q "测试数据"

# === UPDATE ===
echo "=== 测试编辑功能 ==="
agent-browser click @e3              # 编辑按钮
sleep 2
agent-browser fill @e1 "修改后数据"   # 修改
agent-browser click @e2              # 保存
sleep 2

# === DELETE ===
echo "=== 测试删除功能 ==="
agent-browser click @e4              # 删除按钮
sleep 1
agent-browser snapshot -i
# 确认删除对话框
agent-browser click @e5              # 确认删除
sleep 2

agent-browser close
```

### 4. 主题切换测试模板

```bash
#!/bin/bash
# 主题切换测试模板

agent-browser open "http://localhost:8080/settings"
sleep 5
agent-browser snapshot -i

# 截图保存当前主题状态
agent-browser screenshot /tmp/theme-before.png

# 切换到深色主题
agent-browser click @e1              # 主题切换按钮
sleep 2

# 截图保存切换后状态
agent-browser screenshot /tmp/theme-dark.png

# 验证深色主题元素
agent-browser eval "
    (function() {
        const body = document.body;
        const bgColor = window.getComputedStyle(body).backgroundColor;
        return { bgColor };
    })()
"

# 切换到浅色主题
agent-browser click @e1
sleep 2

agent-browser screenshot /tmp/theme-light.png
agent-browser close
```

### 5. 表格列表测试模板

```bash
#!/bin/bash
# 表格列表测试模板

agent-browser open "http://localhost:8080/list"
sleep 5
agent-browser snapshot -i

# 测试分页
echo "=== 测试分页 ==="
agent-browser click @e1              # 下一页
sleep 2
agent-browser snapshot -i

# 测试排序
echo "=== 测试排序 ==="
agent-browser click @e2              # 排序按钮
sleep 1

# 测试筛选
echo "=== 测试筛选 ==="
agent-browser fill @e3 "关键词"       # 筛选输入框
agent-browser press Enter
sleep 2

# 测试展开行
echo "=== 测试行展开 ==="
agent-browser click @e4              # 展开按钮
sleep 1
agent-browser snapshot -i

agent-browser close
```

## 测试用例设计原则

### AAA模式 (Arrange-Act-Assert)

```bash
# Arrange: 准备测试数据和环境
agent-browser open "http://localhost:8080/page"
sleep 5

# Act: 执行操作
agent-browser fill @e1 "test"
agent-browser click @e2

# Assert: 验证结果
agent-browser get url
agent-browser eval "document.body.innerText" | grep -q "预期结果"
```

### 测试数据管理

```bash
# 使用环境变量传递测试数据
export TEST_USERNAME="testuser"
export TEST_EMAIL="test@example.com"

# 使用时间戳生成唯一数据
TIMESTAMP=$(date +%s)
UNIQUE_NAME="test_${TIMESTAMP}"
```

### 等待策略

| 场景 | 等待方式 | 示例 |
|------|---------|------|
| 页面导航 | 固定等待 | `sleep 5` |
| API加载 | 检查加载状态 | `agent-browser eval "document.querySelector('.loading').style.display"` |
| 动画过渡 | 等待过渡完成 | `sleep 1` (CSS transition 通常 300ms) |
| 弹窗动画 | 等待DOM出现 | `agent-browser snapshot -i` |

## 调试技巧

### 常见问题快速诊断

```bash
# 1. 页面空白
agent-browser console | grep -i "failed\|error\|cannot"
agent-browser eval "document.readyState"

# 2. 点击无效
agent-browser snapshot -i -C          # 检查元素是否可点击
agent-browser eval "element.getBoundingClientRect()"

# 3. 元素不存在
agent-browser snapshot                 # 获取完整快照
agent-browser eval "document.querySelectorAll('.class').length"

# 4. 样式问题
agent-browser eval "window.getComputedStyle(document.querySelector('.element')).backgroundColor"

# 5. 网络请求失败
agent-browser console | grep -E "fetch|xhr|404|500"
```

### 元素定位技巧

```bash
# 使用文本内容定位
agent-browser click "text=提交按钮"

# 使用模糊匹配
agent-browser click "text*=删除"

# 使用XPath
agent-browser click "xpath=//button[@class='submit']"

# 使用ARIA角色
agent-browser click "role=button --name '提交'"
```

### 状态验证模式

```bash
# 验证加载完成
agent-browser eval "
    (function() {
        const loading = document.querySelector('.loading');
        return !loading || loading.style.display === 'none';
    })()
"

# 验证弹窗打开
agent-browser eval "
    (function() {
        const modal = document.querySelector('.arco-modal');
        return modal && !modal.classList.contains('arco-modal-hidden');
    })()
"

# 验证表单验证错误
agent-browser eval "
    (function() {
        const error = document.querySelector('.arco-form-item-error');
        return error ? error.innerText : null;
    })()
"
```

## 测试脚本最佳实践

### 1. 错误处理

```bash
#!/bin/bash
set -e  # 遇到错误立即退出

# 或者使用错误处理
trap 'echo "测试失败，截图: /tmp/fail.png"; agent-browser screenshot /tmp/fail.png; agent-browser close; exit 1' ERR

agent-browser open "http://localhost:8080"
# ... 测试代码
agent-browser close
```

### 2. 日志输出

```bash
log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

log "开始测试..."
log "测试完成"
```

### 3. 截图命名规范

```bash
# 命名格式: {功能}_{操作}_{状态}.png
agent-browser screenshot /tmp/login-success.png
agent-browser screenshot /tmp/form-filled.png
agent-browser screenshot /tmp/error-state.png
```

### 4. 条件跳过

```bash
# 检查功能是否存在
agent-browser snapshot -i
if agent-browser eval "document.querySelector('.advanced-feature')"; then
    echo "高级功能存在，测试它"
else
    echo "高级功能不存在，跳过"
fi
```

## 快速验证清单

每次测试前快速检查：

```
□ 服务是否运行中?
□ 页面URL是否正确?
□ Token/登录状态是否有效?
□ 测试数据是否准备好?
□ 截图目录是否存在?
```

---

# UI Framework Specific Tips

## Arco Design Vue 测试技巧

### 常见组件选择器

```bash
# Arco Design 组件选择器

# 输入框
agent-browser fill "input[placeholder*='请输入']" "value"

# 按钮
agent-browser click "button:has-text('提交')"
agent-browser click "button:has-text('取消')"

# 表格
agent-browser click "table tbody tr:first-child .arco-table-cell"  # 点击行
agent-browser click ".arco-table-pagination button.arco-pagination-next"  # 分页

# 弹窗
agent-browser click ".arco-modal button:has-text('确定')"

# 下拉选择
agent-browser click ".arco-select-view"
agent-browser snapshot -i
agent-browser click "text=选项1"  # 选择项

# 抽屉 Drawer
agent-browser snapshot -i
agent-browser click ".arco-drawer-close"

# 表单验证
agent-browser eval "
    document.querySelectorAll('.arco-form-item-error-message').length > 0
"
```

### 主题切换验证

```bash
# Arco Design 主题测试

# 检查当前主题
agent-browser eval "
    document.querySelector('html').getAttribute('arco-theme')
"

# 切换主题
agent-browser click "button:has-text('主题')"
sleep 1

# 验证主题应用到所有组件
agent-browser eval "
    (function() {
        const body = document.body;
        const bg = window.getComputedStyle(body).backgroundColor;
        const components = {
            modal: document.querySelector('.arco-modal'),
            dropdown: document.querySelector('.arco-dropdown'),
            tooltip: document.querySelector('.arco-tooltip'),
        };
        return { bg, components };
    })()
"
```

## Vue 3 测试技巧

### 响应式数据验证

```bash
# 验证Vue响应式更新
agent-browser eval "
    (function() {
        // 获取Vue组件实例
        const el = document.querySelector('#app');
        const vm = el.__vue_app__ || el._vnode;
        
        // 获取响应式数据
        const count = vm.config.globalProperties.\$store?.state?.count 
                   || vm.exposed?.count;
        
        return { count };
    })()
"

# 验证v-if/v-show条件
agent-browser eval "
    (function() {
        const el = document.querySelector('.dynamic-element');
        return {
            exists: !!el,
            visible: el && (el.style.display !== 'none' && !el.hasAttribute('hidden'))
        };
    })()
"
```

### 路由测试

```bash
# Vue Router 测试

# 获取当前路由
agent-browser eval "window.location.hash"
agent-browser eval "window.history.state.current"

# 验证路由跳转
agent-browser click "a[href='/path']"
sleep 2
agent-browser get url

# 验证路由守卫跳转
agent-browser eval "
    (function() {
        const url = window.location.href;
        const expected = '/expected-path';
        return { current: url, expected, match: url.includes(expected) };
    })()
"
```

## React 测试技巧

### 组件状态验证

```bash
# React 组件状态

# 获取组件props
agent-browser eval "
    (function() {
        // 使用React DevTools或在控制台获取
        const root = document.getElementById('root');
        return root._reactRootContainer?._internalRoot?.current?.memoizedState;
    })()
"

# 验证useState
agent-browser eval "
    document.body.innerText.includes('状态值')
"

# 验证useEffect触发
agent-browser console | grep -i "useEffect"
```

### 状态管理验证

```bash
# Redux/Zustand 状态
agent-browser eval "
    window.__REDUX_DEVTOOLS_EXTENSION__
"
```

---

# Advanced Testing Patterns

## 1. 批量测试脚本

```bash
#!/bin/bash
# 批量测试多个页面

PAGES=(
    "/"
    "/login"
    "/dashboard"
    "/settings"
    "/help"
)

BASE_URL="http://localhost:8080"

for page in "${PAGES[@]}"; do
    echo "=== Testing $page ==="
    agent-browser open "$BASE_URL$page"
    sleep 3
    
    ERRORS=$(agent-browser errors 2>&1)
    if echo "$ERRORS" | grep -qi "error"; then
        echo "❌ $page: 发现错误"
        agent-browser screenshot "/tmp/error-$page.png"
    else
        echo "✅ $page: 正常"
    fi
    
    agent-browser close
    sleep 1
done
```

## 2. 数据驱动测试

```bash
#!/bin/bash
# 数据驱动测试

TEST_DATA_CSV="test_data.csv"

while IFS=',' read -r name email role; do
    echo "=== Testing user: $name ==="
    agent-browser open "http://localhost:8080/user/add"
    sleep 3
    
    agent-browser fill @e1 "$name"
    agent-browser fill @e2 "$email"
    agent-browser fill @e3 "$role"
    agent-browser click @e4
    
    sleep 2
done < <(tail -n +2 "$TEST_DATA_CSV")

agent-browser close
```

## 3. 对比测试

```bash
#!/bin/bash
# 对比测试: 修改前 vs 修改后

# 修改前截图
agent-browser open "http://localhost:8080/page"
sleep 5
agent-browser screenshot "/tmp/before.png"

# 模拟修改操作
# ...

# 修改后截图
agent-browser open "http://localhost:8080/page"
sleep 5
agent-browser screenshot "/tmp/after.png"

# 使用diff比较 (如果有工具)
# compare /tmp/before.png /tmp/after.png /tmp/diff.png
```

## 4. 性能测试辅助

```bash
#!/bin/bash
# 页面性能测试

agent-browser open "http://localhost:8080/page"
sleep 5

# 获取页面加载时间
agent-browser eval "
    (function() {
        const perfData = window.performance.timing;
        return {
            loadTime: perfData.loadEventEnd - perfData.navigationStart,
            domReady: perfData.domContentLoadedEventEnd - perfData.navigationStart,
            firstPaint: perfData.firstPaint,
        };
    })()
"

# 获取Core Web Vitals
agent-browser eval "
    (function() {
        return new Promise((resolve) => {
            if ('web-vitals' in window) {
                // web-vitals 库已加载
            }
            resolve({ 
                cls: 0, 
                lcp: 0, 
                fid: 0 
            });
        });
    })()
"
```

## 参考资源

### 测试框架
- **Playwright**: https://playwright.dev/ - 微软出品，支持多浏览器
- **Puppeteer**: https://pptr.dev/ - Google Chrome团队维护
- **Cypress**: https://www.cypress.io/ - 现代Web测试框架
- **WebdriverIO**: https://webdriver.io/ - Selenium WebDriver的Node.js封装

### API测试工具
- **REST Assured**: Java REST API测试
- **Supertest**: Node.js HTTP测试
- **HttpRunner**: Python测试框架
- **httpie**: 命令行HTTP客户端 https://httpie.io/

### 辅助工具
- **jq**: 命令行JSON处理器 https://stedolan.github.io/jq/
- **Lighthouse**: 性能测试 https://developer.chrome.com/docs/lighthouse
- **Percy**: 视觉回归测试 https://percy.io/
- **Applitools**: AI视觉测试 https://applitools.com/
