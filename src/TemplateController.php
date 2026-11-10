<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;
use RuntimeException;
use think\App;
use think\Request;
use think\response\Json;
use Throwable;

class TemplateController
{
    public function __construct(
        protected Templates $templates,
        protected PathGuard $paths,
        protected App $app
    ) {
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

            if (!$this->templates->update($id, $content)) {
                return $this->fail('模板不存在', 404);
            }

            return $this->ok(['id' => $id]);
        });
    }

    /**
     * 新增接口：新增模板记录，可选在受限目录内创建模板文件。
     */
    public function create(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $content = $this->validateContent($request->param('content', null));
            $file    = $request->param('file', '');
            $target  = null;

            if ($file !== null && !is_string($file)) {
                throw new InvalidArgumentException('模板文件参数 file 必须是字符串');
            }

            $file = trim((string) ($file ?? ''));

            if ($file !== '') {
                if (!(bool) $this->app->config->get('codeditor.file.enabled', true)) {
                    throw new InvalidArgumentException('未开启模板文件创建（codeditor.file.enabled）');
                }

                $target = $this->paths->resolveForWrite($file);
            }

            $id = $this->templates->create($content);

            if ($target !== null) {
                try {
                    $this->paths->put($target, $content);
                } catch (Throwable $e) {
                    $this->templates->delete($id);

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