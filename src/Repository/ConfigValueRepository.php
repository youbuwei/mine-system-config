<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Repository;

use App\Repository\IRepository;
use Hyperf\Database\Model\Builder;
use Plugin\NsTicket\SystemConfig\Model\ConfigValue;

/**
 * 配置值仓库.
 * @extends IRepository<ConfigValue>
 */
class ConfigValueRepository extends IRepository
{
    public ?ConfigValue $model;

    public function __construct(ConfigValue $model)
    {
        $this->model = $model;
    }

    public function handleSearch(Builder $query, array $params): Builder
    {
        return $query
            ->when(isset($params['field_id']), static fn ($q) => $q->where('field_id', $params['field_id']))
            ->when(isset($params['scope']), static fn ($q) => $q->where('scope', $params['scope']));
    }

    public function findByFieldAndScope(int $fieldId, string $scope = 'default'): ?ConfigValue
    {
        return $this->getQuery()
            ->where('field_id', $fieldId)
            ->where('scope', $scope)
            ->first();
    }

    public function getByScope(string $scope = 'default'): array
    {
        return $this->getQuery()
            ->where('scope', $scope)
            ->get()
            ->keyBy('field_id')
            ->toArray();
    }

    public function setValue(int $fieldId, string $value, string $scope = 'default', ?int $userId = null): ConfigValue
    {
        $configValue = $this->findByFieldAndScope($fieldId, $scope);

        if ($configValue) {
            $configValue->fill([
                'value' => $value,
                'updated_by' => $userId,
            ])->save();
            return $configValue->refresh();
        }

        return $this->create([
            'field_id' => $fieldId,
            'scope' => $scope,
            'value' => $value,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);
    }

    public function batchSetValues(array $data, string $scope = 'default', ?int $userId = null): void
    {
        foreach ($data as $fieldId => $value) {
            $this->setValue((int) $fieldId, (string) $value, $scope, $userId);
        }
    }
}
