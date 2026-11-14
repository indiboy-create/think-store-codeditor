<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\Model;

/**
 * 模板分类模型。
 *
 * 对应 template_category 表，表名可由 database.category_table 配置。
 */
class TemplateCategory extends Model
{
    /**
     * 数据表名称，不含表前缀。
     *
     * @var string
     */
    protected $name = 'template_category';

    /**
     * 主键字段名。
     *
     * @var string
     */
    protected $pk = 'id';

    /**
     * 分类表通常由主项目维护创建时间、更新时间，关闭自动时间戳。
     *
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * 字段结构缓存。
     *
     * @var array
     */
    protected $schema = [];

    /**
     * @param array|object $data  模型数据
     * @param string       $table 数据表名，不含前缀
     */
    public function __construct(array | object $data = [], string $table = '')
    {
        if ($table !== '') {
            $this->name = $table;
        }

        $this->schema = [];

        parent::__construct($data);
    }

    /**
     * 设置数据表名称。
     */
    public function useTable(string $name): static
    {
        $this->name   = $name;
        $this->schema = [];

        return $this;
    }
}