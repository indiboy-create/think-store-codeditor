## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
- 仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
- 在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# youvision/think-store-codeditor

ThinkPHP 8 模板代码编辑器扩展：通过包内 `View` 渲染 layui 管理页面，在 [Ace Editor](https://github.com/ajaxorg/ace) 中编辑 HTML + PHP 混合模板；提供页面、读取、更新、新增与静态资源路由，静态资源无需发布到宿主 `public`。

编辑器默认读取 `template` 数据表的 `id` 与 `newstext` 字段，数据表、字段、路由前缀与可编辑目录均可写入 `config/store_codeditor.php` 自定义。

## 环境要求

- PHP >= 8.0
- ThinkPHP 8 主项目
- `topthink/think-view`（随本包自动安装）

## 安装

```bash
composer require youvision/think-store-codeditor
```

ThinkPHP 8 项目通常在 `composer.json` 的 `post-autoload-dump` 中执行 `service:discover` 与 `vendor:publish`，安装后会自动完成服务注册与配置发布。若主项目未配置该脚本，请手动执行：

```bash
php think service:discover
php think vendor:publish
```

发布后主项目会得到 `config/store_codeditor.php`，可直接修改；未发布的配置项使用包内默认值。路由由 `Youvision\ThinkStoreCodeditor\Service` 在 `boot()` 阶段加载，宿主项目不需要复制路由文件。

## 路由

| 方法 | 地址 | 说明 |
| --- | --- | --- |
| GET | `/store/think-store-codeditor/index` | 渲染模板代码编辑器页面 |
| GET | `/store/think-store-codeditor/read` | 读取模板列表，或按 `id` 读取单条模板 |
| POST | `/store/think-store-codeditor/update` | 按 `id` 更新模板内容 |
| POST | `/store/think-store-codeditor/create` | 新增模板记录，可选创建模板文件 |
| GET | `/store/think-store-codeditor/category` | 渲染模板分类管理页面 |
| GET | `/store/think-store-codeditor/category/read` | 读取分类列表，或按 `id` 读取单条分类 |
| POST | `/store/think-store-codeditor/category/create` | 新增分类 |
| POST | `/store/think-store-codeditor/category/update` | 按 `id` 更新分类 |
| POST | `/store/think-store-codeditor/category/delete` | 按 `id` 删除分类 |
| GET | `/store/assets/<path>` | 直接输出包内 `theme/assets`，含 layui、Ace 与页面资源 |

路由前缀由 `config/store_codeditor.php` 的 `route.prefix` 控制，默认 `store`；设为空字符串时地址变为 `/think-store-codeditor/index`、`/think-store-codeditor/read` 等。页面、接口与资源规则分别由 `route.rules` 与 `route.assets` 配置，修改后模板内的地址会自动跟随，不需要手工同步页面文件。

## 页面与静态资源

- 模板位于包内 `theme/view`，由包内 `View` 渲染器通过 think-view 的 `Think` 驱动渲染，不依赖主项目的 `view_path`，也不需要复制到主项目。
- 静态资源由 `AssetController` 读取 `theme/assets` 后输出，路径经过 `realpath` 校验并拒绝 `..`，避免目录穿越。
- 资源响应带 `Cache-Control: public, max-age=86400`，字体、CSS、JS、图片等类型会返回对应的 `Content-Type`。

```text
/store/think-store-codeditor/index
/store/assets/layui/css/layui.css
/store/assets/ace/ace.js
```

## 页面功能

- 左侧模板列表：layui 数据表格，显示 ID、内容摘要与字符数，支持排序与按 ID 或摘要筛选
- 主编辑区：Ace Editor 的 `ace/mode/php` 模式，支持 HTML + PHP 混合高亮、自动换行、行号与括号匹配
- 保存模板：按 ID 更新 `newstext`，支持 `Ctrl + S` 快捷键
- 新增模板：新增一条模板记录，可选填写受限制目录内的文件路径，同时生成模板文件
- 分类管理：编辑器页右上角进入分类管理，layui 数据表格展示分类，支持新增、编辑、删除
- 分类字段：分类标题、分类名称、分类图片、分类介绍、排序、状态、最后一级、管理员 ID、站点 ID
- 未保存状态提示与切换确认

## 接口

页面与接口共用统一 JSON 结构：

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
GET /store/think-store-codeditor/read
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
GET /store/think-store-codeditor/read?id=1
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
POST /store/think-store-codeditor/update
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
POST /store/think-store-codeditor/create
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

### 分类管理

分类接口与模板接口共用统一的 `{code, msg, data}` 结构，页面入口为 `/store/think-store-codeditor/category`。

读取分类列表：

```http
GET /store/think-store-codeditor/category/read
```

响应 `data.items` 为分类数组，字段包含 `id`、`title`、`titlepic`、`name`、`sort`、`status`、`intro`、`is_last`、`admin_id`、`site_id`、`create_time`、`update_time`。

新增分类：

```http
POST /store/think-store-codeditor/category/create
Content-Type: application/json
```

```json
{
  "title": "企业模板",
  "name": "enterprise",
  "titlepic": "/static/category/enterprise.png",
  "intro": "企业官网模板分类",
  "sort": 0,
  "status": 1,
  "is_last": 0,
  "admin_id": 1,
  "site_id": 1
}
```

`title` 与 `name` 至少填写一个，其余字段可选。创建时间与更新时间由服务自动写入。

更新分类：

```http
POST /store/think-store-codeditor/category/update
Content-Type: application/json
```

请求体在新增字段基础上增加 `id`，例如 `{"id": 1, "title": "企业模板"}`。

删除分类：

```http
POST /store/think-store-codeditor/category/delete
Content-Type: application/json
```

```json
{ "id": 1 }
```

`id` 不存在时返回 HTTP 404 与 `code: 1`。

## 数据表

默认读取 `template` 表，最少需要 `id` 与 `newstext` 两个字段：

```sql
CREATE TABLE `template` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '模板id',
  `newstext` longtext NULL COMMENT '模板内容',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模板数据表';
```

模板分类表 `template_category` 用于维护模板分类，模板表的 `cid` 字段对应分类表主键：

```sql
CREATE TABLE `template_category` (
  `id` int unsigned NOT NULL AUTO_INCREMENT COMMENT '分类id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '分类标题',
  `titlepic` varchar(255) NOT NULL DEFAULT '' COMMENT '分类图片',
  `name` varchar(100) NOT NULL DEFAULT '' COMMENT '分类名称',
  `sort` int unsigned NOT NULL DEFAULT 0 COMMENT '排序',
  `status` tinyint unsigned NOT NULL DEFAULT 1 COMMENT '状态：1启用，0禁用',
  `intro` varchar(500) NOT NULL DEFAULT '' COMMENT '分类介绍',
  `is_last` tinyint unsigned NOT NULL DEFAULT 0 COMMENT '是否最后一级：1是，0否',
  `admin_id` int unsigned NOT NULL DEFAULT 0 COMMENT '管理员id',
  `site_id` int unsigned NOT NULL DEFAULT 0 COMMENT '站点id',
  `create_time` int unsigned NOT NULL DEFAULT 0 COMMENT '创建时间',
  `update_time` int unsigned NOT NULL DEFAULT 0 COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `sort` (`sort`),
  KEY `site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模板分类表';
```

分类表名由 `database.category_table` 配置，可通过 `$templates->categories()` 读取启用分类。

表名、字段名全部可配置，见下文 `database` 配置。

## 配置

### route 路由

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `route.enabled` | `true` | 是否随服务自动注册页面、接口与资源路由；关闭后可在主项目 `route` 目录自行注册 |
| `route.prefix` | `store` | 路由前缀，留空表示不带前缀 |
| `route.middleware` | `[]` | 应用到整组路由上的中间件，可填写主项目鉴权中间件类名或别名 |
| `route.rules.index` | `think-store-codeditor/index` | 编辑器页面规则，注册为 GET |
| `route.rules.read` | `think-store-codeditor/read` | 读取接口规则，注册为 GET |
| `route.rules.update` | `think-store-codeditor/update` | 更新接口规则，注册为 POST |
| `route.rules.create` | `think-store-codeditor/create` | 新增接口规则，注册为 POST |
| `route.rules.category` | `think-store-codeditor/category` | 分类管理页面规则，注册为 GET |
| `route.rules.category_read` | `think-store-codeditor/category/read` | 分类读取接口规则，注册为 GET |
| `route.rules.category_create` | `think-store-codeditor/category/create` | 分类新增接口规则，注册为 POST |
| `route.rules.category_update` | `think-store-codeditor/category/update` | 分类更新接口规则，注册为 POST |
| `route.rules.category_delete` | `think-store-codeditor/category/delete` | 分类删除接口规则，注册为 POST |
| `route.assets` | `assets` | 静态资源规则前缀，最终地址为 `/{prefix}/{assets}/<path>` |

`route.rules` 与 `route.assets` 不能为空且不能重复，否则路由文件不会注册。


### database 数据库

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `database.table` | `template` | 模板数据表名，不含表前缀，框架自动拼接 |
| `database.category_table` | `template_category` | 分类数据表名，不含表前缀，框架自动拼接 |
| `database.id_field` | `id` | 模板主键字段名 |
| `database.cid_field` | `cid` | 分类 id 字段名 |
| `database.title_field` | `title` | 模板标题字段名 |
| `database.intro_field` | `intro` | 模板介绍字段名 |
| `database.path_field` | `path` | 模板主输出路径字段名 |
| `database.content_field` | `newstext` | 模板内容字段名 |
| `database.list_limit` | `200` | 模板列表接口最多返回的记录数，避免大表拖慢页面 |
| `database.excerpt_length` | `60` | 列表摘要最大字符数，`0` 表示不截断 |

`table`、`id_field`、`cid_field`、`title_field`、`intro_field`、`path_field`、`content_field` 只允许英文、数字与下划线，避免配置拼接注入。

### file 可编辑目录

读取与更新接口只操作数据库，不读写磁盘。仅在新增模板传入 `file` 时才会创建模板文件。

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `file.enabled` | `true` | 是否允许新增模板时创建文件；关闭后新增接口只写入数据库 |
| `file.root` | `template` | 模板根目录，相对主项目根目录或绝对路径；主项目使用 `view` 目录时可改为 `view` |
| `file.allow_dirs` | `["default"]` | 可编辑目录白名单，相对 `file.root`；`file` 必须落在其中之一；空数组表示不允许创建任何文件 |
| `file.ext` | `["html"]` | 允许创建的文件扩展名，统一转小写比较 |
| `file.overwrite` | `false` | 目标文件已存在时是否覆盖；`false` 时新增接口直接报错 |
| `file.create_dir` | `true` | 目标文件的父目录不存在时是否自动创建 |

目录限制规则：

- `file` 必须是相对路径，禁止绝对路径、Windows 盘符、`.`、`..` 与空路径片段
- 最终路径必须位于 `file.root/{allow_dirs}` 内，并使用 `realpath` 校验，防止符号链接逃逸
- 扩展名必须在 `file.ext` 白名单内，无扩展名文件一律拒绝
- `file.overwrite=false` 时不会覆盖已有模板文件

示例：`file.root` 为 `template`、`file.allow_dirs` 为 `["default"]`，请求 `file` 为 `default/about.html` 时最终写入 `template/default/about.html`；请求 `file` 为 `other/about.html` 或 `../about.html` 都会被拒绝。

### Service 配置合并

包启动时会自动合并包内默认配置与主项目 `config/store_codeditor.php`，主项目配置优先。`file.allow_dirs` 与 `file.ext` 为列表型配置，主项目配置会整体覆盖默认值，不会与默认值混合。修改配置后清除 `runtime` 缓存即可生效。

## PHP 调用

服务注册后，可通过容器获取 `Templates` 与 `PathGuard`：

```php
use Youvision\ThinkStoreCodeditor\Categories;
use Youvision\ThinkStoreCodeditor\PathGuard;
use Youvision\ThinkStoreCodeditor\Templates;

/** @var Templates $templates */
$templates = app(Templates::class);
// 也可使用别名：app('codeditor.templates')

/** @var PathGuard $guard */
$guard = app(PathGuard::class);
// 也可使用别名：app('codeditor.path_guard')

/** @var Categories $categories */
$categories = app(Categories::class);
// 也可使用别名：app('codeditor.categories')

$list = $templates->lists();
$categories->lists();
```

这些类都支持脱离容器使用：

```php
$templates  = new Templates((array) config('store_codeditor'));
$categories = new Categories((array) config('store_codeditor'));
$guard      = new PathGuard((array) config('store_codeditor.file'), app()->getRootPath());
```

## 目录结构

```text
think-store-codeditor/
├─ composer.json
├─ config/
│  └─ store_codeditor.php        # 编辑器配置
├─ route/
│  └─ store_codeditor.php        # 页面、接口与资源路由
├─ src/
│  ├─ Service.php                # ThinkPHP 服务注册
│  ├─ CodeditorController.php     # 模板页面与读取、更新、新增接口
│  ├─ CategoryController.php      # 分类页面与增改删接口
│  ├─ AssetController.php         # 包内静态资源输出
│  ├─ View.php                   # 包内模板渲染器
│  ├─ Templates.php              # 模板数据仓储
│  ├─ Categories.php             # 分类数据仓储
│  ├─ Template.php               # 模板数据模型
│  ├─ TemplateCategory.php       # 模板分类模型
│  └─ PathGuard.php              # 模板文件路径守卫
├─ theme/
│  ├─ view/
│  │  ├─ index.html              # 编辑器页面模板
│  │  └─ category.html           # 分类管理页面模板
│  └─ assets/
│     ├─ css/index.css           # 页面自定义样式
│     ├─ js/codeditor.js         # 编辑器页面脚本
│     ├─ js/category.js          # 分类管理页面脚本
│     ├─ ace/                    # Ace Editor 本地资源
│     └─ layui/                  # layui 本地资源
├─ tests/
│  └─ verify.php                 # 独立验证脚本
├─ LICENSE
└─ README.md
```

- `theme/view` 与 `theme/assets` 均由包内路由读取，不发布到宿主 `public`
- `theme/assets/ace`、`theme/assets/layui` 为完整本地第三方库，保持原样
- `src` 为接口与渲染实现，`route/store_codeditor.php` 为路由定义
- `config/store_codeditor.php` 为默认配置

## 安全建议

- 为 `route.middleware` 配置主项目鉴权中间件，避免模板被匿名读取或修改
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
- `config/store_codeditor.php` 的默认配置项
- 路由文件、服务文件、控制器、渲染器与前端资源的关键实现
- `theme/view` 模板变量、layui 数据表格与 assets 路由引用
- `AssetController` 的资源解析、MIME 类型与目录穿越防护
- `PathGuard` 的目录白名单、目录穿越拦截、扩展名限制、覆盖开关与文件写入
- 全部 PHP 文件的语法

## 许可证

MIT，详见 `LICENSE`。