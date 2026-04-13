<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\NsTicket\SystemConfig\Model\ConfigItem;

/**
 * 配置项仓库.
 * @extends IRepository<ConfigItem>
 */
class ConfigItemRepository extends IRepository
{
    public ?ConfigItem $model;

    public function __construct(ConfigItem $model)
    {
        $this->model = $model;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['module_id']), static fn ($q) => $q->where('module_id', $params['module_id']))
            ->when(isset($params['group_id']), static fn ($q) => $q->where('group_id', $params['group_id']))
            ->when(isset($params['key']), static fn ($q) => $q->where('key', $params['key']))
            ->when(isset($params['name']), static fn ($q) => $q->where('name', 'like', "%{$params['name']}%"))
            ->when(isset($params['type']), static fn ($q) => $q->where('type', $params['type']))
            ->when(isset($params['status']), static fn ($q) => $q->where('status', $params['status']))
            ->orderBy('sort')
            ->orderBy('id');
    }

    public function getByGroupId(int $groupId): array
    {
        return $this->getQuery()
            ->where('group_id', $groupId)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function getByModuleId(int $moduleId): array
    {
        return $this->getQuery()
            ->where('module_id', $moduleId)
            ->where('status', 1)
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->toArray();
    }

    public function findByPath(string $path): ?ConfigItem
    {
        $parts = explode('.', $path);
        if (\count($parts) !== 3) {
            if (\count($parts) === 2) {
                return $this->findByLegacyPath($parts[0], $parts[1]);
            }
            return null;
        }

        [$moduleKey, $groupKey, $itemKey] = $parts;

        return $this->getQuery()
            ->whereHas('module', static fn ($q) => $q->where('key', $moduleKey))
            ->whereHas('group', static fn ($q) => $q->where('key', $groupKey))
            ->where('key', $itemKey)
            ->first();
    }

    public function findByModuleAndGroup(string $moduleKey, string $groupKey, string $itemKey): ?ConfigItem
    {
        return $this->getQuery()
            ->whereHas('module', static fn ($q) => $q->where('key', $moduleKey))
            ->whereHas('group', static fn ($q) => $q->where('key', $groupKey))
            ->where('key', $itemKey)
            ->first();
    }

    protected function findByLegacyPath(string $groupKey, string $itemKey): ?ConfigItem
    {
        return $this->getQuery()
            ->whereHas('group', static fn ($q) => $q->where('key', $groupKey))
            ->where('key', $itemKey)
            ->first();
    }
}
