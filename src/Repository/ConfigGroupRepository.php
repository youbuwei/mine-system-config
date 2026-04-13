<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig\Repository;

use App\Repository\IRepository;
use Hyperf\Collection\Collection;
use Hyperf\Database\Model\Builder;
use Plugin\Youbuwei\SystemConfig\Model\ConfigGroup;

/**
 * 配置分组仓库.
 * @extends IRepository<ConfigGroup>
 */
class ConfigGroupRepository extends IRepository
{
    public ?ConfigGroup $model;

    public function __construct(ConfigGroup $model)
    {
        $this->model = $model;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['key']), static fn ($q) => $q->where('key', $params['key']))
            ->when(isset($params['name']), static fn ($q) => $q->where('name', 'like', "%{$params['name']}%"))
            ->when(isset($params['parent_id']), static fn ($q) => $q->where('parent_id', $params['parent_id']))
            ->when(isset($params['status']), static fn ($q) => $q->where('status', $params['status']))
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function getTree(?int $parentId = null): Collection
    {
        return $this->getQuery()
            ->where('parent_id', $parentId)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function findByKey(string $key): ?ConfigGroup
    {
        return $this->getQuery()->where('key', $key)->first();
    }

    public function getAllWithFields(): Collection
    {
        return $this->getQuery()
            ->where('status', 1)
            ->whereNull('parent_id')
            ->with(['fields' => static fn ($q) => $q->where('status', 1)])
            ->with('children.fields')
            ->orderBy('sort')
            ->get();
    }
}
