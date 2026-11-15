<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;


/**
 * 模板编辑历史仓储。
 *
 * 负责写入完整内容快照、按 newstext_hash 去重、读取历史列表与详情，
 * 并按每个模板保留最近 N 条历史。
 */
class TemplateHistories
{
    protected bool $enabled;

    protected string $table;

    protected int $limit;

    protected Operator $operator;

    public function __construct(array $config = [], ?Operator $operator = null)
    {
        $database = (array) ($config['database'] ?? []);
        $history  = (array) ($config['history'] ?? []);

        $this->operator = $operator ?? new Operator($config);
        $this->enabled  = (bool) ($history['enabled'] ?? true);
        $this->table    = $this->identifier((string) ($database['history_table'] ?? 'template_history'), 'database.history_table');
        $this->limit    = max(0, (int) ($history['limit'] ?? 50));
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * 创建按当前配置初始化的历史模型。
     */
    public function model(): TemplateHistory
    {
        return new TemplateHistory([], $this->table);
    }

    /**
     * 写入一条历史快照；内容与最近一条历史一致时跳过并返回 null。
     */
    public function record(int $templateId, string $title, string $content, string $intro = ''): ?int
    {
        if (!$this->enabled) {
            return null;
        }

        $hash = hash('sha256', $content);

        $latest = $this->model()
            ->where('template_id', $templateId)
            ->order('id', 'desc')
            ->value('newstext_hash');

        if ($latest !== null && (string) $latest === $hash) {
            return null;
        }

        $now = time();

        $model = $this->model();
        $model->save([
            'template_id'   => $templateId,
            'title'         => $this->limitText($title, 255),
            'intro'         => $this->limitText($intro, 255),
            'newstext'      => $content,
            'newstext_hash' => $hash,
            'newstime'      => $now,
            'admin_id'      => $this->operator->adminId(),
            'site_id'       => $this->operator->siteId(),
            'create_time'   => $now,
            'update_time'   => $now,
        ]);

        $id = (int) $model->getKey();

        $this->prune($templateId);

        return $id;
    }

    /**
     * 读取指定模板的历史列表，不返回 newstext 与 newstext_hash。
     *
     * @return array<int, array<string, mixed>>
     */
    public function lists(int $templateId): array
    {
        return $this->model()
            ->field(['id', 'template_id', 'title', 'intro', 'newstime', 'admin_id', 'site_id', 'create_time', 'update_time'])
            ->where('template_id', $templateId)
            ->order('id', 'desc')
            ->select()
            ->toArray();
    }

    /**
     * 读取单条历史详情，包含 newstext。
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $row = $this->model()->where('id', $id)->find();

        return $row ? $row->toArray() : null;
    }

    /**
     * 删除某个模板的全部历史，供模板删除或创建回滚时清理。
     */
    public function deleteByTemplate(int $templateId): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->model()->where('template_id', $templateId)->delete();
    }

    /**
     * 保留最近 limit 条历史，超出部分删除。
     */
    protected function prune(int $templateId): void
    {
        if ($this->limit <= 0) {
            return;
        }

        $threshold = $this->model()
            ->where('template_id', $templateId)
            ->order('id', 'desc')
            ->limit($this->limit - 1, 1)
            ->value('id');

        if (!$threshold) {
            return;
        }

        $this->model()
            ->where('template_id', $templateId)
            ->where('id', '<', (int) $threshold)
            ->delete();
    }


    protected function limitText(string $text, int $length): string
    {
        $text = trim($text);

        return function_exists('mb_substr') ? mb_substr($text, 0, $length) : substr($text, 0, $length);
    }

    /**
     * 校验表名只允许英文、数字与下划线，避免配置拼接注入。
     */
    protected function identifier(string $value, string $name): string
    {
        $value = trim($value);

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new InvalidArgumentException('codeditor.' . $name . ' 不是合法的表名或字段名：' . $value);
        }

        return $value;
    }
}