## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# youvision/think-store-codeditor

ThinkPHP 8 模板代码编辑器实例包：内置基于 [Ace Editor](https://github.com/ajaxorg/ace) 与 layui 的 HTML + PHP 混合代码编辑页面，通过 [`youvision/think-static`](https://github.com/indiboy-create/think-static) 部署到主项目的 `public/store/store-codeditor`，并提供模板读取、更新、新增三个路由接口。

编辑器默认读取 `template` 数据表的 `id` 与 `newstext` 字段，数据表、字段、路由前缀与可编辑目录均可写入 `config/codeditor.php` 自定义。

## 环境要求

- PHP >= 8.0
- Composer >= 2.2
- ThinkPHP 8 主项目

`youvision/think-static` 是 Composer 插件。Composer 2.2 及以上版本默认拦截插件安装，主项目需要允许该插件：

```json
{
    "config": {
        "allow-plugins": {
            "youvision/think-static": true
        }
    }
}
```

## 安装

```bash
composer require youvision/think-store-codeditor
```

在主项目 `composer.json` 中允许插件，并把静态资源目录指向本包：

```json
{
    "config": {
        "allow-plugins": {
            "youvision/think-static": true
        }
    },
    "extra": {
        "think-static": {
            "project_dir": "vendor/youvision/think-store-codeditor/project"
        }
    }
}
```

配置完成后执行：

```bash
composer dump-autoload
```

`project_dir` 会替换主项目默认的 `project` 目录。ThinkPHP 8 项目通常在 `composer.json` 的 `post-autoload-dump` 中执行 `service:discover` 与 `vendor:publish`，安装后会自动完成服务注册与配置发布；若主项目未配置该脚本，请手动执行：

```bash
php think service:discover
php think vendor:publish
```

发布后主项目会得到 `config/codeditor.php`，可直接修改；未发布的配置项使用包内默认值。

## 数据表

默认读取 `template` 表，最少需要 `id` 与 `newstext` 两个字段：

```sql
CREATE TABLE `template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '模板id',
  `newstext` longtext NULL COMMENT '模板内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模板数据表';
```

表名、字段名全部可配置，见下文 `database` 配置。

## 页面

部署完成后访问：

```text
/store/store-codeditor/index.html
```

页面功能：

- 左侧模板列表：读取 `template` 表记录，显示 id、内容摘要与字符数，支持按 id 或摘要筛选
- 主编辑区：Ace Editor 的 `ace/mode/php` 模式，支持 HTML + PHP 混合高亮、自动换行、行号、括号匹配
- 保存模板：按 id 更新 `newstext`，支持 `Ctrl + S` 快捷键
- 新增模板：新增一条模板记录，可选填写受限制目录内的文件路径，同时生成模板文件
- 未保存状态提示与切换确认

页面通过 `<div class="codeditor-shell" data-api="/codeditor">` 指定接口前缀。修改 `route.prefix` 后请同步调整 `project/index.html` 的 `data-api` 属性。

## 接口

三个接口由 `route/codeditor.php` 注册，默认前缀为 `codeditor`。所有接口返回统一 JSON 结构：

```json
{
  "code": 0,
  "msg": "ok",
  "data": {}
}
```

`code` 为 `0` 表示成功，非 `0` 表示失败；失败时 `msg` 为错误原因，`data` 为 `null`。

### 读取模板

```http
GET /codeditor/read
```

不带参数时返回模板列表：

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "total": 2,
    "items": [
      { "id": 2, "excerpt": "首页模板 …", "length": 128 },
      { "id": 1, "excerpt": "关于我们 …", "length": 96 }
    ]
  }
}
```

带 `id` 参数时返回单条模板：

```http
GET /codeditor/read?id=1
```

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "id": 1,
    "content": "<html>…</html>"
  }
}
```

`id` 不存在时返回 HTTP 404 与 `code: 1`。

### 更新模板

```http
POST /codeditor/update
Content-Type: application/json
```

请求体：

```json
{
  "id": 1,
  "content": "<html>…</html>"
}
```

成功响应：

```json
{
  "code": 0,
  "msg": "ok",
  "data": { "id": 1 }
}
```

`content` 允许为空字符串（清空模板），但必须显式传入。`id` 不存在时返回 HTTP 404 与 `code: 1`。

### 新增模板

```http
POST /codeditor/create
Content-Type: application/json
```

请求体：

```json
{
  "content": "<html>…</html>",
  "file": "default/about.html"
}
```

- `content` 必填，写入模板内容字段
- `file` 可选，相对模板根目录的路径；必须位于 `file.allow_dirs` 白名单内，扩展名必须在 `file.ext` 白名单内

成功响应：

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "id": 3,
    "file": "template/default/about.html"
  }
}
```

`file` 为空时只新增数据库记录，不读写磁盘。文件写入失败时会自动回滚刚插入的数据库记录。

## 配置 config/codeditor.php

### route 路由

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `route.enabled` | `true` | 是否随服务自动注册内置路由；关闭后可在主项目 `route` 目录自行注册 |
| `route.prefix` | `codeditor` | 路由前缀，留空表示不带前缀；最终地址为 `/{prefix}/{rule}` |
| `route.middleware` | `[]` | 应用到全部路由上的中间件，可填写类名或别名；生产环境应配置登录鉴权中间件 |
| `route.rules.read` | `read` | 读取接口规则，注册为 GET |
| `route.rules.update` | `update` | 更新接口规则，注册为 POST |
| `route.rules.create` | `create` | 新增接口规则，注册为 POST |

三个路由规则不能为空且不能重复，否则路由文件不会注册。

