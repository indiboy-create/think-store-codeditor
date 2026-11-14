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
        ($composer['scripts']['test'] ?? '') === '@php tests/verify.php',
        'composer.json 的 test 脚本应执行 tests/verify.php'
    );
}

$requiredFiles = [
    'config/store_codeditor.php',
    'route/store_codeditor.php',
    'src/AssetController.php',
    'src/CodeditorController.php',
    'src/PathGuard.php',
    'src/Service.php',
    'src/Template.php',
    'src/Templates.php',
    'src/View.php',
    'theme/view/index.html',
    'theme/assets/css/index.css',
    'theme/assets/js/codeditor.js',
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

$configFile = $packageRoot . '/config/store_codeditor.php';
$config     = is_file($configFile) ? require $configFile : [];

$check(($config['route']['enabled'] ?? null) === true, '默认应启用内置路由');
$check(($config['route']['prefix'] ?? '') === 'store', '默认路由前缀应为 store');
$check(($config['route']['middleware'] ?? null) === [], '默认路由中间件应为空数组');
$check(
    ($config['route']['rules'] ?? []) === [
        'index'  => 'think-store-codeditor/index',
        'read'   => 'think-store-codeditor/read',
        'update' => 'think-store-codeditor/update',
        'create' => 'think-store-codeditor/create',
    ],
    '默认路由规则应为 think-store-codeditor/index、read、update、create'
);
$check(($config['route']['assets'] ?? '') === 'assets', '默认静态资源规则前缀应为 assets');

$check(($config['database']['connection'] ?? null) === '', '默认应使用主项目默认数据库连接');
$check(($config['database']['table'] ?? '') === 'template', '默认模板数据表应为 template');
$check(($config['database']['id_field'] ?? '') === 'id', '默认模板主键字段应为 id');
$check(($config['database']['content_field'] ?? '') === 'newstext', '默认模板内容字段应为 newstext');
$check(($config['database']['list_limit'] ?? 0) === 200, '默认列表上限应为 200');
$check(($config['database']['excerpt_length'] ?? 0) === 60, '默认摘要长度应为 60');

$check(($config['file']['enabled'] ?? null) === true, '默认应允许新增模板时创建文件');
$check(($config['file']['root'] ?? '') === 'template', '默认可编辑根目录应为 template');
$check(($config['file']['allow_dirs'] ?? []) === ['default'], '默认可编辑目录应为 default');
$check(($config['file']['ext'] ?? []) === ['html', 'htm', 'php', 'tpl'], '默认扩展名白名单应包含 html、htm、php、tpl');
$check(($config['file']['overwrite'] ?? null) === false, '默认不覆盖已存在的模板文件');
$check(($config['file']['create_dir'] ?? null) === true, '默认应自动创建模板目录');

$routeSource = (string) file_get_contents($packageRoot . '/route/store_codeditor.php');
$check(str_contains($routeSource, "Route::get(\$index"), '路由应注册 GET 页面');
$check(str_contains($routeSource, "Route::get(\$read"), '路由应注册 GET 读取接口');
$check(str_contains($routeSource, "Route::post(\$update"), '路由应注册 POST 更新接口');
$check(str_contains($routeSource, "Route::post(\$create"), '路由应注册 POST 新增接口');
$check(str_contains($routeSource, "Route::get(\$assets . '/<path>'"), '路由应注册 GET 静态资源');
$check(str_contains($routeSource, 'AssetController'), '资源路由应使用 AssetController');
$check(str_contains($routeSource, "pattern(['path' => '.*'])"), '资源路由应匹配多级路径');
$check(str_contains($routeSource, 'middleware($middleware)'), '路由应应用中间件配置');
$check(str_contains($routeSource, 'Route::group($prefix, $register)'), '路由应统一按前缀分组注册');
$check(!str_contains($routeSource, "if (\$prefix === '')"), '空前缀时也应走分组并应用中间件');
$check(str_contains($routeSource, 'CodeditorController::class'), '路由应指向 CodeditorController');
$check(str_contains($routeSource, "config('store_codeditor.route'"), '路由应读取 store_codeditor.route 配置');

$serviceSource = (string) file_get_contents($packageRoot . '/src/Service.php');
$check(str_contains($serviceSource, 'loadRoutesFrom'), '服务应在 boot 阶段加载路由');
$check(str_contains($serviceSource, 'mergeConfig'), '服务应合并包内默认配置');
$check(str_contains($serviceSource, 'array_replace_recursive'), '服务应深度合并配置');
$check(str_contains($serviceSource, "bind('codeditor.templates'"), '服务应绑定 Templates');
$check(str_contains($serviceSource, "bind('codeditor.path_guard'"), '服务应绑定 PathGuard');
$check(str_contains($serviceSource, 'instance(View::class'), '服务应注册包内视图渲染器');
$check(str_contains($serviceSource, 'getRootPath'), '服务应使用主项目根目录');

$controllerSource = (string) file_get_contents($packageRoot . '/src/CodeditorController.php');
$check(str_contains($controllerSource, 'function index'), '控制器应实现页面');
$check(str_contains($controllerSource, 'function read'), '控制器应实现读取接口');
$check(str_contains($controllerSource, 'function update'), '控制器应实现更新接口');
$check(str_contains($controllerSource, 'function create'), '控制器应实现新增接口');
$check(str_contains($controllerSource, 'View $view'), '控制器应注入包内渲染器');
$check(str_contains($controllerSource, '$this->view->render'), '控制器应通过渲染器输出页面');
$check(str_contains($controllerSource, 'function endpoints'), '控制器应生成接口地址');
$check(str_contains($controllerSource, 'resolveForWrite'), '控制器新增接口应校验模板文件路径');
$check(str_contains($controllerSource, 'store_codeditor.file.enabled'), '控制器应读取 file.enabled 配置');
$check(str_contains($controllerSource, 'InstalledVersions'), '控制器应从 Composer 运行时读取版本');
$check(str_contains($controllerSource, "'dev'"), '控制器应提供开发版本回退');

$viewSource = (string) file_get_contents($packageRoot . '/src/View.php');
$check(str_contains($viewSource, 'use think\\view\\driver\\Think'), '渲染器应使用 think-view 模板驱动');
$check(str_contains($viewSource, "'view_path'"), '渲染器应指定模板目录');
$check(str_contains($viewSource, '/theme/view/'), '渲染器应指向包内 theme/view 目录');
$check(str_contains($viewSource, 'cache_path'), '渲染器应配置模板缓存目录');
$check(str_contains($viewSource, 'getRuntimePath'), '渲染器应使用主项目运行时目录');
$check(str_contains($viewSource, 'cache_prefix'), '渲染器应使用独立的模板缓存前缀');
$check(str_contains($viewSource, 'ob_start'), '渲染器应捕获模板输出');
$check(str_contains($viewSource, 'templateFile'), '渲染器应解析完整模板路径');
$check(str_contains($viewSource, 'PATHINFO_EXTENSION'), '渲染器应补全模板后缀');

$templatesSource = (string) file_get_contents($packageRoot . '/src/Templates.php');
$check(str_contains($templatesSource, 'new Template('), 'Templates 应使用 Template 模型查询');
$check(!str_contains($templatesSource, 'Db::'), 'Templates 不应使用 Db 原生查询');
$check(str_contains($templatesSource, 'getAttr'), 'Templates 应通过模型读取字段');
$check(str_contains($templatesSource, 'setAttr'), 'Templates 应通过模型写入字段');

$pageSource = (string) file_get_contents($packageRoot . '/theme/view/index.html');
$check(str_contains($pageSource, 'data-api="{$api}"'), '编辑器页面应使用视图变量声明接口前缀');
$check(str_contains($pageSource, '{$api}/assets/layui/css/layui.css'), '编辑器页面应引用 assets 路由的 layui 样式');
$check(str_contains($pageSource, '{$api}/assets/layui/layui.js'), '编辑器页面应引用 assets 路由的 layui 脚本');
$check(str_contains($pageSource, '{$api}/assets/ace/ace.js'), '编辑器页面应引用 assets 路由的 Ace 脚本');
$check(str_contains($pageSource, '{$api}/assets/js/codeditor.js'), '编辑器页面应引用页面脚本');
$check(str_contains($pageSource, 'window.CODEDITOR_CONFIG'), '编辑器页面应注入接口配置');
$check(str_contains($pageSource, '{$endpoints.read}'), '编辑器页面应使用读取接口地址');
$check(str_contains($pageSource, '{$endpoints.update}'), '编辑器页面应使用更新接口地址');
$check(str_contains($pageSource, '{$endpoints.create}'), '编辑器页面应使用新增接口地址');
$check(str_contains($pageSource, 'id="tpl-table"'), '编辑器页面应包含 layui 数据表格容器');
$check(str_contains($pageSource, 'lay-filter="tpl-table"'), '编辑器页面应配置 layui 表格过滤器');
$check(str_contains($pageSource, '{$version}'), '编辑器页面应输出版本号');
$check(!str_contains($pageSource, 'cdn.'), '编辑器页面不应引用 CDN 资源');
$check(!str_contains($pageSource, 'unpkg'), '编辑器页面不应引用 unpkg 资源');
$check(!str_contains($pageSource, 'project/'), '编辑器页面不应再引用 project 目录');

$scriptSource = (string) file_get_contents($packageRoot . '/theme/assets/js/codeditor.js');
$check(str_contains($scriptSource, "layui.use(['table', 'layer', 'form']"), '页面脚本应加载 layui table、layer 与 form');
$check(str_contains($scriptSource, 'table.render'), '页面脚本应渲染 layui 数据表格');
$check(str_contains($scriptSource, "table.on('row(tpl-table)'"), '页面脚本应处理表格行点击');
$check(str_contains($scriptSource, 'fetch('), '页面脚本应使用 fetch 读取数据');
$check(str_contains($scriptSource, 'ace/mode/php'), '页面脚本应启用 ace/mode/php 混合高亮');
$check(str_contains($scriptSource, 'aceBase'), '页面脚本应使用 assets 路由的 Ace 资源');
$check(!str_contains($scriptSource, "'/codeditor'"), '页面脚本不应写死旧接口前缀');
$check(!str_contains($scriptSource, 'cdn.'), '页面脚本不应引用 CDN 资源');

$styleSource = (string) file_get_contents($packageRoot . '/theme/assets/css/index.css');
$check(str_contains($styleSource, '--codeditor-brand'), '样式应定义主题变量');
$check(str_contains($styleSource, '.codeditor-table'), '样式应包含 layui 表格区域');
$check(str_contains($styleSource, '@media (max-width: 820px)'), '样式应包含响应式断点');
$check(str_contains($styleSource, '@media (max-width: 640px)'), '样式应包含移动端布局');
$check(!str_contains($styleSource, 'cdn'), '样式不应依赖 CDN 资源');

if (!class_exists(\Youvision\ThinkStoreCodeditor\AssetController::class)) {
    require_once $packageRoot . '/src/AssetController.php';
}

$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('layui/css/layui.css') !== null, '应能解析包内静态资源');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('ace/ace.js') !== null, '应能解析 Ace 资源');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('../composer.json') === null, '应拒绝目录穿越');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('..') === null, '应拒绝上级目录');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('') === null, '空路径应返回 null');
$check(\Youvision\ThinkStoreCodeditor\AssetController::resolve('missing.css') === null, '不存在的资源应返回 null');
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
    'ext'        => ['html', 'htm', 'php', 'tpl'],
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
$check(
    str_contains((string) file_get_contents($target), '<?php echo "ok"; ?>'),
    '写入的模板文件应保留 HTML + PHP 混合内容'
);
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
$check(
    $overwriteGuard->resolveForWrite('default/index.html') === $target,
    'overwrite=true 时应允许解析已存在的模板文件'
);

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

$remove($tmp);

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