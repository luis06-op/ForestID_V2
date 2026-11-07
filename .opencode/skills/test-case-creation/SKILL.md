---
name: test-case-creation
description: 当进行功能测试时，帮助快速分析、总结测试方法并形成结构化测试用例，方便跨项目复用
license: Apache-2.0
---

# Test Case Creation

帮助AI在进行功能测试时，快速分析、总结测试方法，形成结构化的测试用例文档，方便跨项目复用。

## 技能目标

| 目标 | 说明 |
|------|------|
| **及时记录** | 测试过程中随时记录，不事后补记 |
| **可复用** | 抽象成通用步骤，避免硬编码具体项目数据 |
| **可追溯** | 记录环境、时间、版本信息 |
| **可搜索** | 分类清晰，便于快速查找 |

**核心原则**：当进行任何功能测试时（API测试、UI测试、E2E测试等），自动将测试过程形成结构化文档，保存到项目 `tests/` 目录下，方便下次复用。

## 目录结构

测试用例是可执行的脚本文件，保存在项目的 `tests/test-cases/` 目录下：

```
{项目根目录}/tests/
├── run.sh                    # 统一运行器
├── test-cases/              # 测试用例目录
│   ├── api/                # API测试
│   │   ├── webdav-list.sh
│   │   └── compress.sh
│   ├── ui/                 # UI测试
│   │   └── login.sh
│   └── performance/         # 性能测试
│       └── benchmark.sh
├── examples/                 # 各类语言示例（按需）
│   ├── php/                # PHP项目示例
│   ├── go/                 # Go项目示例
│   └── python/             # Python项目示例
└── reports/                  # 测试报告
```

**注意**：
- 优先使用Shell脚本（`.sh`）
- 其他语言根据项目需要自行创建（如PHP项目用PHP测试）
- 脚本头部包含知识库注释

## 通用变量占位符

| 占位符 | 说明 | 示例值 |
|--------|------|--------|
| `$BASE_URL` | 应用基础URL | http://localhost:8080 |
| `$TOKEN` | 认证Token | eyJhbGciOiJIUzI1NiIs... |
| `$API_BASE` | API基础路径 | /api/v1 |
| `$TEST_USER` | 测试用户 | testuser |
| `$TEST_DATA` | 测试数据 | {"name":"test"} |
| `$WAIT_TIME` | 等待时间(秒) | 5 |
| `$PROJECT_DIR` | 项目根目录 | /path/to/project |

## 认证方案

测试用例支持多种认证方式：

### 1. Bearer Token (JWT)

```bash
# Header认证
-H "Authorization: Bearer $TOKEN"
```

### 2. Basic Auth

```bash
# Base64编码的用户名密码
-H "Authorization: Basic $(echo -n 'user:password' | base64)"
```

### 3. API Key

```bash
# 常见形式
-H "X-API-Key: $API_KEY"
# 或
-H "X-Auth-Token: $API_KEY"
```

### 4. Cookie认证

```bash
# Cookie
-H "Cookie: session_id=$SESSION_ID"
```

### 5. OAuth2

```bash
# Access Token (与Bearer相同)
-H "Authorization: Bearer $ACCESS_TOKEN"

# 或携带refresh_token
-d "grant_type=refresh_token&refresh_token=$REFRESH_TOKEN"
```

### 认证变量

| 变量 | 说明 | 用途 |
|------|------|------|
| `$TOKEN` | 主Token | Bearer Token认证 |
| `$API_KEY` | API密钥 | API Key认证 |
| `$SESSION_ID` | Session ID | Cookie认证 |
| `$REFRESH_TOKEN` | 刷新Token | OAuth2刷新 |
| `$USERNAME` | 用户名 | Basic Auth |
| `$PASSWORD` | 密码 | Basic Auth |

### 认证获取方式

| 认证类型 | 获取方式 |
|----------|----------|
| Bearer Token | 登录API返回、kubeconfig、ServiceAccount |
| API Key | 系统设置、开发者设置 |
| Session ID | 登录后Cookie |
| OAuth2 | 授权流程获取 |

## 触发场景

