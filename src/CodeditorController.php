<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;
use RuntimeException;
use think\App;
use think\facade\Db;
use think\Request;
use think\response\Json;
use Throwable;

class CodeditorController
{
    public function __construct(
        protected StoreCodeditor $codeditor,
        protected Templates $templates,
        protected TemplateHistories $histories,
        protected PathGuard $paths,
        protected View $view,
        protected App $app
    ) {
    }

    /**
     * 渲染项目介绍页。
     */
    public function index(): string
    {
        return $this->view->render('index', [
            'base'        => $this->codeditor->base(),
            'home'        => $this->codeditor->index(),
            'template'    => $this->codeditor->template(),
            'category'    => $this->codeditor->category(),
            'assets'      => $this->codeditor->assets(),
            'templateApi' => $this->codeditor->apiTemplate(),
            'categoryApi' => $this->codeditor->apiCategory(),
            'routes'      => $this->codeditor->routes(),
            'meta'        => $this->codeditor->meta(),
            'version'     => $this->codeditor->version(),
            'active'      => 'intro',
            'title'       => 'Think Store Codeditor',
            'description' => 'ThinkPHP 8 模板代码编辑器：包内视图、静态资源路由、模板与分类管理、编辑历史。',
        ]);
    }

    /**
     * 渲染模板代码编辑器页面。
     */
    public function template(): string
    {
        return $this->view->render('template', [
            'base'           => $this->codeditor->base(),
            'home'           => $this->codeditor->index(),
            'template'       => $this->codeditor->template(),
            'category'       => $this->codeditor->category(),
            'assets'         => $this->codeditor->assets(),
            'templateApi'    => $this->codeditor->apiTemplate(),
            'historyApi'     => $this->codeditor->apiTemplate('history'),
            'historyDetail'  => $this->codeditor->apiTemplate('history/detail'),
            'historyRestore' => $this->codeditor->apiTemplate('history/restore'),
            'version'        => $this->codeditor->version(),
            'active'         => 'template',
            'title'          => '模板代码编辑器',
            'description'    => '在浏览器中编辑 ThinkPHP 模板，支持 HTML 与 PHP 混合高亮、保存、新增与历史还原。',
        ]);
    }

    /**
     * 读取接口：带 id 返回单条模板，不带 id 返回模板列表。
     */
    public function read(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $id = $request->param('id', null);

            if ($id === null || $id === '') {
                return $this->ok($this->templates->lists());
            }

            $template = $this->templates->find($this->validateId($id));

            if ($template === null) {
                return $this->fail('模板不存在', 404);
            }

            return $this->ok($template);
        });
    }

    /**
     * 更新接口：按 id 更新模板内容。
     */
    public function update(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $id      = $this->validateId($request->param('id', null));
            $content = $this->validateContent($request->param('content', null));
            $intro   = $this->validateIntro($request->param('intro', ''), '更新模板');

            $old = $this->templates->find($id);

            if ($old === null) {
                return $this->fail('模板不存在', 404);
            }

            if ((string) ($old['content'] ?? '') === $content) {
                return $this->ok(['id' => $id, 'changed' => false]);
            }

            Db::transaction(function () use ($id, $content, $old, $intro): void {
                $this->histories->record(
                    $id,
                    (string) ($old['title'] ?? ''),
                    (string) ($old['content'] ?? ''),
                    $intro
                );

                if (!$this->templates->update($id, $content)) {
                    throw new RuntimeException('模板更新失败');
                }
            });

            return $this->ok(['id' => $id, 'changed' => true]);
        });
    }

    /**
     * 新增接口：新增模板记录，可选在受限目录内创建模板文件。
     */
    public function create(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $content = $this->validateContent($request->param('content', null));
            $intro   = $this->validateIntro($request->param('intro', ''), '创建模板');
            $file    = $request->param('file', '');
            $target  = null;

            if ($file !== null && !is_string($file)) {
                throw new InvalidArgumentException('模板文件参数 file 必须是字符串');
            }

            $file = trim((string) ($file ?? ''));

            if ($file !== '') {
                if (!(bool) $this->app->config->get('store_codeditor.file.enabled', true)) {
                    throw new InvalidArgumentException('未开启模板文件创建（store_codeditor.file.enabled）');
                }

                $target = $this->paths->resolveForWrite($file);
            }

            $recordOnCreate = (bool) $this->app->config->get('store_codeditor.history.record_on_create', true);

            $id = Db::transaction(function () use ($content, $intro, $recordOnCreate): int {
                $id = $this->templates->create($content);

                if ($recordOnCreate) {
                    $this->histories->record($id, '', $content, $intro);
                }

                return $id;
            });

            if ($target !== null) {
                try {
                    $this->paths->put($target, $content);
                } catch (Throwable $e) {
                    $this->templates->delete($id);
                    $this->histories->deleteByTemplate($id);

                    throw $e;
                }
            }

            return $this->ok([
                'id'   => $id,
                'file' => $target === null ? null : $this->paths->relative($target),
            ]);
        });
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
            throw new InvalidArgumentException('模板 id 必须是正整数');
        }

        return (int) $value;
    }

    protected function validateIntro(mixed $value, string $default): string
    {
        if ($value === null) {
            return $default;
        }

        if (!is_string($value)) {
            throw new InvalidArgumentException('修改说明必须是字符串');
        }

        $value = trim($value);

        if ($value === '') {
            return $default;
        }

        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        if ($length > 255) {
            throw new InvalidArgumentException('修改说明不能超过 255 个字符');
        }

        return $value;
    }

    protected function validateContent(mixed $content): string
    {
        if ($content === null) {
            throw new InvalidArgumentException('缺少模板内容参数 content');
        }

        if (!is_string($content)) {
            throw new InvalidArgumentException('模板内容参数 content 必须是字符串');
        }

        return $content;
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