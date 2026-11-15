<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\App;
use think\view\driver\Think;

/**
 * 包内视图渲染器。
 *
 * view 配置为空时使用包内 theme/view；不为空时直接作为模板目录，
 * 相对路径从项目根目录算起，例如 view/think-store-codeditor。
 * think-view 的 fetch() 直接输出内容，因此用输出缓冲捕获并返回字符串。
 */
class View
{
    protected string $path;

    protected ?Think $driver = null;

    public function __construct(protected App $app, array $config = [])
    {
        $this->path = $this->resolvePath((string) ($config['view'] ?? ''));
    }

    /**
     * 渲染模板并返回 HTML。
     *
     * @param array<string, mixed> $vars
     */
    public function render(string $template, array $vars = []): string
    {
        $level = ob_get_level();

        ob_start();

        try {
            $this->driver()->fetch($this->templateFile($template), $vars);
        } catch (\Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $e;
        }

        return (string) ob_get_clean();
    }

    /**
     * 解析模板文件，避免 think-view 按控制器名推导路径。
     */
    protected function templateFile(string $template): string
    {
        $file = $this->path . ltrim($template, '/\\');

        if (pathinfo($file, PATHINFO_EXTENSION) === '') {
            $file .= '.html';
        }

        return $file;
    }

    /**
     * 解析模板目录。
     *
     * view 留空：包内 theme/view。
     * view 非空：该值即模板目录，相对路径从项目根目录算起（主项目
     * view 与 app 同级），例如 view/think-store-codeditor。
     */
    protected function resolvePath(string $view): string
    {
        $view = rtrim(trim($view), '/\\');

        if ($view === '') {
            return dirname(__DIR__) . '/theme/view/';
        }

        $absolute = str_starts_with($view, '/') || preg_match('/^[A-Za-z]:[\/\\\\]/', $view) === 1;
        $root     = $absolute ? '' : $this->app->getRootPath();

        return rtrim($root . $view, '/\\') . '/';
    }

    protected function driver(): Think
    {
        return $this->driver ??= new Think($this->app, [
            'view_path'    => $this->path,
            'view_suffix'  => 'html',
            'cache_path'   => $this->app->getRuntimePath() . 'temp' . DIRECTORY_SEPARATOR,
            'cache_prefix' => 'codeditor_',
        ]);
    }
}