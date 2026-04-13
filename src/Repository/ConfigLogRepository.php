<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\NsTicket\SystemConfig\Model\ConfigLog;

/**
 * 配置日志仓库.
 * @extends IRepository<ConfigLog>
 */
class ConfigLogRepository extends IRepository
{
    public ?ConfigLog $model;

    public function __construct(ConfigLog $model)
    {
        $this->model = $model;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['field_id']), static fn ($q) => $q->where('field_id', $params['field_id']))
            ->when(isset($params['scope']), static fn ($q) => $q->where('scope', $params['scope']))
            ->when(isset($params['changed_by']), static fn ($q) => $q->where('changed_by', $params['changed_by']))
            ->orderBy('changed_at', 'desc');
    }

    public function log(int $fieldId, string $scope, ?string $oldValue, ?string $newValue, int $userId, string $ip): ConfigLog
    {
        return $this->create([
            'field_id' => $fieldId,
            'scope' => $scope,
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'changed_by' => $userId,
            'ip' => $ip,
        ]);
    }

    public function getFieldHistory(int $fieldId, string $scope = 'default', int $limit = 50): array
    {
        return $this->getQuery()
            ->where('field_id', $fieldId)
            ->where('scope', $scope)
            ->orderBy('changed_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
