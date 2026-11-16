<?php

declare(strict_types=1);

$packageRoot = dirname(__DIR__);
$failures    = [];

$check = static function (bool $ok, string $message) use (&$failures): void {
    if (!$ok) {
        $failures[] = $message;
    }
};

$expectThrow = static function (callable $callback, string $message) use (&$failures): void {
    try {
        $callback();
    } catch (Throwable $e) {
        return;
    }

    $failures[] = $message;
};

$remove = static function (string $dir) use (&$remove): void {
    if (!is_dir($dir)) {
        return;
    }

    $items = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($items as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($dir);
};

$composerFile = $packageRoot . '/composer.json';
$check(is_file($composerFile), 'composer.json 应存在');

$composer = is_file($composerFile)
    ? json_decode((string) file_get_contents($composerFile), true)
    : null;

$check(is_array($composer), 'composer.json 应为有效 JSON');

if (is_array($composer)) {
    $check(($composer['name'] ?? '') === 'youvision/think-store-codeditor', '包名应为 youvision/think-store-codeditor');
    $check(($composer['type'] ?? '') === 'library', 'composer.json 的 type 应为 library');
    $check(($composer['license'] ?? '') === 'MIT', 'composer.json 的 license 应为 MIT');
    $check(trim((string) ($composer['description'] ?? '')) !== '', 'composer.json 应包含包描述');
    $check(($composer['require']['php'] ?? '') === '>=8.0', 'composer.json 应要求 PHP >= 8.0');
    $check(($composer['require']['topthink/framework'] ?? '') === '^8.0', 'composer.json 应依赖 topthink/framework ^8.0');
    $check(($composer['require']['topthink/think-view'] ?? '') === '^2.0', 'composer.json 应依赖 topthink/think-view ^2.0');
    $check(!isset($composer['require']['youvision/think-static']), 'composer.json 不应再依赖 youvision/think-static');
    $check(
        ($composer['autoload']['psr-4']['Youvision\\ThinkStoreCodeditor\\'] ?? '') === 'src/',
        'composer.json 应配置 Youvision\\ThinkStoreCodeditor 的 PSR-4 自动加载'
    );
    $check(
        in_array('Youvision\\ThinkStoreCodeditor\\Service', (array) ($composer['extra']['think']['services'] ?? []), true),
        'composer.json 应注册 ThinkPHP 服务'
    );
    $check(
        ($composer['extra']['think']['config']['store_codeditor'] ?? '') === 'config/store_codeditor.php',
        'composer.json 应声明 store_codeditor 配置'
    );
    $check(
        ($composer['extra']['store']['home'] ?? '') === '/store/think-store-codeditor/index',
        'composer.json 应通过 extra.store.home 声明首页'
    );
    $check(
        ($composer['scripts']['test'] ?? '') === '@php tests/verify.php',
        'composer.json 的 test 脚本应执行 tests/verify.php'
    );
}

$requiredFiles = [
    'config/store_codeditor.php',
    'route/store_codeditor.php',
    'src/AssetController.php',
    'src/Categories.php',
    'src/CategoryController.php',
    'src/CodeditorController.php',
    'src/Operator.php',
    'src/PathGuard.php',
    'src/Service.php',
    'src/StoreCodeditor.php',
    'src/Template.php',
    'src/TemplateCategory.php',
    'src/TemplateHistory.php',
    'src/TemplateHistories.php',
    'src/TemplateHistoryController.php',
    'src/Templates.php',
    'src/View.php',
    'theme/view/index.html',
    'theme/view/template.html',
    'theme/view/category.html',
    'theme/view/iframe.html',
    'theme/view/iframe/category.html',
    'theme/view/iframe/template.html',
    'theme/assets/iframe/css/iframe.css',
    'theme/assets/iframe/js/category.js',
    'theme/assets/iframe/js/template.js',

    'theme/assets/css/index.css',
    'theme/assets/js/common.js',
    'theme/assets/js/iframe.js',
    'theme/assets/js/codeditor.js',
    'theme/assets/js/category.js',
    'theme/assets/js/history.js',
    'theme/assets/ace/ace.js',
    'theme/assets/ace/mode-php.js',
    'theme/assets/ace/theme-monokai.js',
    'theme/assets/ace/worker-php.js',
    'theme/assets/layui/layui.js',
    'theme/assets/layui/css/layui.css',
    'README.md',
    'LICENSE',
];

foreach ($requiredFiles as $file) {
    $check(is_file($packageRoot . '/' . $file), $file . ' 应存在');
}

$check(!is_dir($packageRoot . '/project'), 'project 目录应已迁移为 theme');
$check(!is_file($packageRoot . '/src/TemplateController.php'), '控制器应重命名为 CodeditorController');
$check(!is_dir($packageRoot . '/theme/view/layout'), '布局片段应内联到页面，不应保留 layout 目录');

$configFile = $packageRoot . '/config/store_codeditor.php';
$config     = is_file($configFile) ? require $configFile : [];

$check(($config['route']['enabled'] ?? null) === true, '默认应启用内置路由');
$check(($config['route']['prefix'] ?? '') === 'store', '默认路由前缀应为 store');
$check(($config['route']['segment'] ?? '') === 'think-store-codeditor', '默认包内命名空间应为 think-store-codeditor');
$check(($config['route']['middleware'] ?? null) === [], '默认路由中间件应为空数组');
$check(!isset($config['route']['rules']), '路由规则应由 segment 推导，不应保留 route.rules');
$check(!isset($config['route']['assets']), '静态资源配置应位于顶层 assets');
$check(($config['assets'] ?? '') === 'assets', '默认静态资源路径应为 assets');
$check(($config['view'] ?? 'x') === '', 'view 默认应留空，使用包内 theme/view');

$check(($config['database']['table'] ?? '') === 'template', '默认模板数据表应为 template');
$check(($config['database']['category_table'] ?? '') === 'template_category', '默认分类数据表应为 template_category');
$check(
    !isset($config['database']['id_field']) && !isset($config['database']['content_field']),
    '数据库字段名应固定，不再提供字段映射配置'
);
$check(
    !isset($config['database']['list_limit']) && !isset($config['database']['excerpt_length']),
    '列表不应再限制条数或生成摘要'
);

$check(($config['file']['enabled'] ?? null) === true, '默认应允许新增模板时创建文件');
$check(($config['file']['root'] ?? '') === 'template', '默认可编辑根目录应为 template');
$check(($config['file']['allow_dirs'] ?? []) === ['default'], '默认可编辑目录应为 default');
$check(($config['file']['ext'] ?? []) === ['html'], '默认扩展名白名单应为 html');
$check(($config['file']['overwrite'] ?? null) === false, '默认不覆盖已存在的模板文件');
$check(($config['file']['create_dir'] ?? null) === true, '默认应自动创建模板目录');

$routeSource = (string) file_get_contents($packageRoot . '/route/store_codeditor.php');
$check(str_contains($routeSource, "config('store_codeditor.route.segment'"), '路由应读取 route.segment 配置');
$check(str_contains($routeSource, "\$base = \$segment === '' ? '' : \$segment . '/'"), '路由应按 segment 推导基础路径');
$check(str_contains($routeSource, "Route::get(\$base . 'index'"), '路由应注册项目介绍页面');
$check(str_contains($routeSource, "Route::get(\$base . 'template'"), '路由应注册模板编辑器页面');
$check(str_contains($routeSource, "Route::get(\$base . 'category'"), '路由应注册分类管理页面');
$check(str_contains($routeSource, "Route::get(\$base . 'iframe/template'"), '路由应注册 iframe 模板列表页');
$check(str_contains($routeSource, "Route::get(\$base . 'iframe/category'"), '路由应注册 iframe 分类管理页');
$check(str_contains($routeSource, "Route::get(\$base . 'iframe', [CodeditorController::class, 'iframeIndex']"), '路由应注册内嵌页面示例页');

$check(str_contains($routeSource, "Route::get(\$base . 'api/template/read'"), '路由应注册模板读取接口');
$check(str_contains($routeSource, "Route::post(\$base . 'api/template/update'"), '路由应注册模板更新接口');
$check(str_contains($routeSource, "Route::post(\$base . 'api/template/create'"), '路由应注册模板新增接口');
$check(str_contains($routeSource, "Route::get(\$base . 'api/category/read'"), '路由应注册分类读取接口');
$check(str_contains($routeSource, "Route::post(\$base . 'api/category/create'"), '路由应注册分类新增接口');
$check(str_contains($routeSource, "Route::post(\$base . 'api/category/update'"), '路由应注册分类更新接口');
$check(str_contains($routeSource, "Route::post(\$base . 'api/category/delete'"), '路由应注册分类删除接口');
$check(str_contains($routeSource, "Route::get(\$base . 'assets/<path>'"), '路由应注册包内静态资源');
$check(str_contains($routeSource, 'AssetController'), '资源路由应使用 AssetController');
$check(str_contains($routeSource, "pattern(['path' => '.*'])"), '资源路由应匹配多级路径');
$check(str_contains($routeSource, 'middleware($middleware)'), '路由应应用中间件配置');
$check(str_contains($routeSource, 'Route::group($prefix, $register)'), '路由应统一按前缀分组注册');
$check(str_contains($routeSource, 'CodeditorController::class'), '路由应指向 CodeditorController');
$check(str_contains($routeSource, 'CategoryController::class'), '路由应指向 CategoryController');

$serviceSource = (string) file_get_contents($packageRoot . '/src/Service.php');
$check(str_contains($serviceSource, 'loadRoutesFrom'), '服务应在 boot 阶段加载路由');
$check(str_contains($serviceSource, 'mergeConfig'), '服务应合并包内默认配置');
$check(str_contains($serviceSource, 'array_replace_recursive'), '服务应深度合并配置');
$check(str_contains($serviceSource, "bind('store_codeditor'"), '服务应绑定 StoreCodeditor');
$check(str_contains($serviceSource, "bind('store_codeditor.operator'"), '服务应绑定 Operator');
$check(str_contains($serviceSource, "bind('store_codeditor.templates'"), '服务应绑定 Templates');
$check(str_contains($serviceSource, "bind('store_codeditor.categories'"), '服务应绑定 Categories');
$check(str_contains($serviceSource, "bind('store_codeditor.path_guard'"), '服务应绑定 PathGuard');
$check(str_contains($serviceSource, 'instance(View::class'), '服务应注册包内视图渲染器');
$check(str_contains($serviceSource, 'getRootPath'), '服务应使用主项目根目录');

$metaSource = (string) file_get_contents($packageRoot . '/src/StoreCodeditor.php');
$check(str_contains($metaSource, "DEFAULT_SEGMENT = 'think-store-codeditor'"), '元数据类应定义默认命名空间');
$check(str_contains($metaSource, 'function base'), '元数据类应提供 base 地址');
$check(str_contains($metaSource, 'function index'), '元数据类应提供介绍页地址');
$check(str_contains($metaSource, 'function template'), '元数据类应提供模板编辑器地址');
$check(str_contains($metaSource, 'function category'), '元数据类应提供分类页面地址');
$check(str_contains($metaSource, 'function iframe'), '元数据类应提供内嵌示例地址');
$check(str_contains($metaSource, 'function iframeTemplate'), '元数据类应提供 iframe 模板管理地址');
$check(str_contains($metaSource, 'function iframeCategory'), '元数据类应提供 iframe 分类管理地址');
$check(str_contains($metaSource, 'function assets'), '元数据类应提供资源地址');
$check(str_contains($metaSource, 'function apiTemplate'), '元数据类应提供模板接口地址');
$check(str_contains($metaSource, 'function apiCategory'), '元数据类应提供分类接口地址');
$check(str_contains($metaSource, 'function version'), '元数据类应提供版本号');
$check(str_contains($metaSource, 'function routes'), '元数据类应提供路由清单');

$controllerSource = (string) file_get_contents($packageRoot . '/src/CodeditorController.php');
$check(str_contains($controllerSource, 'StoreCodeditor $codeditor'), '编辑器控制器应注入元数据类');
$check(str_contains($controllerSource, 'function index'), '编辑器控制器应实现介绍页');
$check(str_contains($controllerSource, 'function template'), '编辑器控制器应实现模板编辑器页');
$check(str_contains($controllerSource, 'function iframe'), '编辑器控制器应实现 iframe 模板列表页');
$check(str_contains($controllerSource, 'function iframeIndex'), '编辑器控制器应实现内嵌页面示例页');
$check(str_contains($controllerSource, "'iframePages'"), '内嵌示例页应注入页面清单');
$check(str_contains($controllerSource, "'iframeDefault'"), '内嵌示例页应注入默认预览地址');
$check(str_contains($controllerSource, '$this->codeditor->template()'), '编辑器控制器应使用元数据模板编辑器地址');
$check(str_contains($controllerSource, 'function read'), '编辑器控制器应实现读取接口');
$check(str_contains($controllerSource, 'function update'), '编辑器控制器应实现更新接口');
$check(str_contains($controllerSource, 'function create'), '编辑器控制器应实现新增接口');
$check(str_contains($controllerSource, '$this->codeditor->base()'), '编辑器控制器应使用元数据 base 地址');
$check(str_contains($controllerSource, '$this->codeditor->assets()'), '编辑器控制器应使用元数据资源地址');
$check(str_contains($controllerSource, '$this->codeditor->apiTemplate()'), '编辑器控制器应使用元数据模板接口');
$check(str_contains($controllerSource, 'resolveForWrite'), '编辑器控制器新增接口应校验模板文件路径');
$check(!str_contains($controllerSource, 'route.rules'), '编辑器控制器不应再读取 route.rules');

$categoryControllerSource = (string) file_get_contents($packageRoot . '/src/CategoryController.php');
$check(str_contains($categoryControllerSource, 'StoreCodeditor $codeditor'), '分类控制器应注入元数据类');
$check(str_contains($categoryControllerSource, 'function index'), '分类控制器应实现页面');
$check(str_contains($categoryControllerSource, 'function iframe'), '分类控制器应实现 iframe 分类管理页');
$check(str_contains($categoryControllerSource, 'function read'), '分类控制器应实现读取接口');
$check(str_contains($categoryControllerSource, 'function create'), '分类控制器应实现新增接口');
$check(str_contains($categoryControllerSource, 'function update'), '分类控制器应实现更新接口');
$check(str_contains($categoryControllerSource, 'function delete'), '分类控制器应实现删除接口');
$check(str_contains($categoryControllerSource, '$this->codeditor->apiCategory()'), '分类控制器应使用元数据分类接口');
$check(str_contains($categoryControllerSource, '$this->codeditor->apiTemplate()'), '分类控制器应为页脚提供模板接口地址');
$check(str_contains($categoryControllerSource, "'iframe'"), '分类管理页面应注入内嵌示例入口地址');
$check(!str_contains($categoryControllerSource, 'route.rules'), '分类控制器不应再读取 route.rules');

$categorySource = (string) file_get_contents($packageRoot . '/src/TemplateCategory.php');
$check(str_contains($categorySource, 'extends Model'), '分类模型应继承 think\Model');
$check(str_contains($categorySource, "'template_category'"), '分类模型默认表名应为 template_category');

$categoriesSource = (string) file_get_contents($packageRoot . '/src/Categories.php');
$check(str_contains($categoriesSource, 'function payload'), '分类仓储应过滤写入字段');
$check(str_contains($categoriesSource, "'titlepic'"), '分类仓储应支持 titlepic 字段');
$check(str_contains($categoriesSource, "'is_last'"), '分类仓储应支持 is_last 字段');

$templatesSource = (string) file_get_contents($packageRoot . '/src/Templates.php');
$check(str_contains($templatesSource, 'new Template('), 'Templates 应使用 Template 模型查询');
$check(str_contains($templatesSource, 'function categories'), 'Templates 应提供分类读取方法');
$check(!str_contains($templatesSource, 'Db::'), 'Templates 不应使用 Db 原生查询');

$viewSource = (string) file_get_contents($packageRoot . '/src/View.php');
$check(str_contains($viewSource, 'use think\\view\\driver\\Think'), '渲染器应使用 think-view 模板驱动');
$check(str_contains($viewSource, "['view']"), '渲染器应读取 view 配置');
$check(str_contains($viewSource, "'/theme/view/'"), '渲染器留空时应使用包内 theme/view');
$check(str_contains($viewSource, 'getRootPath'), '渲染器相对路径应从主项目根目录算起');
$check(str_contains($viewSource, 'getRuntimePath'), '渲染器应使用主项目运行时目录');
$check(str_contains($viewSource, 'ob_start'), '渲染器应捕获模板输出');


$pageSource = (string) file_get_contents($packageRoot . '/theme/view/index.html');
$check(str_contains($pageSource, '<!DOCTYPE html>'), '介绍页面应是完整 HTML 文档');
$check(str_contains($pageSource, '{$title}'), '介绍页面应输出页面标题');
$check(str_contains($pageSource, '{$description}'), '介绍页面应输出页面描述');
$check(str_contains($pageSource, '{$assets}/layui/css/layui.css'), '介绍页面应引用 layui 样式');
$check(str_contains($pageSource, '{$assets}/css/index.css'), '介绍页面应引用页面样式');
$check(str_contains($pageSource, '{$assets}/layui/layui.js'), '介绍页面应加载 layui');
$check(str_contains($pageSource, 'codeditor-header'), '介绍页面应包含站点头部');
$check(str_contains($pageSource, 'layui-header'), '介绍页面应采用 layui 页头');
$check(str_contains($pageSource, 'codeditor-footer'), '介绍页面应包含站点页脚');
$check(str_contains($pageSource, 'layui-footer'), '介绍页面应采用 layui 页脚');
$check(str_contains($pageSource, 'codeditor-hero'), '介绍页面应包含 hero 区域');
$check(str_contains($pageSource, 'codeditor-hero__row'), '介绍页面 hero 应使用 layui 栅格');
$check(str_contains($pageSource, 'layui-row'), '介绍页面应采用 layui 栅格');
$check(str_contains($pageSource, 'layui-card codeditor-glass'), '英雄区右侧应使用 layui-card');
$check(str_contains($pageSource, 'layui-card codeditor-cta'), '底部 CTA 应使用 layui-card');
$check(str_contains($pageSource, 'layui-table codeditor-endpoints'), '路由表应使用 layui-table');
$check(str_contains($pageSource, 'layui-table codeditor-config'), '配置表应使用 layui-table');
$check(str_contains($pageSource, 'name="routes"'), '介绍页面应输出路由清单');
$check(str_contains($pageSource, 'codeditor-cta'), '介绍页面应包含底部 CTA');
$check(str_contains($pageSource, '{$template}'), '介绍页面应包含模板编辑器入口');
$check(str_contains($pageSource, '{$category}'), '介绍页面应包含分类管理入口');
$check(str_contains($pageSource, '{$iframe}'), '介绍页面应包含内嵌示例入口');
$check(str_contains($pageSource, 'layui-nav'), '介绍页面应采用 layui 导航');
$check(str_contains($pageSource, '{$assets}/js/common.js'), '介绍页面应加载公共导航脚本');
$check(str_contains($pageSource, '{$templateApi}/read'), '介绍页面应展示模板读取接口');
$check(str_contains($pageSource, '{$categoryApi}/read'), '介绍页面应展示分类读取接口');
$check(!str_contains($pageSource, 'id="tpl-table"'), '介绍页面不应包含编辑器表格');

$templatePageSource = (string) file_get_contents($packageRoot . '/theme/view/template.html');
$check(str_contains($templatePageSource, '<!DOCTYPE html>'), '编辑器页面应是完整 HTML 文档');
$check(str_contains($templatePageSource, 'codeditor-header'), '编辑器页面应包含站点头部');
$check(str_contains($templatePageSource, 'codeditor-footer'), '编辑器页面应包含站点页脚');
$check(str_contains($templatePageSource, '{$assets}/ace/ace.js'), '编辑器页面应加载 Ace');
$check(str_contains($templatePageSource, '{$assets}/js/history.js'), '编辑器页面应加载历史脚本');
$check(str_contains($templatePageSource, 'window.CODEDITOR_CONFIG'), '编辑器页面应注入接口配置');
$check(str_contains($templatePageSource, '{$base}'), '编辑器页面应使用元数据 base 地址');
$check(str_contains($templatePageSource, '{$assets}'), '编辑器页面应使用元数据资源地址');
$check(str_contains($templatePageSource, '{$templateApi}/read'), '编辑器页面应使用模板读取接口');
$check(str_contains($templatePageSource, '{$templateApi}/update'), '编辑器页面应使用模板更新接口');
$check(str_contains($templatePageSource, '{$templateApi}/create'), '编辑器页面应使用模板新增接口');
$check(str_contains($templatePageSource, 'id="tpl-table"'), '编辑器页面应包含 layui 数据表格容器');
$check(str_contains($templatePageSource, '{$iframe}'), '编辑器页面应包含内嵌示例入口');
$check(str_contains($templatePageSource, '{$assets}/js/common.js'), '编辑器页面应加载公共导航脚本');
$check(!str_contains($templatePageSource, 'cdn.'), '编辑器页面不应引用 CDN 资源');

$categoryPageSource = (string) file_get_contents($packageRoot . '/theme/view/category.html');
$check(str_contains($categoryPageSource, '<!DOCTYPE html>'), '分类页面应是完整 HTML 文档');
$check(str_contains($categoryPageSource, 'codeditor-footer'), '分类页面应包含站点页脚');
$check(str_contains($categoryPageSource, '{$assets}/js/category.js'), '分类页面应加载分类脚本');
$check(str_contains($categoryPageSource, 'window.CODEDITOR_CATEGORY'), '分类页面应注入接口配置');
$check(str_contains($categoryPageSource, '{$categoryApi}/read'), '分类页面应使用分类读取接口');
$check(str_contains($categoryPageSource, '{$categoryApi}/create'), '分类页面应使用分类新增接口');
$check(str_contains($categoryPageSource, '{$categoryApi}/update'), '分类页面应使用分类更新接口');
$check(str_contains($categoryPageSource, '{$categoryApi}/delete'), '分类页面应使用分类删除接口');
$check(str_contains($categoryPageSource, 'id="category-table"'), '分类页面应包含 layui 数据表格容器');
$check(str_contains($categoryPageSource, 'category-form-tpl'), '分类页面应包含新增/编辑表单');
$check(str_contains($categoryPageSource, '{$iframe}'), '分类页面应包含内嵌示例入口');
$check(str_contains($categoryPageSource, '{$assets}/js/common.js'), '分类页面应加载公共导航脚本');

$iframeDemoSource = (string) file_get_contents($packageRoot . '/theme/view/iframe.html');
$check(str_contains($iframeDemoSource, '<!DOCTYPE html>'), '内嵌示例页应是完整 HTML 文档');
$check(str_contains($iframeDemoSource, 'codeditor-header'), '内嵌示例页应包含站点头部');
$check(str_contains($iframeDemoSource, 'codeditor-footer'), '内嵌示例页应包含站点页脚');
$check(str_contains($iframeDemoSource, 'codeditor-hero--compact'), '内嵌示例页应包含紧凑 hero');
$check(str_contains($iframeDemoSource, 'codeditor-iframe-table'), '内嵌示例页应包含页面列表表格');
$check(str_contains($iframeDemoSource, '{$iframeDefault}'), '内嵌示例页应使用默认预览地址');
$check(str_contains($iframeDemoSource, '{$iframePagesJson|raw}'), '内嵌示例页应注入页面清单 JSON');
$check(str_contains($iframeDemoSource, '{$assets}/js/iframe.js'), '内嵌示例页应加载 iframe 示例脚本');

$iframeTemplatePageSource = (string) file_get_contents($packageRoot . '/theme/view/iframe/template.html');
$check(str_contains($iframeTemplatePageSource, '<!DOCTYPE html>'), 'iframe 模板列表页应是完整 HTML 文档');
$check(str_contains($iframeTemplatePageSource, 'iframe-template-table'), 'iframe 模板列表页应包含数据表格容器');
$check(str_contains($iframeTemplatePageSource, 'window.CODEDITOR_IFRAME_TEMPLATE'), 'iframe 模板列表页应注入接口配置');
$check(str_contains($iframeTemplatePageSource, '{$templateApi}/create'), 'iframe 模板列表页应使用模板新增接口');
$check(str_contains($iframeTemplatePageSource, '{$categoryApi}/read'), 'iframe 模板列表页应使用分类读取接口');
$check(str_contains($iframeTemplatePageSource, 'layadmin-header'), 'iframe 模板列表页应采用 layuiAdmin 原版头部');
$check(str_contains($iframeTemplatePageSource, '{$assets}/iframe/css/iframe.css'), 'iframe 模板列表页应加载 iframe 目录样式');
$check(str_contains($iframeTemplatePageSource, '{$assets}/iframe/js/template.js'), 'iframe 模板列表页应加载 iframe 目录脚本');

$iframeCategoryPageSource = (string) file_get_contents($packageRoot . '/theme/view/iframe/category.html');
$check(str_contains($iframeCategoryPageSource, '<!DOCTYPE html>'), 'iframe 分类页应是完整 HTML 文档');
$check(str_contains($iframeCategoryPageSource, 'iframe-category-table'), 'iframe 分类页应包含树形表格容器');
$check(str_contains($iframeCategoryPageSource, 'window.CODEDITOR_IFRAME_CATEGORY'), 'iframe 分类页应注入接口配置');
$check(str_contains($iframeCategoryPageSource, '{$categoryApi}/delete'), 'iframe 分类页应使用分类删除接口');
$check(str_contains($iframeCategoryPageSource, 'layadmin-header'), 'iframe 分类页应采用 layuiAdmin 原版头部');
$check(str_contains($iframeCategoryPageSource, '{$assets}/iframe/css/iframe.css'), 'iframe 分类页应加载 iframe 目录样式');
$check(str_contains($iframeCategoryPageSource, '{$assets}/iframe/js/category.js'), 'iframe 分类页应加载 iframe 目录脚本');

$iframeTemplateScript = (string) file_get_contents($packageRoot . '/theme/assets/iframe/js/template.js');
$check(str_contains($iframeTemplateScript, "layui.use(['table', 'layer', 'form']"), 'iframe 模板脚本应加载 layui table、layer 与 form');
$check(str_contains($iframeTemplateScript, 'table.render'), 'iframe 模板脚本应渲染数据表格');
$check(str_contains($iframeTemplateScript, "base + '/api/template/read'"), 'iframe 模板脚本应使用模板读取接口');
$check(str_contains($iframeTemplateScript, "base + '/api/template/create'"), 'iframe 模板脚本应使用模板新增接口');

$iframeCategoryScript = (string) file_get_contents($packageRoot . '/theme/assets/iframe/js/category.js');
$check(str_contains($iframeCategoryScript, "layui.use(['treeTable', 'layer', 'form']"), 'iframe 分类脚本应加载 layui treeTable、layer 与 form');
$check(str_contains($iframeCategoryScript, 'treeTable.render'), 'iframe 分类脚本应渲染树形表格');
$check(str_contains($iframeCategoryScript, "base + '/api/category/delete'"), 'iframe 分类脚本应使用分类删除接口');

$commonScriptSource = (string) file_get_contents($packageRoot . '/theme/assets/js/common.js');
$check(str_contains($commonScriptSource, 'codeditor-header'), '公共脚本应负责站点导航折叠');
$check(str_contains($commonScriptSource, "layui.use(['element']"), '公共脚本应初始化 layui element');

$iframeDemoScript = (string) file_get_contents($packageRoot . '/theme/assets/js/iframe.js');
$check(str_contains($iframeDemoScript, 'codeditor-iframe-pages'), '内嵌示例脚本应读取页面清单');
$check(str_contains($iframeDemoScript, 'codeditor-iframe-preview'), '内嵌示例脚本应切换预览地址');
$check(str_contains($iframeDemoScript, 'table.render'), '内嵌示例脚本应渲染页面列表');

$scriptSource = (string) file_get_contents($packageRoot . '/theme/assets/js/codeditor.js');
$check(str_contains($scriptSource, "layui.use(['table', 'layer', 'form']"), '编辑器脚本应加载 layui table、layer 与 form');
$check(str_contains($scriptSource, 'table.render'), '编辑器脚本应渲染 layui 数据表格');
$check(str_contains($scriptSource, "table.on('row(tpl-table)'"), '编辑器脚本应处理表格行点击');
$check(str_contains($scriptSource, 'fetch('), '编辑器脚本应使用 fetch 读取数据');
$check(str_contains($scriptSource, "base + '/api/template/read'"), '编辑器脚本应使用 api/template 接口地址');
$check(str_contains($scriptSource, "aceBase = assets + '/ace'"), '编辑器脚本应使用 assets 推导 Ace 路径');
$check(str_contains($scriptSource, 'ace/mode/php'), '编辑器脚本应启用 ace/mode/php 混合高亮');
$check(str_contains($scriptSource, "field: 'title'"), '编辑器列表应展示模板标题');
$check(!str_contains($scriptSource, 'excerpt'), '编辑器列表不应使用摘要');
$check(!str_contains($scriptSource, 'cdn.'), '编辑器脚本不应引用 CDN 资源');

$categoryScriptSource = (string) file_get_contents($packageRoot . '/theme/assets/js/category.js');
$check(str_contains($categoryScriptSource, "layui.use(['table', 'layer', 'form']"), '分类脚本应加载 layui table、layer 与 form');
$check(str_contains($categoryScriptSource, 'table.render'), '分类脚本应渲染 layui 数据表格');
$check(str_contains($categoryScriptSource, "table.on('tool(category-table)'"), '分类脚本应处理表格操作');
$check(str_contains($categoryScriptSource, 'layer.confirm'), '分类脚本应提供删除确认');
$check(str_contains($categoryScriptSource, 'fetch('), '分类脚本应使用 fetch 请求接口');
$check(str_contains($categoryScriptSource, "base + '/api/category/read'"), '分类脚本应使用 api/category 接口地址');

$styleSource = (string) file_get_contents($packageRoot . '/theme/assets/css/index.css');
$check(str_contains($styleSource, '--codeditor-brand'), '样式应定义主题变量');
$check(str_contains($styleSource, '.codeditor-table'), '样式应包含 layui 表格区域');
$check(str_contains($styleSource, '.codeditor-admin__body'), '样式应包含分类管理页布局');
$check(str_contains($styleSource, '.codeditor-editor-head .layui-btn'), '样式应适配编辑器工具栏按钮');
$check(str_contains($styleSource, '.codeditor-workspace'), '样式应包含编辑器工作台布局');
$check(str_contains($styleSource, '.codeditor-pane--editor'), '样式应包含编辑器面板');
$check(str_contains($styleSource, '.codeditor-admin__card'), '样式应包含分类管理卡片');
$check(str_contains($styleSource, '@media (max-width: 820px)'), '样式应包含响应式断点');
$check(str_contains($styleSource, '@media (max-width: 640px)'), '样式应包含移动端布局');
$check(str_contains($styleSource, '.codeditor-nav.layui-nav'), '样式应适配 layui 导航');
$check(str_contains($styleSource, '.codeditor-hero--compact'), '样式应包含紧凑 hero');
$check(str_contains($styleSource, '.codeditor-hero__row'), '样式应包含 layui 栅格 hero');
$check(str_contains($styleSource, '.codeditor-doc-card .layui-card-header'), '文档卡片应使用 layui-card 头部');
$check(str_contains($styleSource, '.codeditor-endpoints.layui-table'), '路由表应适配 layui-table');
$check(str_contains($styleSource, '.codeditor-iframe__preview'), '样式应包含内嵌示例预览区');

$check(($config['database']['history_table'] ?? '') === 'template_history', '默认历史数据表应为 template_history');
$check(($config['session']['admin_session_key'] ?? null) === '', '默认不绑定管理员 Session 键');
$check(($config['session']['site_session_key'] ?? null) === '', '默认不绑定站点 Session 键');
$check(($config['history']['enabled'] ?? null) === true, '默认应启用模板编辑历史');
$check(($config['history']['limit'] ?? 0) === 50, '默认历史保留条数应为 50');
$check(($config['history']['record_on_create'] ?? null) === true, '默认应记录新增模板初始版本');
$check(!isset($config['history']['table']), '历史表名应位于 database.history_table');
$check(!isset($config['database']['history']), 'history 行为配置不应嵌套在 database 下');

$check(str_contains($routeSource, "Route::get(\$base . 'api/template/history'"), '路由应注册历史列表接口');
$check(str_contains($routeSource, "Route::get(\$base . 'api/template/history/detail'"), '路由应注册历史详情接口');
$check(str_contains($routeSource, "Route::post(\$base . 'api/template/history/restore'"), '路由应注册历史还原接口');
$check(str_contains($routeSource, 'TemplateHistoryController'), '历史路由应指向 TemplateHistoryController');

$check(str_contains($serviceSource, "bind('store_codeditor.histories'"), '服务应绑定 TemplateHistories');

$operatorSource = (string) file_get_contents($packageRoot . '/src/Operator.php');
$check(str_contains($operatorSource, 'function adminId'), 'Operator 应提供 adminId');
$check(str_contains($operatorSource, 'function siteId'), 'Operator 应提供 siteId');
$check(str_contains($operatorSource, 'session($key)'), 'Operator 应从 Session 读取操作人');

$historySource = (string) file_get_contents($packageRoot . '/src/TemplateHistory.php');
$check(str_contains($historySource, 'extends Model'), '历史模型应继承 think\Model');
$check(str_contains($historySource, "'template_history'"), '历史模型默认表名应为 template_history');

$historiesSource = (string) file_get_contents($packageRoot . '/src/TemplateHistories.php');
$check(str_contains($historiesSource, 'function record'), '历史仓储应提供记录方法');
$check(str_contains($historiesSource, 'function lists'), '历史仓储应提供列表方法');
$check(str_contains($historiesSource, 'function find'), '历史仓储应提供详情方法');
$check(str_contains($historiesSource, 'function prune'), '历史仓储应提供裁剪方法');
$check(str_contains($historiesSource, "hash('sha256'"), '历史仓储应使用 sha256 去重');
$check(str_contains($historiesSource, 'newstext_hash'), '历史仓储应写入 newstext_hash');

$historyControllerSource = (string) file_get_contents($packageRoot . '/src/TemplateHistoryController.php');
$check(str_contains($historyControllerSource, 'function lists'), '历史控制器应实现列表接口');
$check(str_contains($historyControllerSource, 'function detail'), '历史控制器应实现详情接口');
$check(str_contains($historyControllerSource, 'function restore'), '历史控制器应实现还原接口');
$check(str_contains($historyControllerSource, 'Db::transaction'), '历史还原应使用事务');
$check(str_contains($historyControllerSource, '$this->histories->record'), '还原前应记录当前内容');
$check(str_contains($historyControllerSource, '$this->templates->update'), '还原应更新模板内容');

$check(str_contains($controllerSource, '$this->histories->record'), '模板保存应写入历史');
$check(str_contains($controllerSource, 'Db::transaction'), '模板保存应使用事务');
$check(str_contains($controllerSource, 'function validateIntro'), '编辑器控制器应校验修改说明');
$check(str_contains($controllerSource, "\$this->codeditor->apiTemplate('history')"), '编辑器页面应注入历史列表地址');
$check(str_contains($controllerSource, "\$this->codeditor->apiTemplate('history/detail')"), '编辑器页面应注入历史详情地址');
$check(str_contains($controllerSource, "\$this->codeditor->apiTemplate('history/restore')"), '编辑器页面应注入历史还原地址');

$check(str_contains($templatesSource, "getAttr('title')"), 'Templates::find 应读取固定标题字段');
$check(str_contains($templatesSource, "'title'"), 'Templates::find 应返回标题快照');
$check(str_contains($templatesSource, "field(['id', 'title', 'cid'])"), '模板列表应只查询 id、title、cid');
$check(!str_contains($templatesSource, 'excerpt'), '模板列表不应生成摘要');
$check(str_contains($templatesSource, '$this->operator->adminId()'), 'Templates 应写入 Session 操作人');
$check(str_contains($categoriesSource, '$this->operator->adminId()'), 'Categories 应写入 Session 操作人');
$check(str_contains($historiesSource, '$this->operator->adminId()'), 'TemplateHistories 应写入 Session 操作人');
$check(str_contains($historiesSource, "'database.history_table'"), 'TemplateHistories 应读取 database.history_table');

$check(str_contains($templatePageSource, 'id="btn-history"'), '编辑器页面应包含历史入口');
$check(str_contains($templatePageSource, 'history-panel-tpl'), '编辑器页面应包含历史面板模板');
$check(str_contains($templatePageSource, "history: '{\$historyApi}'"), '编辑器页面应注入历史列表地址');
$check(str_contains($templatePageSource, "historyDetail: '{\$historyDetail}'"), '编辑器页面应注入历史详情地址');
$check(str_contains($templatePageSource, "historyRestore: '{\$historyRestore}'"), '编辑器页面应注入历史还原地址');


$historyScriptSource = (string) file_get_contents($packageRoot . '/theme/assets/js/history.js');
$check(str_contains($historyScriptSource, 'table.render'), '历史脚本应渲染 layui 数据表格');
$check(str_contains($historyScriptSource, 'layer.confirm'), '历史脚本应提供还原确认');
$check(str_contains($historyScriptSource, 'fetch('), '历史脚本应使用 fetch 请求接口');
$check(str_contains($historyScriptSource, 'ace.edit'), '历史脚本应使用只读 Ace 展示内容');
$check(str_contains($historyScriptSource, 'codeditor:history-restored'), '历史脚本应通知编辑器刷新内容');

$check(str_contains($styleSource, '.codeditor-intro-body'), '样式应包含项目介绍页布局');
$check(str_contains($styleSource, '.codeditor-history'), '样式应包含历史面板布局');

if (!class_exists(\Youvision\ThinkStoreCodeditor\AssetController::class)) {
    require_once $packageRoot . '/src/AssetController.php';
}

$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('layui/css/layui.css') !== null, '应能解析包内静态资源');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('ace/ace.js') !== null, '应能解析 Ace 资源');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('../composer.json') === null, '应拒绝目录穿越');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('..') === null, '应拒绝上级目录');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('') === null, '空路径应返回 null');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('missing.css') === null, '不存在的资源应返回 null');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolveIn($packageRoot . '/theme/assets/iframe/css', 'iframe.css') !== null, '应能解析 iframe 目录样式');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolveIn($packageRoot . '/theme/assets/iframe/css', '../category.html') === null, 'iframe 资源应拒绝目录穿越');
$check(\Youvision\ThinkStoreCodeditor\AssetController::mime('a.css') === 'text/css; charset=utf-8', 'CSS MIME 应正确');
$check(\Youvision\ThinkStoreCodeditor\AssetController::mime('a.js') === 'application/javascript; charset=utf-8', 'JS MIME 应正确');
$check(\Youvision\ThinkStoreCodeditor\AssetController::mime('a.woff2') === 'font/woff2', '字体 MIME 应正确');
$check(\Youvision\ThinkStoreCodeditor\AssetController::mime('a.unknown') === 'application/octet-stream', '未知类型应回退为 octet-stream');

