<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\Service as ThinkService;

class Service extends ThinkService
{
    public function register(): void
    {
        $this->mergeConfig();

        $this->app->bind('codeditor.templates', function () {
            return new Templates((array) $this->app->config->get('codeditor', []));
        });

        $this->app->bind(Templates::class, 'codeditor.templates');

        $this->app->bind('codeditor.path_guard', function () {
            return new PathGuard(
                (array) $this->app->config->get('codeditor.file', []),
                (string) $this->app->getRootPath()
            );
        });

        $this->app->bind(PathGuard::class, 'codeditor.path_guard');
    }

    public function boot(): void
    {
        if ((bool) $this->app->config->get('codeditor.route.enabled', true)) {
            $this->loadRoutesFrom(dirname(__DIR__) . '/route/codeditor.php');
        }
    }

    /**
     * 合并包内默认配置与主项目配置，主项目配置优先。
     *
     * 列表型配置（可编辑目录、扩展名）整体覆盖，避免默认值与自定义值混用。
     */
    protected function mergeConfig(): void
    {
        $default = require dirname(__DIR__) . '/config/codeditor.php';
        $current = (array) $this->app->config->get('codeditor', []);
        $merged  = array_replace_recursive($default, $current);

        foreach ([['file', 'allow_dirs'], ['file', 'ext']] as [$section, $key]) {
            if (array_key_exists($key, (array) ($current[$section] ?? []))) {
                $merged[$section][$key] = array_values((array) $current[$section][$key]);
            }
        }

        $this->app->config->set($merged, 'codeditor');
    }
}