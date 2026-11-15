<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;

/**
 * 模板数据仓储。
 *
 * 模板字段固定为 id、title、cid、intro、path、newstext，
 * 只允许配置表名与列表行为。
 */
class Templates
{
    protected string $table;

    protected string $categoryTable;

    protected Operator $operator;

    public function __construct(array $config = [], ?Operator $operator = null)
    {
        $database = (array) ($config['database'] ?? []);

        $this->operator      = $operator ?? new Operator($config);
        $this->table         = $this->identifier((string) ($database['table'] ?? 'template'), 'database.table');
        $this->categoryTable = $this->identifier((string) ($database['category_table'] ?? 'template_category'), 'database.category_table');
    }

    /**
     * 创建按当前配置初始化的模型实例。
     */
    public function model(): Template
    {
        return new Template([], $this->table);
    }

    /**
     * 读取启用状态的模板分类，按 sort、id 升序。
     *
     * @return array<int, array<string, mixed>>
     */
    public function categories(): array
    {
        return (new TemplateCategory([], $this->categoryTable))
            ->where('status', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 读取单条模板。
     *
     * @return array{id:int, title:string, content:string}|null
     */
    public function find(int $id): ?array
    {
        $row = $this->model()->where('id', $id)->find();

        if (!$row) {
            return null;
        }

        return [
            'id'      => (int) $row->getAttr('id'),
            'title'   => (string) $row->getAttr('title'),
            'content' => (string) $row->getAttr('newstext'),
        ];
    }

    /**
     * 读取模板列表，仅返回 id、title、cid，不读取 newstext。
     *
     * @return array{total:int, items:array<int, array{id:int, title:string, cid:int}>}
     */
    public function lists(): array
    {
        $rows = $this->model()
            ->field(['id', 'title', 'cid'])
            ->order('id', 'desc')
            ->select();

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id'    => (int) $row->getAttr('id'),
                'title' => (string) $row->getAttr('title'),
                'cid'   => (int) $row->getAttr('cid'),
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
        $row = $this->model()->where('id', $id)->find();

        if (!$row) {
            return false;
        }

        $row->setAttr('newstext', $content);

        $adminId = $this->operator->adminId();
        $siteId  = $this->operator->siteId();

        if ($adminId > 0) {
            $row->setAttr('admin_id', $adminId);
        }

        if ($siteId > 0) {
            $row->setAttr('site_id', $siteId);
        }

        return (bool) $row->save();
    }

    /**
     * 新增模板记录并返回自增主键。
     */
    public function create(string $content): int
    {
        $model = $this->model();
        $model->setAttr('newstext', $content);

        $adminId = $this->operator->adminId();
        $siteId  = $this->operator->siteId();

        if ($adminId > 0) {
            $model->setAttr('admin_id', $adminId);
        }

        if ($siteId > 0) {
            $model->setAttr('site_id', $siteId);
        }

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
        $row = $this->model()->where('id', $id)->find();

        if ($row) {
            $row->delete();
        }
    }

    /**

     * 校验表名只允许英文、数字与下划线，避免配置拼接注入。
     */
    protected function identifier(string $value, string $name): string
    {
        $value = trim($value);

        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $value)) {
            throw new InvalidArgumentException('codeditor.' . $name . ' 不是合法的表名：' . $value);
        }

        return $value;
    }
}