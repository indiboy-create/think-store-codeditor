<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use InvalidArgumentException;

/**
 * 模板分类数据仓储。
 *
 * 负责分类数据的查询与增改删，字段白名单与类型在写入前统一处理。
 */
class Categories
{
    /**
     * 允许写入的字段。
     */
    private const FIELDS = [
        'title',
        'titlepic',
        'name',
        'sort',
        'status',
        'intro',
        'is_last',
    ];

    /**
     * 整型字段。
     */
    private const INTEGER_FIELDS = ['sort'];

    protected string $table;

    protected Operator $operator;

    public function __construct(array $config = [], ?Operator $operator = null)
    {
        $database = (array) ($config['database'] ?? []);
        $table    = (string) ($database['category_table'] ?? 'template_category');

        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            throw new InvalidArgumentException('分类数据表名只允许英文、数字与下划线');
        }

        $this->table    = $table;
        $this->operator = $operator ?? new Operator($config);
    }

    /**
     * 创建按当前配置初始化的分类模型。
     */
    public function model(): TemplateCategory
    {
        return new TemplateCategory([], $this->table);
    }

    /**
     * 读取全部分类，按 sort、id 升序。
     *
     * @return array<int, array<string, mixed>>
     */
    public function lists(): array
    {
        return $this->model()
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 读取单条分类。
     *
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $row = $this->model()->where('id', $id)->find();

        return $row ? $row->toArray() : null;
    }

    /**
     * 新增分类，自动写入创建时间与更新时间。
     */
    public function create(array $input): int
    {
        $data = $this->payload($input);
        $now  = time();

        $adminId = $this->operator->adminId();
        $siteId  = $this->operator->siteId();

        if ($adminId > 0) {
            $data['admin_id'] = $adminId;
        }

        if ($siteId > 0) {
            $data['site_id'] = $siteId;
        }

        $data['create_time'] = $now;
        $data['update_time'] = $now;

        $model = $this->model();
        $model->save($data);

        return (int) $model->getKey();
    }

    /**
     * 更新分类，自动刷新更新时间。
     */
    public function update(int $id, array $input): bool
    {
        $row = $this->model()->where('id', $id)->find();

        if (!$row) {
            return false;
        }

        $data = $this->payload($input);

        $adminId = $this->operator->adminId();
        $siteId  = $this->operator->siteId();

        if ($adminId > 0) {
            $data['admin_id'] = $adminId;
        }

        if ($siteId > 0) {
            $data['site_id'] = $siteId;
        }

        if ($data === []) {
            return true;
        }

        $data['update_time'] = time();
        $row->save($data);

        return true;
    }

    /**
     * 删除分类。
     */
    public function delete(int $id): bool
    {
        return (int) $this->model()->where('id', $id)->delete() > 0;
    }

    /**
     * 过滤并转换允许写入的字段。
     *
     * @return array<string, mixed>
     */
    public function payload(array $input): array
    {
        $data = [];

        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $input)) {
                continue;
            }

            $value = $input[$field];

            if ($field === 'status' || $field === 'is_last') {
                $data[$field] = (int) $value === 1 ? 1 : 0;
                continue;
            }

            if (in_array($field, self::INTEGER_FIELDS, true)) {
                $data[$field] = max(0, (int) $value);
                continue;
            }

            $data[$field] = trim((string) $value);
        }

        return $data;
    }
}