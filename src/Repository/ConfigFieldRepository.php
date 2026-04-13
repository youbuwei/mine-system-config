<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\NsTicket\SystemConfig\Model\ConfigField;

/**
 * 配置字段仓库.
 * @extends IRepository<ConfigField>
 */
class ConfigFieldRepository extends IRepository
{
    public ?ConfigField $model;

    public function __construct(ConfigField $model)
    {
        $this->model = $model;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
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

    public function findByGroupKeyAndFieldKey(string $groupKey, string $fieldKey): ?ConfigField
    {
        return $this->getQuery()
            ->whereHas('group', static fn ($q) => $q->where('key', $groupKey))
            ->where('key', $fieldKey)
            ->first();
    }

    public function findByPath(string $path): ?ConfigField
    {
        $parts = explode('.', $path, 2);
        if (\count($parts) !== 2) {
            return null;
        }
        [$groupKey, $fieldKey] = $parts;
        return $this->findByGroupKeyAndFieldKey($groupKey, $fieldKey);
    }
}