| 场景 | 执行动作 |
|------|----------|
| 用户要求"测试一下xxx" | 创建测试用例并执行 |
| 发现Bug需要复现 | 记录复现步骤形成用例 |
| 完成功能验证 | 总结测试方法形成文档 |
| 用户要求"查找相关测试" | 搜索已有用例 |

## 工作流程

```
接收测试任务
    │
    ▼
分析测试需求
    ├─ 测试类型 (API/UI/E2E/Integration/Stress)
    ├─ 测试优先级 (P0/P1/P2)
    └─ 涉及模块
    │
    ▼
搜索现有测试用例
    │ (在项目 tests/test-cases/ 目录下搜索)
    │
    ▼
选择模板并创建/更新用例
    ├─ 填写测试步骤
    ├─ 记录预期结果
    └─ 添加验证方法
    │
    ▼
保存到项目tests目录
    │ tests/test-cases/{类型}/{模块}/{名称}.md
    │
    ▼
更新项目索引
    │
    ▼
输出执行摘要
```

---

## 测试用例结构

每个测试用例是一个可执行的脚本文件，知识库通过注释形式包含在脚本头部：

```bash
#!/bin/bash
#========================================
# 测试用例: W7Panel登录测试
#========================================
#
# ## 测试信息
# | 项目 | 内容 |
# |------|------|
# | 测试类型 | UI |
# | 优先级 | P0 |
#
# ## 测试目的
# 验证用户登录功能
#
# ## 前置条件
# - [ ] 服务已启动
# - [ ] 验证码已禁用
#
# ## 测试步骤
# 1. 打开登录页
# 2. 填写用户名密码
# 3. 点击登录
# 4. 验证跳转
#
# ## 预期结果
# - 登录成功，跳转到首页
#
#========================================

# 测试代码开始
BASE_URL="${BASE_URL:-http://localhost:8080}"

agent-browser open "$BASE_URL"
# ...
```

**注意**：知识库以 `#` 注释开头，使用Markdown格式，方便阅读和检索。

## 分类标准

### 按测试类型

| 类型 | 说明 |
|------|------|
| API | 接口测试、API集成测试 |
| UI | 页面测试、组件测试 |
| E2E | 端到端测试、用户流程 |
| Integration | 多个服务集成测试 |
| Stress | 压力测试、性能测试 |

### 按功能模块

| 模块 | 说明 |
|------|------|
| authentication | 登录、注册、权限 |
| data-query | 列表、搜索、筛选 |
| data-modification | 创建、更新、删除 |
| file-operations | 文件上传、下载、删除 |
| settings | 系统配置、用户设置 |

### 按优先级

| 优先级 | 说明 |
|--------|------|
| P0 | 核心流程，必须通过 |
| P1 | 重要功能，影响使用 |
| P2 | 边缘功能，影响较小 |

## 输出格式

创建测试用例后，输出标准摘要：

```
✅ 测试用例已创建: examples/authentication/login.md

📋 测试摘要
├── 类型: API
├── 优先级: P0
├── 步骤数: 3
├── 预计耗时: 30秒
└── 依赖: $TOKEN, $BASE_URL

📝 快速执行
curl -X POST "$BASE_URL/api/login" -d '{"user":"admin"}'

🔍 查找相关测试
bash scripts/search.sh login
```

---

## 模板选择

| 模板 | 说明 |
|------|------|
| `.sh` 脚本 | Shell测试脚本（优先使用） |

**注意**：
- 优先使用Shell脚本（`.sh`）
- 其他语言根据项目需要自行创建（如PHP项目用PHP测试）
- 脚本头部包含知识库注释

## 辅助命令

```bash
# 创建新测试用例 (保存到项目 tests/test-cases/)
bash scripts/new-test.sh api login

# 搜索测试用例
bash scripts/search.sh login

# 搜索特定类型
bash scripts/search.sh --type api

# 搜索特定优先级
bash scripts/search.sh --priority P0

# 生成索引
bash scripts/index.sh
```

## 最佳实践

1. **测试前先搜索** - 避免重复创建已有用例
2. **使用通用变量** - 不硬编码具体值
3. **记录边界条件** - 便于回归测试
4. **及时更新** - 发现问题立即更新用例
5. **保持简洁** - 步骤清晰，避免冗余

