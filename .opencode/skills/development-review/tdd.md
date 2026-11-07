# TDD 整合

## 铁律

```
NO PRODUCTION CODE WITHOUT A FAILING TEST FIRST
```

测试前写代码？删除它。重新开始。

## 概述

先写测试。看它失败。写最小代码通过。

**核心原则**：如果不看测试失败，就不知道是否测试了正确的东西。

违反规则 = 违反 TDD 精神。

## 红绿重构循环

```
RED → GREEN → REFACTOR → RED → ...
```

### RED - 写失败的测试

写一个最小测试，展示应该发生什么。

```
test('retries failed operations 3 times', async () => {
  let attempts = 0;
  const operation = () => {
    attempts++;
    if (attempts < 3) throw new Error('fail');
    return 'success';
  };

  const result = await retryOperation(operation);

  expect(result).toBe('success');
  expect(attempts).toBe(3);
});
```

**要求：**
- 一个行为
- 清晰的名字
- 真实代码（除非不可避免）

### GREEN - 最小代码

写最简单的代码来通过测试。

```
async function retryOperation<T>(fn: () => Promise<T>): Promise<T> {
  for (let i = 0; i < 3; i++) {
    try {
      return await fn();
    } catch (e) {
      if (i === 2) throw e;
    }
  }
  throw new Error('unreachable');
}
```

不要添加功能、重构其他代码、或超出测试改进。

### REFACTOR - 重构

只有在绿色之后：
- 消除重复
- 改进名字
- 提取辅助函数

保持测试绿色。不要添加行为。

### 重复

下一个失败测试为下一个功能。

## 何时使用

- 新功能
- Bug修复
- 重构
- 行为变更

**例外**（需要人类伙伴许可）：
- 临时原型
- 生成代码
- 配置文件

## 禁止行为

- 代码在测试之前
- 测试在实现之后
- 测试立即通过
- 不能解释为什么测试失败
- "之后"添加的测试
- 合理化"就这一次"
- "我已经手动测试过了"

## 为什么顺序重要

"我之后写测试来验证它工作"

代码后写的测试立即通过。立即通过证明不了什么：
- 可能测试了错误的东西
- 可能测试了实现，而非行为
- 可能错过了你忘记的边缘情况
- 你从未见过它捕获Bug

测试优先强制你看到测试失败，证明它真的测试了什么东西。

## 常见合理化

| 借口 | 现实 |
|------|------|
| "太简单不需要测试" | 简单代码也会坏。测试只需30秒。 |
| "我之后测试" | 测试立即通过证明不了什么。 |
| "之后测试达到相同目标" | 之后测试 = "这做什么？" 测试优先 = "这应该做什么？" |
| "已经手动测试过了" | 临时 ≠ 系统。没有记录，不能重跑。 |
| "删除X小时工作太浪费" | 沉没成本谬误。保留无法信任的代码是技术债务。 |
| "TDD是教条的，我更务实" | TDD更务实：提交前发现Bug（比之后调试更快）。 |

## Bug修复示例

**Bug：** 接受空邮箱

**RED**
```typescript
test('rejects empty email', async () => {
  const result = await submitForm({ email: '' });
  expect(result.error).toBe('Email required');
});
```

**Verify RED**
```bash
$ npm test
FAIL: expected 'Email required', got undefined
```

**GREEN**
```typescript
function submitForm(data: FormData) {
  if (!data.email?.trim()) {
    return { error: 'Email required' };
  }
  // ...
}
```

**Verify GREEN**
```bash
$ npm test
PASS
```

**REFACTOR**
如果需要，为多个字段提取验证。
