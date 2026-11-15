<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

/**
 * 编辑器包元数据与可见地址。
 *
 * 页面、接口与静态资源地址都由这里推导，修改 route.prefix、route.segment
 * 或 assets 后模板与页面脚本会跟随变化，避免在模板中散落硬编码路径。
 */
class StoreCodeditor
{
    public const DEFAULT_SEGMENT = 'think-store-codeditor';

    protected string $packageRoot;

    public function __construct(protected array $config = [], string $packageRoot = '')
    {
        $this->packageRoot = rtrim(
            str_replace('\\', '/', $packageRoot !== '' ? $packageRoot : dirname(__DIR__)),
            '/'
        );
    }

    /**
     * 页面根地址，例如 /store/think-store-codeditor。
     */
    public function base(): string
    {
        $prefix  = trim((string) ($this->config['route']['prefix'] ?? 'store'), '/');
        $segment = trim((string) ($this->config['route']['segment'] ?? self::DEFAULT_SEGMENT), '/');

        return ($prefix === '' ? '' : '/' . $prefix) . ($segment === '' ? '' : '/' . $segment);
    }

    /**
     * 项目介绍页地址，例如 /store/think-store-codeditor/index。
     */
    public function index(): string
    {
        return $this->base() . '/index';
    }

    /**
     * 模板代码编辑器地址，例如 /store/think-store-codeditor/template。
     */
    public function template(): string
    {
        return $this->base() . '/template';
    }

    /**
     * 分类管理页面地址，例如 /store/think-store-codeditor/category。
     */
    public function category(): string
    {
        return $this->base() . '/category';
    }

    /**
     * 内嵌页面示例地址，例如 /store/think-store-codeditor/iframe。
     */
    public function iframe(): string
    {
        return $this->base() . '/iframe';
    }

    /**
     * iframe 模板简易管理地址，例如 /store/think-store-codeditor/iframe/template。
     */
    public function iframeTemplate(): string
    {
        return $this->iframe() . '/template';
    }

    /**
     * iframe 分类简易管理地址，例如 /store/think-store-codeditor/iframe/category。
     */
    public function iframeCategory(): string
    {
        return $this->iframe() . '/category';
    }

    /**
     * 包内静态资源地址，例如 /store/think-store-codeditor/assets。
     *
     * assets 为相对路径时拼在包内命名空间后；以 / 开头时视为外部托管的绝对地址。
     */
    public function assets(): string
    {
        $raw = trim((string) ($this->config['assets'] ?? 'assets'));

        if ($raw === '') {
            $raw = 'assets';
        }

        if (str_starts_with($raw, '/')) {
            return rtrim($raw, '/');
        }

        return $this->base() . '/' . trim($raw, '/');
    }

    /**
     * 模板接口根地址或指定动作地址。
     */
    public function apiTemplate(?string $action = null): string
    {
        return $this->api('template', $action);
    }

    /**
     * 分类接口根地址或指定动作地址。
     */
    public function apiCategory(?string $action = null): string
    {
        return $this->api('category', $action);
    }

    /**
     * 当前包版本，优先读取 Composer 安装信息，回退到包内 composer.json。
     */
    public function version(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion('youvision/think-store-codeditor');

                if (is_string($version) && $version !== '') {
                    return $version;
                }
            } catch (\Throwable) {
            }
        }

        $file = $this->packageRoot . '/composer.json';

        if (!is_file($file)) {
            return 'dev';
        }

        $json    = json_decode((string) file_get_contents($file), true);
        $version = is_array($json) ? (string) ($json['version'] ?? '') : '';

        return $version !== '' ? $version : 'dev';
    }

    /**
     * 页面、接口与资源路由清单。
     *
     * @return array<int, array<string, string>>
     */
    public function routes(): array
    {
        return [
            ['method' => 'GET', 'path' => $this->index(), 'name' => '项目介绍', 'type' => '页面'],
            ['method' => 'GET', 'path' => $this->template(), 'name' => '模板编辑器', 'type' => '页面'],
            ['method' => 'GET', 'path' => $this->category(), 'name' => '分类管理', 'type' => '页面'],
            ['method' => 'GET', 'path' => $this->iframe(), 'name' => '内嵌页面示例', 'type' => '页面'],
            ['method' => 'GET', 'path' => $this->iframeTemplate(), 'name' => '模板简易管理', 'type' => '页面'],
            ['method' => 'GET', 'path' => $this->iframeCategory(), 'name' => '分类简易管理', 'type' => '页面'],
            ['method' => 'GET', 'path' => $this->apiTemplate('read'), 'name' => '读取模板', 'type' => '接口'],
            ['method' => 'POST', 'path' => $this->apiTemplate('update'), 'name' => '更新模板', 'type' => '接口'],
            ['method' => 'POST', 'path' => $this->apiTemplate('create'), 'name' => '新增模板', 'type' => '接口'],
            ['method' => 'GET', 'path' => $this->apiTemplate('history'), 'name' => '历史列表', 'type' => '接口'],
            ['method' => 'GET', 'path' => $this->apiTemplate('history/detail'), 'name' => '历史详情', 'type' => '接口'],
            ['method' => 'POST', 'path' => $this->apiTemplate('history/restore'), 'name' => '还原历史', 'type' => '接口'],
            ['method' => 'GET', 'path' => $this->apiCategory('read'), 'name' => '读取分类', 'type' => '接口'],
            ['method' => 'POST', 'path' => $this->apiCategory('create'), 'name' => '新增分类', 'type' => '接口'],
            ['method' => 'POST', 'path' => $this->apiCategory('update'), 'name' => '更新分类', 'type' => '接口'],
            ['method' => 'POST', 'path' => $this->apiCategory('delete'), 'name' => '删除分类', 'type' => '接口'],
            ['method' => 'GET', 'path' => $this->assets() . '/<path>', 'name' => '包内静态资源', 'type' => '资源'],
        ];
    }

    /**
     * 包元数据，供外部展示。
     *
     * @return array<int, array<string, string>>
     */
    public function meta(): array
    {
        return [
            ['label' => '包名', 'value' => 'youvision/think-store-codeditor'],
            ['label' => '版本', 'value' => 'v' . $this->version()],
            ['label' => '类型', 'value' => 'library'],
            ['label' => 'PHP', 'value' => '>=8.0'],
            ['label' => 'ThinkPHP', 'value' => '^8.0'],
            ['label' => '视图驱动', 'value' => 'topthink/think-view ^2.0'],
            ['label' => '静态资源', 'value' => '包内 theme/assets 路由输出'],
        ];
    }

    protected function api(string $group, ?string $action): string
    {
        $path = $this->base() . '/api/' . trim($group, '/');

        return $action === null || $action === '' ? $path : $path . '/' . trim($action, '/');
    }
}