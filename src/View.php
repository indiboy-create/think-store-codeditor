<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\App;
use think\view\driver\Think;

/**
 * 包内视图渲染器。
 *
 * 模板目录由 view 配置指定，默认包内 theme/view；也可使用绝对路径。
 * think-view 的 fetch() 直接输出内容，因此用输出缓冲捕获并返回字符串。
 */
class View
{
    protected string $path;

    protected ?Think $driver = null;

    public function __construct(protected App $app, array $config = [])
    {
        $this->path = $this->resolvePath((string) ($config['view'] ?? 'view'));
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
     * 解析视图目录：相对路径拼接包内 theme，绝对路径原样使用。
     */
    protected function resolvePath(string $view): string
    {
        $view = trim(str_replace('\\', '/', $view));

        if ($view === '') {
            $view = 'view';
        }

        if (str_starts_with($view, '/') || preg_match('#^[A-Za-z]:/#', $view)) {
            return rtrim($view, '/') . '/';
        }

        return dirname(__DIR__) . '/theme/' . trim($view, '/') . '/';
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