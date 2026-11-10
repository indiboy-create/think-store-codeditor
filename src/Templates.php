<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;

/**
 * 模板数据仓储。
 *
 * 统一封装 template 表的读取、更新、新增与删除，字段名全部来自配置。
 */
class Templates
{
    protected string $table;

    protected string $idField;

    protected string $contentField;

    protected string $connection;

    protected int $listLimit;

    protected int $excerptLength;

    public function __construct(array $config = [])
    {
        $database = (array) ($config['database'] ?? []);

        $this->table        = $this->identifier((string) ($database['table'] ?? 'template'), 'database.table');
        $this->idField      = $this->identifier((string) ($database['id_field'] ?? 'id'), 'database.id_field');
        $this->contentField = $this->identifier((string) ($database['content_field'] ?? 'newstext'), 'database.content_field');
        $this->connection   = trim((string) ($database['connection'] ?? ''));
        $this->listLimit    = max(1, (int) ($database['list_limit'] ?? 200));
        $this->excerptLength = max(0, (int) ($database['excerpt_length'] ?? 60));
    }

    /**
     * 创建按当前配置初始化的模型实例。
     */
    public function model(): Template
    {
        return new Template([], $this->table, $this->idField, $this->connection);
    }

    /**
     * 读取单条模板。
     *
     * @return array{id:int, content:string}|null
     */
    public function find(int $id): ?array
    {
        $row = $this->model()->where($this->idField, $id)->find();

        if (!$row) {
            return null;
        }

        return [
            'id'      => (int) $row->getAttr($this->idField),
            'content' => (string) $row->getAttr($this->contentField),
        ];
    }

    /**
     * 读取模板列表。
     *
     * @return array{total:int, items:array<int, array{id:int, excerpt:string, length:int}>}
     */
    public function lists(): array
    {
        $rows = $this->model()
            ->field([$this->idField, $this->contentField])
            ->order($this->idField, 'desc')
            ->limit($this->listLimit)
            ->select();

        $items = [];

        foreach ($rows as $row) {
            $content = (string) $row->getAttr($this->contentField);
            $items[] = [
                'id'      => (int) $row->getAttr($this->idField),
                'excerpt' => $this->excerpt($content),
                'length'  => $this->length($content),
            ];
        }

        return [
            'total' => count($items),
            'items' => $items,
        ];
    }

    /**
     * 按主键更新模板内容，记录不存在时返回 false。
     */
    public function update(int $id, string $content): bool
    {
        $row = $this->model()->where($this->idField, $id)->find();

        if (!$row) {
            return false;
        }

        $row->setAttr($this->contentField, $content);

        return (bool) $row->save();
    }

    /**
     * 新增模板记录并返回自增主键。
     */
    public function create(string $content): int
    {
        $model = $this->model();
        $model->setAttr($this->contentField, $content);

        if (!$model->save()) {
            throw new \RuntimeException('模板记录写入失败');
        }

        return (int) $model->getKey();
    }

    /**
     * 删除模板记录，用于文件创建失败时回滚。
     */
    public function delete(int $id): void
    {
        $row = $this->model()->where($this->idField, $id)->find();

        if ($row) {
            $row->delete();
        }
    }

    /**
     * 生成列表摘要：去除标签、压缩空白并按配置截断。
     */
    protected function excerpt(string $content): string
    {
        $text = preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '';
        $text = trim($text);

        if ($this->excerptLength > 0 && $this->length($text) > $this->excerptLength) {
            $text = $this->substr($text, 0, $this->excerptLength) . '…';
        }

        return $text;
    }

    protected function length(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }

    protected function substr(string $text, int $start, int $length): string
    {
        return function_exists('mb_substr') ? mb_substr($text, $start, $length) : substr($text, $start, $length);
    }

    /**
     * 校验表名、字段名只允许英文、数字与下划线，避免配置拼接注入。
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