### database 数据库

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `database.connection` | 空字符串 | 数据库连接名，留空使用主项目默认连接 |
| `database.table` | `template` | 模板数据表名，不含表前缀，框架自动拼接 |
| `database.id_field` | `id` | 模板主键字段名 |
| `database.content_field` | `newstext` | 模板内容字段名 |
| `database.list_limit` | `200` | 模板列表接口最多返回的记录数，避免大表拖慢页面 |
| `database.excerpt_length` | `60` | 列表摘要最大字符数，`0` 表示不截断 |

`table`、`id_field`、`content_field` 只允许英文、数字与下划线，避免配置拼接注入。

### file 可编辑目录

读取与更新接口只操作数据库，不读写磁盘。仅在新增模板传入 `file` 时才会创建模板文件。

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `file.enabled` | `true` | 是否允许新增模板时创建文件；关闭后新增接口只写入数据库 |
| `file.root` | `template` | 模板根目录，相对主项目根目录或绝对路径；主项目使用 `view` 目录时可改为 `view` |
| `file.allow_dirs` | `["default"]` | 可编辑目录白名单，相对 `file.root`；`file` 必须落在其中之一；空数组表示不允许创建任何文件 |
| `file.ext` | `["html","htm","php","tpl"]` | 允许创建的文件扩展名，统一转小写比较 |
| `file.overwrite` | `false` | 目标文件已存在时是否覆盖；`false` 时新增接口直接报错 |
| `file.create_dir` | `true` | 目标文件的父目录不存在时是否自动创建 |

目录限制规则：

- `file` 必须是相对路径，禁止绝对路径、Windows 盘符、`.`、`..` 与空路径片段
- 最终路径必须位于 `file.root/{allow_dirs}` 内，并使用 `realpath` 校验，防止符号链接逃逸
- 扩展名必须在 `file.ext` 白名单内，无扩展名文件一律拒绝
- `file.overwrite=false` 时不会覆盖已有模板文件

示例：`file.root` 为 `template`、`file.allow_dirs` 为 `["default"]`，请求 `file` 为 `default/about.html` 时最终写入 `template/default/about.html`；请求 `file` 为 `other/about.html` 或 `../about.html` 都会被拒绝。

### Service 配置合并

包启动时会自动合并包内默认配置与主项目 `config/codeditor.php`，主项目配置优先。`file.allow_dirs` 与 `file.ext` 为列表型配置，主项目配置会整体覆盖默认值，不会与默认值混合。修改配置后清除 `runtime` 缓存即可生效。

## 目录结构

```text
think-store-codeditor/
├─ composer.json
├─ config/
│  └─ codeditor.php
├─ route/
│  └─ codeditor.php
├─ src/
│  ├─ PathGuard.php
│  ├─ Service.php
│  ├─ Template.php
│  ├─ TemplateController.php
│  └─ Templates.php
├─ project/
│  ├─ project.json
│  ├─ index.html
│  ├─ readme.md
│  ├─ theme/
│  │  ├─ ace/
│  │  ├─ css/codeditor.css
│  │  └─ layui/
│  └─ view/
│     └─ index.html
├─ tests/
│  └─ verify.php
├─ LICENSE
└─ README.md
```

- `project` 是静态资源部署源目录，`project.json` 自身不会被部署
- `theme/ace`、`theme/layui` 为完整本地第三方库，保持原样
- `src` 为接口实现，`route/codeditor.php` 为路由定义
- `config/codeditor.php` 为默认配置

## 部署配置 project/project.json

```json
{
  "path": "store-codeditor",
  "map": {
    "theme": "theme",
    "view": "view",
    "index.html": "index.html",
    "readme.md": "readme.md"
  },
  "exclude": ["**/*.map"]
}
```

| 字段 | 必填 | 类型 | 说明 |
| --- | --- | --- | --- |
| `path` | 是 | string | 整个 `project` 部署到 `public/store` 下的目录名称 |
| `map` | 否 | object | 部署映射，键为 `project` 下的相对路径，值为目标相对路径 |
| `exclude` | 否 | array | 排除规则，相对 `project` 根目录，支持通配符 |

部署结果：

```text
project/theme       → public/store/store-codeditor/theme
project/view        → public/store/store-codeditor/view
project/index.html  → public/store/store-codeditor/index.html
project/readme.md   → public/store/store-codeditor/readme.md
```

部署后访问 `/store/store-codeditor/index.html`。使用 PHP 内置服务器预览：

```bash
php -S 127.0.0.1:8003 -t public
```

修改 `project` 下静态资源后执行 `composer dump-autoload` 重新发布。

## 安全建议

- 为 `route.middleware` 配置登录鉴权中间件，避免模板被匿名读取或修改
- 生产环境保持 `file.overwrite=false`，并只开放必要的可编辑目录
- 明确 `file.ext` 白名单，不要加入 `js`、`exe` 等可执行或脚本类型
- `file.root` 与 `file.allow_dirs` 建议指向模板目录，不要指向项目根目录或 `public`
- 接口涉及模板内容修改，建议同时限制请求方法、来源与频率

## 验证

```bash
composer test
```

或直接运行：

```bash
php tests/verify.php
```

验证脚本会检查：

- `composer.json` 的包名、依赖、PSR-4 自动加载、服务注册与配置声明
- `config/codeditor.php` 的默认配置项
- `project/project.json` 的部署路径、映射与排除规则
- 路由文件、服务文件与前端页面的关键实现
- `PathGuard` 的目录白名单、目录穿越拦截、扩展名限制、覆盖开关与文件写入
- 全部 PHP 文件的语法

## 许可证

MIT，详见 `LICENSE`。