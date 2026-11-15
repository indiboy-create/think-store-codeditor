## ThinkPHP-PanGu - 盘古ThinkPHP开源索引!
面向 ThinkPHP 的开源工程索引：组件、框架、服务与发布工具，按能力组织，持续整理。
- 仓库地址：**<https://github.com/indiboy-create/ThinkPHP-PanGu/>**
- 在线站点：**<https://indiboy-create.github.io/ThinkPHP-PanGu/>**

# youvision/think-store-codeditor

ThinkPHP 8 模板代码编辑器扩展：通过包内 `View` 渲染 layui 管理页面，在 [Ace Editor](https://github.com/ajaxorg/ace) 中编辑 HTML + PHP 混合模板；页面、JSON 接口与静态资源均由包内路由输出，静态资源无需发布到宿主 `public`。

编辑器默认读取 `template` 数据表的 `id` 与 `newstext` 字段，数据表名、路由前缀、包内命名空间与可编辑目录均可写入 `config/store_codeditor.php` 自定义；模板字段固定为 `id`、`title`、`cid`、`intro`、`path`、`newstext`。包在 `composer.json` 的 `extra.store.home` 中声明首页 `/store/think-store-codeditor/index`，可被 `youvision/think-store` 识别。

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
| GET | `/store/think-store-codeditor/index` | 渲染项目介绍页面 |
| GET | `/store/think-store-codeditor/template` | 渲染模板代码编辑器页面 |
| GET | `/store/think-store-codeditor/category` | 渲染模板分类管理页面 |
| GET | `/store/think-store-codeditor/iframe` | 渲染内嵌页面示例，列出可内嵌页面并提供 iframe 预览 |
| GET | `/store/think-store-codeditor/iframe/template` | 渲染 iframe 版模板列表页，支持查看、新增与内容编辑 |
| GET | `/store/think-store-codeditor/iframe/category` | 渲染 iframe 版分类管理页，树形表格与增改删 |

| GET | `/store/think-store-codeditor/api/template/read` | 读取模板列表，或按 `id` 读取单条模板 |
| POST | `/store/think-store-codeditor/api/template/update` | 按 `id` 更新模板内容 |
| POST | `/store/think-store-codeditor/api/template/create` | 新增模板记录，可选创建模板文件 |
| GET | `/store/think-store-codeditor/api/template/history` | 读取模板编辑历史列表 |
| GET | `/store/think-store-codeditor/api/template/history/detail` | 读取历史快照详情 |
| POST | `/store/think-store-codeditor/api/template/history/restore` | 用历史快照覆盖模板内容 |
| GET | `/store/think-store-codeditor/api/category/read` | 读取分类列表，或按 `id` 读取单条分类 |
| POST | `/store/think-store-codeditor/api/category/create` | 新增分类 |
| POST | `/store/think-store-codeditor/api/category/update` | 按 `id` 更新分类 |
| POST | `/store/think-store-codeditor/api/category/delete` | 按 `id` 删除分类 |
| GET | `/store/think-store-codeditor/assets/<path>` | 直接输出包内 `theme/assets`，含 layui、Ace 与页面资源 |

路由前缀由 `config/store_codeditor.php` 的 `route.prefix` 控制，默认 `store`；包内命名空间由 `route.segment` 控制，默认 `think-store-codeditor`；静态资源路径由顶层 `assets` 控制，默认 `assets`。`route.prefix` 设为空字符串时介绍页变为 `/think-store-codeditor/index`；`route.segment` 设为空字符串时页面直接挂在路由前缀下，例如 `/store/index` 与 `/store/template`；`assets` 以 `/` 开头时视为外部托管的绝对地址。页面、接口与资源地址由 `StoreCodeditor` 统一推导，修改配置后模板与脚本会自动跟随。

## 页面与静态资源

- 模板默认位于包内 `theme/view`，由包内 `View` 渲染器通过 think-view 的 `Think` 驱动渲染，不依赖主项目的 `view_path`，也不需要复制到主项目；可通过 `view` 配置改为主项目模板目录。
- 介绍页、编辑器页、分类页与内嵌示例页各自是完整的 HTML 文档，采用 layui 导航与统一页脚，导航折叠脚本位于 `theme/assets/js/common.js`；编辑器页不输出页脚以保留全屏编辑区。
- iframe 版页面位于 `theme/view/iframe`，采用 layuiAdmin 官方原版结构（`layadmin-header` + `layui-fluid` + `layui-card`）；iframe 专用样式位于 `theme/assets/iframe/css`，脚本位于 `theme/assets/iframe/js`，经统一的 `assets/<path>` 路由输出，适合嵌入 layuiAdmin 等后台框架，并复用同一组 JSON 接口。
- 内嵌页面示例位于 `theme/view/iframe.html`，外层保留站点导航与页脚，用 layui 表格列出 `iframe/template`、`iframe/category` 两个内容页并在当前页加载预览；示例脚本位于 `theme/assets/js/iframe.js`。
- 静态资源由 `AssetController` 读取 `theme/assets` 后输出，路径经过 `realpath` 校验并拒绝 `..`，避免目录穿越。
- 资源响应带 `Cache-Control: public, max-age=86400`，字体、CSS、JS、图片等类型会返回对应的 `Content-Type`。
- 页面、接口与资源地址由 `StoreCodeditor` 根据 `route.prefix`、`route.segment` 与 `assets` 推导，修改配置后无需改动模板。

```text
/store/think-store-codeditor/index
/store/think-store-codeditor/template
/store/think-store-codeditor/category
/store/think-store-codeditor/iframe
/store/think-store-codeditor/iframe/template
/store/think-store-codeditor/iframe/category
/store/think-store-codeditor/assets/iframe/css/iframe.css
/store/think-store-codeditor/assets/iframe/js/category.js
/store/think-store-codeditor/assets/layui/css/layui.css
/store/think-store-codeditor/assets/ace/ace.js
```

## 页面功能

- 项目介绍：`/index` 展示包简介、路由清单、包信息与快速开始，提供进入编辑器与分类管理的入口
- 模板编辑器：`/template` 提供模板列表与 Ace 编辑区
- 左侧模板列表：layui 数据表格，显示 ID、标题与分类，支持排序与按 ID、标题或分类筛选
- 主编辑区：Ace Editor 的 `ace/mode/php` 模式，支持 HTML + PHP 混合高亮、自动换行、行号与括号匹配
- 保存模板：按 ID 更新 `newstext`，支持 `Ctrl + S` 快捷键
- 新增模板：新增一条模板记录，可选填写受限制目录内的文件路径，同时生成模板文件
- 分类管理：编辑器页右上角进入分类管理，layui 数据表格展示分类，支持新增、编辑、删除
- iframe 模板列表：`/iframe/template` 用 layui 数据表格展示模板，支持搜索、分类筛选、查看、新增与编辑内容；现有接口不支持删除模板
- iframe 分类管理：`/iframe/category` 用 layui treeTable 展示分类，支持搜索、展开/折叠、新增、编辑与删除；数据含 `pid`/`parent_id` 时自动按父子层级展示
- 内嵌页面示例：`/iframe` 汇总两个 iframe 内容页，支持预览切换、新窗口打开，并提供嵌入代码示例
- 分类字段：分类标题、分类名称、分类图片、分类介绍、排序、状态、最后一级、管理员 ID、站点 ID
- 编辑历史：编辑器页「历史」按钮打开历史抽屉，查看内容快照并还原；保存时可填写修改说明
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
GET /store/think-store-codeditor/api/template/read
```

不带参数时返回模板列表：

```json
{
  "code": 0,
  "msg": "ok",
  "data": {
    "total": 2,
    "items": [
      { "id": 2, "title": "首页模板", "cid": 1 },
      { "id": 1, "title": "关于我们", "cid": 1 }
    ]
  }
}
```

带 `id` 参数时返回单条模板：

```http
GET /store/think-store-codeditor/api/template/read?id=1
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
POST /store/think-store-codeditor/api/template/update
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
POST /store/think-store-codeditor/api/template/create
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
GET /store/think-store-codeditor/api/category/read
```

响应 `data.items` 为分类数组，字段包含 `id`、`title`、`titlepic`、`name`、`sort`、`status`、`intro`、`is_last`、`admin_id`、`site_id`、`create_time`、`update_time`。

新增分类：

```http
POST /store/think-store-codeditor/api/category/create
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
POST /store/think-store-codeditor/api/category/update
Content-Type: application/json
```

请求体在新增字段基础上增加 `id`，例如 `{"id": 1, "title": "企业模板"}`。

删除分类：

```http
POST /store/think-store-codeditor/api/category/delete
Content-Type: application/json
```

```json
{ "id": 1 }
```

`id` 不存在时返回 HTTP 404 与 `code: 1`。

### 编辑历史

历史接口与模板接口共用统一的 `{code, msg, data}` 结构，只提供列表、详情、覆盖 template 三个接口。

读取某模板的历史列表：

```http
GET /store/think-store-codeditor/api/template/history?template_id=1
```

返回 `data.items` 数组，字段包含 `id`、`template_id`、`title`、`intro`、`newstime`、`admin_id`、`site_id`、`create_time`、`update_time`，不返回 `newstext` 与 `newstext_hash`。

读取历史详情：

```http
GET /store/think-store-codeditor/api/template/history/detail?id=1
```

返回 `newstext` 与上述元数据；可附带 `template_id` 校验历史归属。

还原到历史版本：

```http
POST /store/think-store-codeditor/api/template/history/restore
Content-Type: application/json
```

```json
{ "id": 1 }
```

服务端读取历史快照，把覆盖前的当前内容再写入一条历史，然后用快照更新 `template.newstext`；只恢复内容，不恢复分类、标题与介绍。

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

模板编辑历史表 `template_history` 通过 `template_id` 关联模板，保存完整内容快照：

```sql
CREATE TABLE `template_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '历史id',
  `template_id` int unsigned NOT NULL DEFAULT 0 COMMENT '模板id',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '模板标题快照',
  `intro` varchar(255) NOT NULL DEFAULT '' COMMENT '修改说明',
  `newstext` longtext NULL COMMENT '模板内容快照',
  `newstext_hash` char(64) NOT NULL DEFAULT '' COMMENT '内容sha256',
  `newstime` int unsigned NOT NULL DEFAULT 0 COMMENT '修改快照时间',
  `admin_id` int unsigned NOT NULL DEFAULT 0 COMMENT '管理员id',
  `site_id` int unsigned NOT NULL DEFAULT 0 COMMENT '站点id',
  `create_time` int unsigned NOT NULL DEFAULT 0 COMMENT '记录创建时间',
  `update_time` int unsigned NOT NULL DEFAULT 0 COMMENT '记录更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_template_id` (`template_id`, `id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='模板编辑历史表';
```

历史表名由 `database.history_table` 配置；保存时用 `newstext_hash` 与最近一条历史比较，内容未变化不重复记录；每个模板保留最近 `history.limit` 条；模板删除时需按 `template_id` 清理历史。

表名可配置，模板字段固定，见下文 `database` 配置。

## 配置

### route 路由

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `route.enabled` | `true` | 是否随服务自动注册页面、接口与资源路由；关闭后可在主项目 `route` 目录自行注册 |
| `route.prefix` | `store` | 路由前缀，留空表示不带前缀 |
| `route.segment` | `think-store-codeditor` | 包内命名空间，留空表示页面直接挂在路由前缀下 |
| `route.middleware` | `[]` | 应用到整组路由上的中间件，可填写主项目鉴权中间件类名或别名 |
| `assets` | `assets` | 顶层静态资源地址；相对路径拼在命名空间后，`/` 开头视为外部托管的绝对地址 |
| `view` | 留空 | 顶层视图目录；留空使用包内 `theme/view`，非空时该值即模板目录，相对路径从主项目根目录算起 |

`route.segment` 留空时页面落在 `/{prefix}/index`、`/{prefix}/category` 与 `/{prefix}/api/*`；`assets` 留空时回退为 `assets`；`view` 留空时使用包内 `theme/view`，填写相对路径（如 `view/think-store-codeditor`）时从主项目根目录算起，`/` 或盘符开头视为绝对路径。

### database 数据库

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `database.table` | `template` | 模板数据表名，不含表前缀，框架自动拼接 |
| `database.category_table` | `template_category` | 分类数据表名，不含表前缀，框架自动拼接 |
| `database.history_table` | `template_history` | 历史数据表名，不含表前缀，框架自动拼接 |


模板字段固定为 `id`、`title`、`cid`、`intro`、`path`、`newstext`，不再提供字段名映射配置。`table`、`category_table`、`history_table` 只允许英文、数字与下划线，避免配置拼接注入。

### session 主项目 Session

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `session.admin_session_key` | 空字符串 | 从主项目 Session 读取操作人 id 的键，留空记 0 |
| `session.site_session_key` | 空字符串 | 从主项目 Session 读取站点 id 的键，留空记 0 |

模板、分类、历史三张表共用该配置写入 `admin_id` 与 `site_id`。

### history 编辑历史

| 配置项 | 默认值 | 说明 |
| --- | --- | --- |
| `history.enabled` | `true` | 是否记录模板编辑历史；关闭后历史接口返回 422 |
| `history.limit` | `50` | 每个模板保留最近多少条，`0` 表示不裁剪 |
| `history.record_on_create` | `true` | 新增模板时是否记录初始版本 |

历史表名由 `database.history_table` 配置。历史写入与模板保存放在同一事务；还原时先把覆盖前的内容写入历史，再用历史快照更新模板内容。历史列表接口不返回 `newstext` 与 `newstext_hash`，详情接口按需返回。

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

服务注册后，可通过容器获取元数据类与数据仓储：

```php
use Youvision\ThinkStoreCodeditor\Categories;
use Youvision\ThinkStoreCodeditor\PathGuard;
use Youvision\ThinkStoreCodeditor\StoreCodeditor;
use Youvision\ThinkStoreCodeditor\Templates;

/** @var StoreCodeditor $codeditor */
$codeditor = app(StoreCodeditor::class);
// 也可使用别名：app('store_codeditor')
$base   = $codeditor->base();          // /store/think-store-codeditor
$home   = $codeditor->index();         // /store/think-store-codeditor/index
$assets = $codeditor->assets();        // /store/think-store-codeditor/assets
$api    = $codeditor->apiTemplate();   // /store/think-store-codeditor/api/template

/** @var Templates $templates */
$templates = app(Templates::class);
// 也可使用别名：app('store_codeditor.templates')

/** @var Categories $categories */
$categories = app(Categories::class);
// 也可使用别名：app('store_codeditor.categories')

/** @var PathGuard $guard */
$guard = app(PathGuard::class);
// 也可使用别名：app('store_codeditor.path_guard')

$list = $templates->lists();
$categories->lists();
```

这些类都支持脱离容器使用：

```php
$codeditor  = new StoreCodeditor((array) config('store_codeditor'), __DIR__);
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
│  ├─ StoreCodeditor.php         # 包元数据与地址推导
│  ├─ Operator.php               # Session 操作人与站点上下文
│  ├─ CodeditorController.php     # 模板页面与读取、更新、新增接口
│  ├─ CategoryController.php      # 分类页面与增改删接口
│  ├─ AssetController.php         # 包内静态资源输出
│  ├─ View.php                   # 包内模板渲染器
│  ├─ Templates.php              # 模板数据仓储
│  ├─ Categories.php             # 分类数据仓储
│  ├─ Template.php               # 模板数据模型
│  ├─ TemplateCategory.php       # 模板分类模型
│  ├─ TemplateHistory.php        # 模板编辑历史模型
│  ├─ TemplateHistories.php      # 历史快照仓储
│  ├─ TemplateHistoryController.php # 历史列表、详情、还原接口
│  └─ PathGuard.php              # 模板文件路径守卫
├─ theme/
│  ├─ view/
│  │  ├─ index.html              # 项目介绍页模板（完整文档）
│  │  ├─ template.html           # 模板代码编辑器页面模板
│  │  ├─ category.html           # 分类管理页面模板
│  │  ├─ iframe.html             # 内嵌页面示例（列表 + 预览）
│  │  └─ iframe/
│  │     ├─ template.html        # iframe 版模板列表页
│  │     └─ category.html        # iframe 版分类管理页
│  └─ assets/
│     ├─ css/index.css           # 页面自定义样式
│     ├─ iframe/
│     │  ├─ css/iframe.css       # layuiAdmin 原版风格补充样式
│     │  └─ js/
│     │     ├─ category.js       # iframe 分类管理脚本
│     │     └─ template.js       # iframe 模板列表脚本
│     ├─ js/common.js            # 站点导航折叠脚本
│     ├─ js/iframe.js            # 内嵌页面示例脚本
│     ├─ js/codeditor.js         # 编辑器页面脚本
│     ├─ js/category.js          # 分类管理页面脚本
│     ├─ js/history.js           # 编辑历史抽屉与还原脚本
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

- `composer.json` 的包名、依赖、PSR-4 自动加载、服务注册、配置声明与 `extra.store.home`
- `config/store_codeditor.php` 的默认配置项、`route.segment` 与顶层 `assets`
- 路由文件、服务文件、控制器、渲染器与前端资源的关键实现
- `StoreCodeditor` 在默认、空前缀、空命名空间、自定义 segment 与绝对 assets 下的地址推导
- `theme/view` 与 `theme/view/iframe` 的完整文档结构、页面变量、layui 数据表格与 assets/iframe 资源路由引用
- 内嵌页面示例列表、预览切换脚本与 layui 导航折叠脚本
- iframe 页面的 layuiAdmin 原版结构、treeTable/table 渲染、增改删入口与接口配置
- `session` 操作人配置与 `Operator` 上下文，模板、分类、历史三张表共用
- 编辑历史的配置、历史模型与仓储、三个接口、保存事务、页面历史入口与还原脚本
- `AssetController` 的资源解析、MIME 类型与目录穿越防护
- `PathGuard` 的目录白名单、目录穿越拦截、扩展名限制、覆盖开关与文件写入
- 全部 PHP 文件的语法

## 许可证

MIT，详见 `LICENSE`。
## 相关项目

ThinkPHP-PanGu 是一个面向 ThinkPHP 的开源工程索引，收录组件、框架、服务与发布工具：[https://github.com/indiboy-create/ThinkPHP-PanGu/](https://github.com/indiboy-create/ThinkPHP-PanGu/)。