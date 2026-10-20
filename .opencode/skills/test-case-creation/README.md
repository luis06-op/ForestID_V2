# Test Case Creation Skill

测试用例创建技能 - 帮助快速创建和管理测试用例。

## 概述

当进行功能测试时，此技能帮助AI快速分析、总结测试方法，形成结构化的测试用例文档。测试用例保存在项目 `tests/test-cases/` 目录下，方便跨项目复用。

## 技能目标

- **及时记录**: 测试过程中随时记录，不事后补记
- **可复用**: 抽象成通用步骤，避免硬编码具体项目数据
- **可追溯**: 记录环境、时间、版本信息
- **可搜索**: 分类清晰，便于快速查找

## 快速开始

### 创建新测试用例

```bash
# 使用脚本创建 (保存到项目 tests/test-cases/)
bash scripts/new-test.sh api login
bash scripts/new-test.sh ui dashboard
bash scripts/new-test.sh e2e user-flow
```

### 搜索测试用例

```bash
# 按关键字搜索
bash scripts/search.sh login

# 按类型搜索
bash scripts/search.sh --type api

# 按优先级搜索
bash scripts/search.sh --priority P0
```

### 运行测试用例

```bash
# 使用运行脚本执行测试
bash scripts/run-test.sh test-cases/api/login.md --ui
bash scripts/run-test.sh test-cases/api/login.md --api
```

### 生成索引

```bash
bash scripts/index.sh
```

## 目录结构

测试用例和报告保存在被测项目的 `tests/` 目录下：

```
{项目根目录}/tests/
├── test-cases/              # 测试用例目录
│   ├── api/                # API测试用例
│   │   ├── authentication/ # 认证类
│   │   └── data-query/    # 数据查询类
│   └── ui/                 # UI测试用例
├── reports/                  # 测试报告目录
│   ├── api/               # API测试报告
│   ├── ui/                # UI测试报告
│   └── performance/       # 性能测试报告
├── scripts/                # 辅助脚本
│   ├── new-test.sh       # 创建新用例
│   ├── search.sh         # 搜索用例
│   ├── index.sh          # 生成索引
│   └── run-test.sh       # 运行测试
└── INDEX.md               # 测试用例索引
```
{项目根目录}/tests/
├── test-cases/              # 测试用例目录
│   ├── api/                # API测试用例
│   │   ├── authentication/ # 认证类
│   │   └── data-query/    # 数据查询类
│   └── ui/                 # UI测试用例
├── scripts/                # 辅助脚本
│   ├── new-test.sh       # 创建新用例
│   ├── search.sh         # 搜索用例
│   ├── index.sh          # 生成索引
│   └── run-test.sh       # 运行测试
└── INDEX.md               # 测试用例索引
```

## 认证方案

支持多种认证方式：

| 认证类型 | 使用方式 |
|----------|----------|
| Bearer Token | `-H "Authorization: Bearer $TOKEN"` |
| Basic Auth | `-H "Authorization: Basic $(echo -n 'user:pass' | base64)"` |
| API Key | `-H "X-API-Key: $API_KEY"` |
| Cookie | `-H "Cookie: session_id=$SESSION_ID"` |

## 通用变量

| 变量 | 说明 |
|------|------|
| $BASE_URL | 应用基础URL |
| $TOKEN | 认证Token |
| $API_BASE | API基础路径 |
| $TEST_USER | 测试用户 |
| $WAIT_TIME | 等待时间 |

## 模板类型

| 模板 | 说明 |
|------|------|
| api.sh | API接口测试脚本 |
| ui.sh | UI测试脚本 |
| stress.sh | 压力测试脚本 |

**注意**: 测试脚本头部包含知识库注释，使用Markdown格式，方便阅读和检索。

## 与agent-browser联动

测试用例通过 [agent-browser](../agent-browser/SKILL.md) 执行。

### 常用命令

```bash
agent-browser open "$BASE_URL"     # 打开页面
agent-browser snapshot -i           # 获取元素
agent-browser click @e1            # 点击
agent-browser fill @e1 "value"     # 填写
agent-browser eval "..."           # 验证
agent-browser errors               # 检查错误
agent-browser screenshot           # 截图
agent-browser close                # 关闭
```

## 分类标准

- **按类型**: API / UI / E2E / Stress / WebSocket / GraphQL
- **按优先级**: P0(核心) / P1(重要) / P2(边缘)
- **按模块**: authentication / data-query / data-modification

## 最佳实践

1. **测试前先搜索** - 避免重复创建
2. **使用通用变量** - 便于复用
3. **记录边界条件** - 便于回归
4. **及时更新** - 保持准确
5. **保持简洁** - 步骤清晰
