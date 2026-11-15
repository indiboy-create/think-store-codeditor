<?php

declare(strict_types=1);

namespace Youvision\ThinkStoreCodeditor;

use Throwable;

/**
 * 主项目操作人与站点上下文。
 *
 * 从主项目 Session 读取 admin_id / site_id，供模板、分类、历史三张表共用。
 */
class Operator
{
    protected string $adminSessionKey;

    protected string $siteSessionKey;

    public function __construct(array $config = [])
    {
        $session = (array) ($config['session'] ?? []);

        $this->adminSessionKey = trim((string) ($session['admin_session_key'] ?? ''));
        $this->siteSessionKey  = trim((string) ($session['site_session_key'] ?? ''));
    }

    public function adminId(): int
    {
        return $this->sessionInt($this->adminSessionKey);
    }

    public function siteId(): int
    {
        return $this->sessionInt($this->siteSessionKey);
    }

    protected function sessionInt(string $key): int
    {
        if ($key === '') {
            return 0;
        }

        try {
            return (int) session($key);
        } catch (Throwable) {
            return 0;
        }
    }
}