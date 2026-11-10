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
    $check(($composer['require']['youvision/think-static'] ?? '') === '^1.0', 'composer.json 应依赖 youvision/think-static ^1.0');
    $check(
        ($composer['autoload']['psr-4']['Youvision\\ThinkStoreCodeditor\\'] ?? '') === 'src/',
        'composer.json 应配置 Youvision\\ThinkStoreCodeditor 的 PSR-4 自动加载'
    );
    $check(
        in_array('Youvision\\ThinkStoreCodeditor\\Service', (array) ($composer['extra']['think']['services'] ?? []), true),
        'composer.json 应注册 ThinkPHP 服务'
    );
    $check(
        ($composer['extra']['think']['config']['codeditor'] ?? '') === 'config/codeditor.php',
        'composer.json 应声明 codeditor 配置'
    );
    $check(
        str_contains((string) ($composer['scripts']['test'] ?? ''), 'tests/verify.php'),
        'composer.json 的 test 脚本应执行 tests/verify.php'
    );
}

$requiredFiles = [
    'config/codeditor.php',
    'route/codeditor.php',
    'src/PathGuard.php',
    'src/Service.php',
    'src/Template.php',
    'src/TemplateController.php',
    'src/Templates.php',
    'project/project.json',
    'project/index.html',
    'project/readme.md',
    'project/theme/css/codeditor.css',
    'project/theme/ace/ace.js',
    'project/theme/ace/mode-php.js',
    'project/theme/ace/theme-monokai.js',
    'project/theme/ace/worker-php.js',
    'project/theme/layui/layui.js',
    'project/theme/layui/css/layui.css',
    'project/view/index.html',
    'README.md',
    'LICENSE',
];

foreach ($requiredFiles as $file) {
    $check(is_file($packageRoot . '/' . $file), $file . ' 应存在');
}

$configFile = $packageRoot . '/config/codeditor.php';
$config     = is_file($configFile) ? require $configFile : [];

$check(($config['route']['enabled'] ?? null) === true, '默认应启用内置路由');
$check(($config['route']['prefix'] ?? '') === 'codeditor', '默认路由前缀应为 codeditor');
$check(($config['route']['middleware'] ?? null) === [], '默认路由中间件应为空数组');
$check(($config['route']['rules'] ?? []) === ['read' => 'read', 'update' => 'update', 'create' => 'create'], '默认路由规则应为 read、update、create');

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

$manifestFile = $packageRoot . '/project/project.json';
$manifest     = is_file($manifestFile) ? json_decode((string) file_get_contents($manifestFile), true) : null;

$check(is_array($manifest), 'project/project.json 应为有效 JSON');
$check(($manifest['path'] ?? '') === 'store-codeditor', 'project.json 的 path 应为 store-codeditor');
$check(
    ($manifest['map'] ?? []) === [
        'theme'      => 'theme',
        'view'       => 'view',
        'index.html' => 'index.html',
        'readme.md'  => 'readme.md',
    ],
    'project.json 的 map 应声明 theme、view、index.html、readme.md'
);
$check(($manifest['exclude'] ?? []) === ['**/*.map'], 'project.json 应排除 **/*.map');

$routeSource = (string) file_get_contents($packageRoot . '/route/codeditor.php');
$check(str_contains($routeSource, 'Route::get($read'), '路由应注册 GET 读取接口');
$check(str_contains($routeSource, 'Route::post($update'), '路由应注册 POST 更新接口');
$check(str_contains($routeSource, 'Route::post($create'), '路由应注册 POST 新增接口');
$check(str_contains($routeSource, 'TemplateController::class'), '路由应指向 TemplateController');
$check(str_contains($routeSource, 'config(\'codeditor.route\''), '路由应读取 codeditor.route 配置');

$serviceSource = (string) file_get_contents($packageRoot . '/src/Service.php');
$check(str_contains($serviceSource, 'loadRoutesFrom'), '服务应在 boot 阶段加载路由');
$check(str_contains($serviceSource, 'mergeConfig'), '服务应合并包内默认配置');
$check(str_contains($serviceSource, 'array_replace_recursive'), '服务应深度合并配置');
$check(str_contains($serviceSource, 'Templates::class'), '服务应绑定 Templates');
$check(str_contains($serviceSource, 'PathGuard::class'), '服务应绑定 PathGuard');

$controllerSource = (string) file_get_contents($packageRoot . '/src/TemplateController.php');
$check(str_contains($controllerSource, 'function read'), '控制器应实现读取接口');
$check(str_contains($controllerSource, 'function update'), '控制器应实现更新接口');
$check(str_contains($controllerSource, 'function create'), '控制器应实现新增接口');
$check(str_contains($controllerSource, 'resolveForWrite'), '控制器新增接口应校验模板文件路径');
$check(str_contains($controllerSource, 'codeditor.file.enabled'), '控制器应读取 file.enabled 配置');

$templatesSource = (string) file_get_contents($packageRoot . '/src/Templates.php');
$check(str_contains($templatesSource, 'new Template('), 'Templates 应使用 Template 模型查询');
$check(!str_contains($templatesSource, 'Db::'), 'Templates 不应使用 Db 原生查询');
$check(str_contains($templatesSource, 'getAttr'), 'Templates 应通过模型读取字段');
$check(str_contains($templatesSource, 'setAttr'), 'Templates 应通过模型写入字段');

$pageSource = (string) file_get_contents($packageRoot . '/project/index.html');
$check(str_contains($pageSource, 'theme/layui/css/layui.css'), '编辑器页面应引用本地 layui 样式');
$check(str_contains($pageSource, 'theme/layui/layui.js'), '编辑器页面应引用本地 layui 脚本');
$check(str_contains($pageSource, 'theme/ace/ace.js'), '编辑器页面应引用本地 Ace 脚本');
$check(str_contains($pageSource, 'ace/mode/php'), '编辑器应启用 ace/mode/php 混合高亮');
$check(str_contains($pageSource, 'data-api="/codeditor"'), '编辑器页面应声明默认接口前缀');
$check(str_contains($pageSource, "request('/read'"), '编辑器页面应调用读取接口');
$check(str_contains($pageSource, "request('/update'"), '编辑器页面应调用更新接口');
$check(str_contains($pageSource, "request('/create'"), '编辑器页面应调用新增接口');
$check(!str_contains($pageSource, 'cdn.'), '编辑器页面不应引用 CDN 资源');
$check(!str_contains($pageSource, 'unpkg'), '编辑器页面不应引用 unpkg 资源');

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