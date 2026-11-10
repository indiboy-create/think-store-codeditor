<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;
use RuntimeException;

/**
 * 模板文件路径守卫。
 *
 * 只允许在配置的模板根目录、可编辑目录白名单与扩展名白名单内创建文件，
 * 拒绝绝对路径、盘符、目录穿越与符号链接逃逸。
 */
class PathGuard
{
    protected string $root;

    /** @var string[] */
    protected array $allowDirs = [];

    /** @var string[] */
    protected array $extensions = [];

    protected bool $overwrite;

    protected bool $createDir;

    protected string $basePath;

    public function __construct(array $config = [], string $basePath = '')
    {
        $this->root        = trim((string) ($config['root'] ?? 'template'));
        $this->allowDirs   = $this->normalizeDirs((array) ($config['allow_dirs'] ?? []));
        $this->extensions  = $this->normalizeExtensions((array) ($config['ext'] ?? []));
        $this->overwrite   = (bool) ($config['overwrite'] ?? false);
        $this->createDir   = (bool) ($config['create_dir'] ?? true);
        $this->basePath    = $basePath;
    }

    /**
     * 校验文件路径并返回可写入的绝对路径。
     *
     * 会按需创建父目录，但不会写入文件内容。
     */
    public function resolveForWrite(string $file): string
    {
        $relative = $this->normalizeRelative($file);

        if ($this->allowDirs === []) {
            throw new InvalidArgumentException('未配置可编辑目录（codeditor.file.allow_dirs）');
        }

        if (!$this->insideAllowed($relative)) {
            throw new InvalidArgumentException('模板文件必须位于可编辑目录内：' . implode('、', $this->allowDirs));
        }

        $extension = strtolower((string) pathinfo($relative, PATHINFO_EXTENSION));

        if ($extension === '' || !in_array($extension, $this->extensions, true)) {
            throw new InvalidArgumentException('不允许创建该类型文件：' . ($extension === '' ? '无扩展名' : $extension));
        }

        $root   = $this->rootPath();
        $target = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        $parent = dirname($target);

        if (!is_dir($parent)) {
            if (!$this->createDir) {
                throw new RuntimeException('模板目录不存在：' . $parent);
            }

            if (!mkdir($parent, 0755, true) && !is_dir($parent)) {
                throw new RuntimeException('模板目录创建失败：' . $parent);
            }
        }

        $this->assertInsideRoot($root, $target);

        if (file_exists($target) && !$this->overwrite) {
            throw new RuntimeException('模板文件已存在：' . $relative);
        }

        return $target;
    }

    /**
     * 将内容写入已经通过 resolveForWrite 校验的绝对路径。
     */
    public function put(string $target, string $content): void
    {
        $this->assertInsideRoot($this->rootPath(), $target);

        if (file_put_contents($target, $content, LOCK_EX) === false) {
            throw new RuntimeException('模板文件写入失败：' . $this->relative($target));
        }
    }

    /**
     * 转换为相对主项目根目录的路径，便于接口回显。
     */
    public function relative(string $path): string
    {
        $base = rtrim(str_replace('\\', '/', $this->basePath), '/');
        $path = str_replace('\\', '/', $path);

        if ($base !== '' && stripos($path, $base . '/') === 0) {
            return ltrim(substr($path, strlen($base) + 1), '/');
        }

        return $path;
    }

    /**
     * 解析模板根目录为绝对路径。
     */
    protected function rootPath(): string
    {
        if ($this->root === '') {
            throw new InvalidArgumentException('未配置模板根目录（codeditor.file.root）');
        }

        $root = $this->root;

        if (!$this->isAbsolute($root)) {
            $base = rtrim(str_replace('\\', '/', $this->basePath), '/');

            if ($base === '') {
                throw new InvalidArgumentException('无法解析模板根目录，缺少主项目根路径');
            }

            $root = $base . '/' . trim(str_replace('\\', '/', $root), '/');
        }

        $root = rtrim(str_replace('\\', '/', $root), '/');

        if (!is_dir($root)) {
            throw new RuntimeException('模板根目录不存在：' . $root);
        }

        return $root;
    }

    /**
     * 规范化请求传入的相对文件路径。
     */
    protected function normalizeRelative(string $file): string
    {
        $file = str_replace('\\', '/', trim($file));

        if ($file === '') {
            throw new InvalidArgumentException('模板文件路径不能为空');
        }

        if (str_starts_with($file, '/') || preg_match('#^[A-Za-z]:#', $file) === 1) {
            throw new InvalidArgumentException('模板文件路径必须是相对路径');
        }

        $segments = explode('/', $file);

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                throw new InvalidArgumentException('模板文件路径包含非法片段：' . $file);
            }
        }

        return implode('/', $segments);
    }

    protected function insideAllowed(string $relative): bool
    {
        $relative = strtolower($relative);

        foreach ($this->allowDirs as $dir) {
            $dir = strtolower(str_replace('\\', '/', $dir));

            if ($relative !== $dir && str_starts_with($relative, $dir . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 防止符号链接把目标文件带出模板根目录。
     */
    protected function assertInsideRoot(string $root, string $target): void
    {
        $realRoot   = realpath($root);
        $realParent = realpath(dirname($target));

        if ($realRoot === false || $realParent === false) {
            throw new RuntimeException('模板路径解析失败：' . $target);
        }

        $this->assertPathInside($realRoot, $realParent, $target);

        if (is_file($target) || is_link($target)) {
            $realTarget = realpath($target);

            if ($realTarget === false) {
                throw new RuntimeException('模板文件解析失败：' . $target);
            }

            $this->assertPathInside($realRoot, $realTarget, $target);
        }
    }

    protected function assertPathInside(string $realRoot, string $realPath, string $target): void
    {
        $prefix = strtolower(rtrim(str_replace('\\', '/', $realRoot), '/') . '/');
        $path   = strtolower(rtrim(str_replace('\\', '/', $realPath), '/') . '/');

        if (!str_starts_with($path, $prefix)) {
            throw new InvalidArgumentException('模板文件路径超出可编辑目录：' . $target);
        }
    }

    protected function isAbsolute(string $path): bool
    {
        return str_starts_with($path, '/')
            || str_starts_with($path, '\\')
            || preg_match('#^[A-Za-z]:[\\\\/]#', $path) === 1;
    }

    protected function normalizeDirs(array $dirs): array
    {
        $result = [];

        foreach ($dirs as $dir) {
            $dir = trim(str_replace('\\', '/', (string) $dir), '/');

            if ($dir === '' || $dir === '.' || $dir === '..') {
                continue;
            }

            if (str_starts_with($dir, '/') || preg_match('#^[A-Za-z]:#', $dir) === 1) {
                continue;
            }

            if (preg_match('#(^|/)\.\.?(/|$)#', $dir) === 1) {
                continue;
            }

            $result[] = $dir;
        }

        return array_values(array_unique($result));
    }

    protected function normalizeExtensions(array $extensions): array
    {
        $result = [];

        foreach ($extensions as $extension) {
            $extension = strtolower(trim((string) $extension, ". \t\n\r\0\x0B"));

            if ($extension !== '' && preg_match('/^[a-z0-9]+$/', $extension) === 1) {
                $result[] = $extension;
            }
        }

        return array_values(array_unique($result));
    }
}