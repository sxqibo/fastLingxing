# 环境配置说明

## 概述

项目现在支持使用 `.env` 文件来管理配置，这样可以更好地管理不同环境的配置信息。

## 文件结构

```
fastLingxin/
├── .env                    # 环境配置文件（不提交到版本控制）
├── .env.example           # 环境配置模板文件
├── sample/
│   ├── env.php            # 环境配置加载器
│   ├── config.php         # 主配置文件
│   └── ...
```

## 配置说明

### 1. 复制环境配置文件

```bash
cp .env.example .env
```

### 2. 编辑 .env 文件

```bash
# OpenAPI 配置
OPENAPI_HOST=https://openapi.lingxing.com
OPENAPI_APP_ID=your_app_id
OPENAPI_APP_SECRET=your_app_secret

# 示例配置
SAMPLE_SID=10743                    # 店铺ID
SAMPLE_WID=1,578,765               # 仓库ID
DEFAULT_OFFSET=0                    # 默认分页偏移量
DEFAULT_LENGTH=20                   # 默认分页长度
MAX_LENGTH=800                      # 最大分页长度
```

### 3. 配置常量

在 `config.php` 中定义了以下常量：

- `OPENAPI_HOST`: OpenAPI 服务地址
- `OPENAPI_APP_ID`: 应用ID
- `OPENAPI_APP_SECRET`: 应用密钥
- `SAMPLE_SID`: 示例店铺ID
- `SAMPLE_WID`: 示例仓库ID
- `DEFAULT_OFFSET`: 默认分页偏移量
- `DEFAULT_LENGTH`: 默认分页长度
- `MAX_LENGTH`: 最大分页长度

## 使用方法

### 在示例文件中使用配置

```php
<?php
require_once __DIR__ . '/../config.php';

// 使用配置常量
$params = [
    'sid' => SAMPLE_SID,           // 使用配置的店铺ID
    'offset' => DEFAULT_OFFSET,    // 使用配置的偏移量
    'length' => MAX_LENGTH,        // 使用配置的长度
];
```

### 直接获取环境变量

```php
<?php
require_once __DIR__ . '/env.php';

// 获取单个配置
$appId = EnvLoader::get('OPENAPI_APP_ID');

// 获取所有配置
$allConfig = EnvLoader::all();
```

## 安全注意事项

1. **不要提交 .env 文件**：`.env` 文件已添加到 `.gitignore` 中
2. **使用 .env.example 作为模板**：团队成员可以复制此文件来创建自己的配置
3. **敏感信息保护**：确保 `.env` 文件包含的敏感信息不会被意外提交

## 环境配置的优势

1. **环境隔离**：不同环境可以使用不同的配置文件
2. **安全性**：敏感信息不会提交到版本控制系统
3. **灵活性**：可以轻松修改配置而不需要修改代码
4. **团队协作**：团队成员可以有自己的本地配置

## 示例

### 开发环境配置

```bash
# .env
OPENAPI_HOST=https://openapi-dev.lingxing.com
OPENAPI_APP_ID=dev_app_id
OPENAPI_APP_SECRET=dev_app_secret
SAMPLE_SID=12345
```

### 生产环境配置

```bash
# .env
OPENAPI_HOST=https://openapi.lingxing.com
OPENAPI_APP_ID=prod_app_id
OPENAPI_APP_SECRET=prod_app_secret
SAMPLE_SID=67890
```
