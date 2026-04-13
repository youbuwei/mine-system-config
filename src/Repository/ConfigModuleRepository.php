<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Hyperf\Database\Model\Collection;
use Plugin\NsTicket\SystemConfig\Model\ConfigModule;

/**
 * 配置模块仓库.
 * @extends IRepository<ConfigModule>
 */
class ConfigModuleRepository extends IRepository
{
    public ?ConfigModule $model;

    public function __construct(ConfigModule $model)
    {
        $this->model = $model;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['key']), static fn ($q) => $q->where('key', $params['key']))
            ->when(isset($params['name']), static fn ($q) => $q->where('name', 'like', "%{$params['name']}%"))
            ->when(isset($params['is_enabled']), static fn ($q) => $q->where('is_enabled', $params['is_enabled']))
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function findByKey(string $key): ?ConfigModule
    {
        return $this->getQuery()
            ->where('key', $key)
            ->first();
    }

    public function getAllWithGroupsAndItems(): Collection
    {
        return $this->getQuery()
            ->with(['groups.items'])
            ->where('is_enabled', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function getAllEnabled(): Collection
    {
        return $this->getQuery()
            ->where('is_enabled', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }
}
