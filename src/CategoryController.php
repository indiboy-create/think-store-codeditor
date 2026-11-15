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
        protected StoreCodeditor $codeditor,
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
            'base'        => $this->codeditor->base(),
            'home'        => $this->codeditor->index(),
            'template'    => $this->codeditor->template(),
            'category'    => $this->codeditor->category(),
            'assets'      => $this->codeditor->assets(),
            'templateApi' => $this->codeditor->apiTemplate(),
            'categoryApi' => $this->codeditor->apiCategory(),
            'version'     => $this->codeditor->version(),
            'active'      => 'category',
            'title'       => '模板分类管理',
            'description' => '维护模板分类，支持新增、编辑、删除与排序。',
        ]);
    }

    /**
     * 渲染 iframe 版分类管理页，复用分类读取、新增、更新与删除接口。
     */
    public function iframe(): string
    {
        return $this->view->render('iframe/category', [
            'base'        => $this->codeditor->base(),
            'home'        => $this->codeditor->index(),
            'template'    => $this->codeditor->template(),
            'category'    => $this->codeditor->category(),
            'assets'      => $this->codeditor->assets(),

            'categoryApi' => $this->codeditor->apiCategory(),
            'version'     => $this->codeditor->version(),
            'title'       => '模板分类管理',
            'description' => 'iframe 版分类管理：树形表格展示分类，支持新增、编辑与删除。',
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