require_once $packageRoot . '/src/PathGuard.php';

$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'codeditor-' . bin2hex(random_bytes(4));
mkdir($tmp . '/template/default', 0755, true);

$guard = new \Youvision\ThinkStoreCodeditor\PathGuard([
    'root'       => 'template',
    'allow_dirs' => ['default'],
    'ext'        => ['html'],
    'overwrite'  => false,
    'create_dir' => true,
], $tmp);

$target = $guard->resolveForWrite('default/index.html');
$check(
    str_ends_with(str_replace('\\', '/', $target), '/template/default/index.html'),
    '合法路径应解析到 template/default/index.html，实际 ' . $target
);

$guard->put($target, '<html><?php echo "ok"; ?></html>');
$check(is_file($target), '合法路径应成功写入模板文件');
$check(str_contains((string) file_get_contents($target), '<?php echo "ok"; ?>'), '写入的模板文件应保留 HTML + PHP 混合内容');
$check($guard->relative($target) === 'template/default/index.html', 'relative 应返回相对主项目根目录的路径');

$expectThrow(static fn () => $guard->resolveForWrite('../escape.html'), '应拒绝 ../ 目录穿越');
$expectThrow(static fn () => $guard->resolveForWrite('default/../../escape.html'), '应拒绝路径中间的 .. 片段');
$expectThrow(static fn () => $guard->resolveForWrite('/etc/passwd'), '应拒绝绝对路径');
$expectThrow(static fn () => $guard->resolveForWrite('C:\\windows\\test.html'), '应拒绝 Windows 盘符路径');
$expectThrow(static fn () => $guard->resolveForWrite('other/index.html'), '应拒绝不在白名单内的目录');
$expectThrow(static fn () => $guard->resolveForWrite('default/evil.exe'), '应拒绝扩展名白名单之外的文件');
$expectThrow(static fn () => $guard->resolveForWrite('default/noext'), '应拒绝无扩展名文件');
$expectThrow(static fn () => $guard->resolveForWrite('default/'), '应拒绝空路径片段');
$expectThrow(static fn () => $guard->resolveForWrite('default/index.html'), '默认不应覆盖已存在的模板文件');

