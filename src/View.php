<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\App;
use think\view\driver\Think;

/**
 * 包内视图渲染器。
 *
 * 模板固定使用 theme/view 目录，不依赖主项目的 view_path 配置；
 * think-view 的 fetch() 直接输出内容，因此用输出缓冲捕获并返回字符串。
 */
class View
{
    protected string $path;

    protected ?Think $driver = null;

    public function __construct(protected App $app)
    {
        $this->path = dirname(__DIR__) . '/theme/view/';
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