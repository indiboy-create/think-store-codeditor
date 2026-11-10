<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use think\Model;

/**
 * 模板数据表模型。
 *
 * 表名、主键与连接均在运行时由 Templates 服务按配置注入，
 * 因此同一个模型可以适配主项目自定义的表结构。
 */
class Template extends Model
{
    /**
     * 数据表名称，不含表前缀。
     *
     * @var string
     */
    protected $name = 'template';

    /**
     * 主键字段名。
     *
     * @var string
     */
    protected $pk = 'id';

    /**
     * 模板表通常没有创建时间、更新时间字段，关闭自动时间戳。
     *
     * @var bool
     */
    protected $autoWriteTimestamp = false;

    /**
     * 数据库连接名，空字符串表示使用默认连接。
     *
     * @var string
     */
    protected $connection = '';

    /**
     * 字段结构缓存。
     *
     * ThinkORM 4 会在模型构造阶段读取字段结构，因此表名、主键或连接
     * 变化后必须清空该缓存，否则会继续使用旧表的字段白名单。
     *
     * @var array
     */
    protected $schema = [];

    /**
     * @param array|object $data       模型数据
     * @param string       $table      数据表名，不含前缀
     * @param string       $key        主键字段名
     * @param string       $connection 数据库连接名
     */
    public function __construct(array | object $data = [], string $table = '', string $key = '', string $connection = '')
    {
        if ($table !== '') {
            $this->name = $table;
        }

        if ($key !== '') {
            $this->pk = $key;
        }

        if ($connection !== '') {
            $this->connection = $connection;
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

    /**
     * 设置主键字段名。
     */
    public function useKey(string $key): static
    {
        $this->pk     = $key;
        $this->schema = [];

        return $this;
    }

    /**
     * 设置数据库连接名。
     */
    public function useConnection(string $connection): static
    {
        $this->connection = $connection;
        $this->schema     = [];

        return $this;
    }
}