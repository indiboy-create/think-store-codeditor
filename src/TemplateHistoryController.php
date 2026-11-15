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

/**
 * 模板编辑历史接口。
 *
 * 只提供列表、详情与覆盖 template 三个接口。
 */
class TemplateHistoryController
{
    public function __construct(
        protected Templates $templates,
        protected TemplateHistories $histories,
        protected App $app
    ) {
    }

    /**
     * 历史列表：按 template_id 返回历史元数据，不返回 newstext。
     */
    public function lists(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $this->ensureEnabled();

            $templateId = $this->validateId($request->param('template_id', null), '模板 id');

            if ($this->templates->find($templateId) === null) {
                return $this->fail('模板不存在', 404);
            }

            $items = $this->histories->lists($templateId);

            return $this->ok([
                'total' => count($items),
                'items' => $items,
            ]);
        });
    }

    /**
     * 历史详情：返回包含 newstext 的完整历史记录。
     */
    public function detail(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $this->ensureEnabled();

            $id      = $this->validateId($request->param('id', null), '历史 id');
            $history = $this->histories->find($id);

            if ($history === null) {
                return $this->fail('历史记录不存在', 404);
            }

            $templateId = (int) $request->param('template_id', 0);

            if ($templateId > 0 && (int) $history['template_id'] !== $templateId) {
                return $this->fail('历史记录不属于该模板', 422);
            }

            return $this->ok($history);
        });
    }

    /**
     * 用历史快照覆盖 template 当前内容，并记录覆盖前的内容。
     */
    public function restore(Request $request): Json
    {
        return $this->handle(function () use ($request): Json {
            $this->ensureEnabled();

            $id      = $this->validateId($request->param('id', null), '历史 id');
            $history = $this->histories->find($id);

            if ($history === null) {
                return $this->fail('历史记录不存在', 404);
            }

            $templateId = (int) ($history['template_id'] ?? 0);
            $template   = $this->templates->find($templateId);

            if ($template === null) {
                return $this->fail('模板不存在', 404);
            }

            $intro = $this->validateIntro($request->param('intro', ''), '还原到历史 #' . $id);

            Db::transaction(function () use ($template, $templateId, $history, $intro): void {
                $this->histories->record(
                    $templateId,
                    (string) ($template['title'] ?? ''),
                    (string) ($template['content'] ?? ''),
                    $intro
                );

                if (!$this->templates->update($templateId, (string) ($history['newstext'] ?? ''))) {
                    throw new RuntimeException('模板更新失败');
                }
            });

            return $this->ok([
                'template_id' => $templateId,
                'content'     => (string) ($history['newstext'] ?? ''),
            ]);
        });
    }

    protected function ensureEnabled(): void
    {
        if (!$this->histories->enabled()) {
            throw new InvalidArgumentException('未启用模板编辑历史（store_codeditor.history.enabled）');
        }
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

    protected function validateId(mixed $value, string $label): int
    {
        if (is_string($value)) {
            $value = trim($value);
        }

        if (!is_numeric($value) || (int) $value <= 0 || (string) (int) $value !== (string) $value) {
            throw new InvalidArgumentException($label . ' 必须是正整数');
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