$overwriteGuard = new \Youvision\ThinkStoreCodeditor\PathGuard([
    'root'       => 'template',
    'allow_dirs' => ['default'],
    'ext'        => ['html'],
    'overwrite'  => true,
    'create_dir' => true,
], $tmp);
$check($overwriteGuard->resolveForWrite('default/index.html') === $target, 'overwrite=true 时应允许解析已存在的模板文件');

$emptyDirsGuard = new \Youvision\ThinkStoreCodeditor\PathGuard([
    'root'       => 'template',
    'allow_dirs' => [],
    'ext'        => ['html'],
    'overwrite'  => false,
    'create_dir' => true,
], $tmp);
$expectThrow(static fn () => $emptyDirsGuard->resolveForWrite('default/index.html'), 'allow_dirs 为空时应禁止创建文件');

$noCreateGuard = new \Youvision\ThinkStoreCodeditor\PathGuard([
    'root'       => 'template',
    'allow_dirs' => ['default'],
    'ext'        => ['html'],
    'overwrite'  => false,
    'create_dir' => false,
], $tmp);
$expectThrow(static fn () => $noCreateGuard->resolveForWrite('default/sub/child.html'), 'create_dir=false 时父目录不存在应报错');

$newDirGuard = new \Youvision\ThinkStoreCodeditor\PathGuard([
    'root'       => 'template',
    'allow_dirs' => ['default'],
    'ext'        => ['html'],
    'overwrite'  => false,
    'create_dir' => true,
], $tmp);
$newDirTarget = $newDirGuard->resolveForWrite('default/sub/child.html');
$check(is_dir(dirname($newDirTarget)), 'create_dir=true 时应自动创建父目录');

