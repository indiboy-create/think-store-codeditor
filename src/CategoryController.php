<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;
use RuntimeException;
use think\App;
use think\Request;
use think\response\Json;
use Throwable;

class CategoryController
{
    public function __construct(
        protected Categories $categories,
        protected View $view,
        protected App $app
    ) {
    }

    /**
     * 渲染模板分类管理页面。
     */
    public function index(): string
    {
        return $this->view->render('category', [
            'api'       => $this->apiPrefix(),
            'endpoints' => $this->endpoints(),
            'editorUrl' => $this->ruleUrl('index', 'think-store-codeditor/index'),
            'version'   => $this->version(),
        ]);
    }

    /**
     * 读取接口：带 id 返回单条分类，不带 id 返回分类列表。
     */
    public function read(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $id = $request->param('id', null);

            if ($id === null || $id === '') {
                $items = $this->categories->lists();

                return $this->ok([
                    'total' => count($items),
                    'items' => $items,
                ]);
            }

            $category = $this->categories->find($this->validateId($id));

            if ($category === null) {
                return $this->fail('分类不存在', 404);
            }

            return $this->ok($category);
        });
    }

    /**
     * 新增分类。
     */
    public function create(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $data = $this->validatePayload($request, true);

            return $this->ok(['id' => $this->categories->create($data)]);
        });
    }

    /**
     * 更新分类。
     */
    public function update(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $id   = $this->validateId($request->param('id', null));
            $data = $this->validatePayload($request, false);

            if (!$this->categories->update($id, $data)) {
                return $this->fail('分类不存在', 404);
            }

            return $this->ok(['id' => $id]);
        });
    }

    /**
     * 删除分类。
     */
    public function delete(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $id = $this->validateId($request->param('id', null));

            if (!$this->categories->delete($id)) {
                return $this->fail('分类不存在', 404);
            }

            return $this->ok(['id' => $id]);
        });
    }

    /**
     * 校验并过滤分类字段，新增时要求标题或名称至少填写一个。
     *
     * @return array<string, mixed>
     */
    protected function validatePayload(Request $request, bool $creating): array
    {
        $input = (array) $request->only([
            'title',
            'titlepic',
            'name',
            'sort',
            'status',
            'intro',
            'is_last',
            'admin_id',
            'site_id',
        ]);

        if ($creating) {
            $title = trim((string) ($input['title'] ?? ''));
            $name  = trim((string) ($input['name'] ?? ''));

            if ($title === '' && $name === '') {
                throw new InvalidArgumentException('分类标题和分类名称至少填写一个');
            }
        }

        return $this->categories->payload($input);
    }

    /**
     * 路由前缀，留空时不添加前导斜杠。
     */
    protected function apiPrefix(): string
    {
        $prefix = trim((string) config('store_codeditor.route.prefix', 'store'), '/');

        return $prefix === '' ? '' : '/' . $prefix;
    }

    /**
     * 页面使用的接口地址，跟随 route.prefix 与 route.rules 自动变化。
     *
     * @return array{read: string, create: string, update: string, delete: string}
     */
    protected function endpoints(): array
    {
        return [
            'read'   => $this->ruleUrl('category_read', 'think-store-codeditor/category/read'),
            'create' => $this->ruleUrl('category_create', 'think-store-codeditor/category/create'),
            'update' => $this->ruleUrl('category_update', 'think-store-codeditor/category/update'),
            'delete' => $this->ruleUrl('category_delete', 'think-store-codeditor/category/delete'),
        ];
    }

    protected function ruleUrl(string $rule, string $default): string
    {
        $path = trim((string) config('store_codeditor.route.rules.' . $rule, $default), '/');

        return $this->apiPrefix() . '/' . $path;
    }

    protected function version(): string
    {
        if (class_exists(\Composer\InstalledVersions::class)) {
            try {
                $version = \Composer\InstalledVersions::getPrettyVersion('youvision/think-store-codeditor');

                if (is_string($version) && $version !== '') {
                    return $version;
                }
            } catch (Throwable) {
            }
        }

        $file = dirname(__DIR__) . '/composer.json';

        if (!is_file($file)) {
            return 'dev';
        }

        $json    = json_decode((string) file_get_contents($file), true);
        $version = is_array($json) ? (string) ($json['version'] ?? '') : '';

        return $version !== '' ? $version : 'dev';
    }

    protected function handle(callable $callback): Json
    {
        try {
            return $callback();
        } catch (InvalidArgumentException | RuntimeException $e) {
            return $this->fail($e->getMessage(), 422);
        } catch (Throwable $e) {
            return $this->fail(
                (bool) $this->app->isDebug() ? $e->getMessage() : '服务器内部错误',
                500
            );
        }
    }

    protected function validateId(mixed $value): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_numeric($value) || (int) $value <= 0 || (string) (int) $value !== (string) $value) {
            throw new InvalidArgumentException('分类 id 必须是正整数');
        }

        return (int) $value;
    }

    protected function ok(mixed $data): Json
    {
        return json([
            'code' => 0,
            'msg'  => 'ok',
            'data' => $data,
        ]);
    }

    protected function fail(string $message, int $status = 422): Json
    {
        return json([
            'code' => 1,
            'msg'  => $message,
            'data' => null,
        ])->code($status);
    }
}