---

## 测试报告

当测试完成后，需要生成测试报告，保存到项目的 `tests/reports/` 目录下。

### 报告目录结构

```
{项目根目录}/tests/
├── test-cases/              # 测试用例
│   ├── api/
│   ├── ui/
│   └── performance/
├── reports/                  # 测试报告 (按分类)
│   ├── api/                # API测试报告
│   │   └── 2024-01-01-login.md
│   ├── ui/                 # UI测试报告
│   │   └── 2024-01-01-panel.md
│   └── performance/        # 性能测试报告
│       └── 2024-01-01-webdav.md
└── INDEX.md                # 测试用例索引
```

### 报告模板

```markdown
---
id: {唯一标识}
title: {测试报告标题}
type: API | UI | Performance
date: {测试日期}
status: PASS | FAIL | PARTIAL
---

# {测试报告标题}

## 测试概要

| 项目 | 内容 |
|------|------|
| 测试日期 | YYYY-MM-DD |
| 测试类型 | API / UI / Performance |
| 测试结果 | PASS / FAIL / PARTIAL |
| 测试人员 | AI |

## 测试环境

| 环境 | 说明 |
|------|------|
| 服务地址 | $BASE_URL |
| Token | $TOKEN |

## 测试结果

### 通过项 ✅

- [ ] 测试项1
- [ ] 测试项2

### 失败项 ❌

- [ ] 测试项3
  - 错误信息: xxx
  - 截图: /path/to/screenshot.png

### 未通过项 ⚠️

- [ ] 测试项4
  - 原因: 待确认

## 问题清单

| 问题ID | 严重程度 | 描述 | 状态 |
|--------|----------|------|------|
| BUG-001 | High | 描述 | Open |
| BUG-002 | Medium | 描述 | Resolved |

## 测试截图

- [截图1](./screenshots/1.png)
- [截图2](./screenshots/2.png)

## 修改建议

{改进建议}

## 相关测试用例

- [测试用例链接](../test-cases/xxx.md)
```

### 报告分类

| 类型 | 目录 | 说明 |
|------|------|------|
| API | reports/api/ | 接口测试报告 |
| UI | reports/ui/ | 页面测试报告 |
| Performance | reports/performance/ | 性能测试报告 |
| Regression | reports/regression/ | 回归测试报告 |

### 报告命名规范

```
{日期}-{模块}-{测试类型}.md

示例:
2024-01-01-login-api.md
2024-01-02-panel-ui.md
2024-01-03-webdav-perf.md
```

---

## 与agent-browser联动

测试用例通过 [agent-browser](../agent-browser/SKILL.md) 执行。

### 常用命令映射

| 测试操作 | agent-browser命令 |
|----------|------------------|
| 打开页面 | `agent-browser open "$BASE_URL"` |
| 获取元素 | `agent-browser snapshot -i` |
| 点击元素 | `agent-browser click @e1` |
| 填写表单 | `agent-browser fill @e1 "value"` |
| 验证文本 | `agent-browser eval "document.body.innerText"` |
| 检查错误 | `agent-browser errors` |
| 截图 | `agent-browser screenshot /tmp/test.png` |
| 关闭 | `agent-browser close` |

### 测试用例执行示例

```bash
#!/bin/bash
# 执行测试用例

BASE_URL="${BASE_URL:-http://localhost:8080}"
WAIT_TIME="${WAIT_TIME:-5}"

# 打开页面
agent-browser open "$BASE_URL"
sleep $WAIT_TIME

# 获取可交互元素
agent-browser snapshot -i

# 执行操作
agent-browser fill @e1 "testuser"
agent-browser fill @e2 "password"
agent-browser click @e3

sleep 2

# 验证结果
agent-browser get url

# 关闭浏览器
agent-browser close
```

### API测试执行

```bash
#!/bin/bash
# 执行API测试

BASE_URL="${BASE_URL:-http://localhost:8080}"
TOKEN="${TOKEN:-your_token}"

# GET请求
curl -X GET "$BASE_URL/api/data" \
  -H "Authorization: Bearer $TOKEN"

# POST请求
curl -X POST "$BASE_URL/api/data" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"test"}'
```