$remove($tmp);

require_once $packageRoot . '/src/StoreCodeditor.php';

$meta = new \Youvision\ThinkStoreCodeditor\StoreCodeditor([], $packageRoot);
$check($meta->base() === '/store/think-store-codeditor', '默认 base 应为 /store/think-store-codeditor，实际 ' . $meta->base());
$check($meta->index() === '/store/think-store-codeditor/index', '默认首页应为 /store/think-store-codeditor/index');
$check($meta->category() === '/store/think-store-codeditor/category', '默认分类页应为 /store/think-store-codeditor/category');
$check($meta->assets() === '/store/think-store-codeditor/assets', '默认 assets 应为 /store/think-store-codeditor/assets');
$check($meta->apiTemplate('read') === '/store/think-store-codeditor/api/template/read', '模板接口地址应正确');
$check($meta->apiCategory('delete') === '/store/think-store-codeditor/api/category/delete', '分类接口地址应正确');
$check($meta->version() !== '', '版本号不应为空');

$noPrefix = new \Youvision\ThinkStoreCodeditor\StoreCodeditor(['route' => ['prefix' => '']], $packageRoot);
$check($noPrefix->base() === '/think-store-codeditor', '空前缀时 base 应为 /think-store-codeditor');
$check($noPrefix->index() === '/think-store-codeditor/index', '空前缀时首页应正确');

$emptySegment = new \Youvision\ThinkStoreCodeditor\StoreCodeditor(['route' => ['segment' => '']], $packageRoot);
$check($emptySegment->base() === '/store', 'segment 为空时应直接挂在路由前缀下');
$check($emptySegment->index() === '/store/index', 'segment 为空时首页应挂在路由前缀下');

$custom = new \Youvision\ThinkStoreCodeditor\StoreCodeditor([
    'route'  => ['prefix' => 'store', 'segment' => '/demo/'],
    'assets' => 'static',
], $packageRoot);
$check($custom->base() === '/store/demo', '自定义 segment 应参与 base 推导');
$check($custom->assets() === '/store/demo/static', '相对 assets 应拼在命名空间后');

$absolute = new \Youvision\ThinkStoreCodeditor\StoreCodeditor(['assets' => '/static/store/'], $packageRoot);
$check($absolute->assets() === '/static/store', '绝对 assets 应原样使用并去除末尾斜杠');

if (function_exists('exec')) {
    $phpFiles = [];

    foreach (['src', 'config', 'route', 'tests'] as $directory) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($packageRoot . '/' . $directory, FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }
    }

    foreach ($phpFiles as $file) {
        $output = [];
        $status = 0;

        exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file) . ' 2>&1', $output, $status);

        $check(
            $status === 0,
            'PHP 语法检查失败：' . str_replace($packageRoot . DIRECTORY_SEPARATOR, '', $file) . ' ' . implode(' ', $output)
        );
    }
}

if ($failures === []) {
    echo "PASS\n";
    exit(0);
}

foreach ($failures as $failure) {
    echo 'FAIL: ' . $failure . "\n";
}

exit(1);