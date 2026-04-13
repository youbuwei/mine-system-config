<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig\Service;

use App\Http\CurrentUser;
use App\Library\IPHelper;
use Hyperf\Collection\Collection;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Plugin\Youbuwei\SystemConfig\Model\ConfigField;
use Plugin\Youbuwei\SystemConfig\Model\ConfigGroup;
use Plugin\Youbuwei\SystemConfig\Model\ConfigItem;
use Plugin\Youbuwei\SystemConfig\Model\ConfigModule;
use Plugin\Youbuwei\SystemConfig\Repository\ConfigFieldRepository;
use Plugin\Youbuwei\SystemConfig\Repository\ConfigGroupRepository;
use Plugin\Youbuwei\SystemConfig\Repository\ConfigItemRepository;
use Plugin\Youbuwei\SystemConfig\Repository\ConfigLogRepository;
use Plugin\Youbuwei\SystemConfig\Repository\ConfigModuleRepository;
use Plugin\Youbuwei\SystemConfig\Repository\ConfigValueRepository;

/**
 * 配置服务 - 核心业务逻辑.
 */
class ConfigService
{
    #[Inject]
    protected ConfigModuleRepository $moduleRepository;

    #[Inject]
    protected ConfigGroupRepository $groupRepository;

    #[Inject]
    protected ConfigItemRepository $itemRepository;

    #[Inject]
    protected ConfigFieldRepository $fieldRepository;

    #[Inject]
    protected ConfigValueRepository $valueRepository;

    #[Inject]
    protected ConfigLogRepository $logRepository;

    #[Inject]
    protected ConfigCacheService $cacheService;

    #[Inject]
    protected CurrentUser $currentUser;

    public function getGroupTree(): Collection
    {
        $result = $this->cacheService->remember('config:groups:tree', 3600, function () {
            $groups = $this->groupRepository->getAllWithFields();
            return $this->formatGroupTree($groups)->toArray();
        });

        return collect($result);
    }

    public function getGroupFields(int $groupId): array
    {
        return $this->cacheService->remember(
            "config:group:{$groupId}:fields",
            3600,
            fn () => $this->fieldRepository->getByGroupId($groupId)
        );
    }

    public function get(string $path, mixed $default = null, string $scope = 'default'): mixed
    {
        if ($this->isLegacyPath($path)) {
            $path = $this->convertLegacyPath($path);
        }

        $cached = $this->cacheService->getValue($path, $scope);
        if ($cached !== null) {
            return $cached;
        }

        $item = $this->itemRepository->findByPath($path);
        if (! $item) {
            return $default;
        }

        $value = $this->valueRepository->findByFieldAndScope($item->id, $scope);
        $result = $value?->value ?? $item->default_value;
        $result = $this->parseValue($result, $item->type);
        $this->cacheService->setValue($path, $result, $scope);

        return $result ?? $default;
    }

    public function getMultiple(array $paths, string $scope = 'default'): array
    {
        $result = [];
        foreach ($paths as $path) {
            $result[$path] = $this->get($path, null, $scope);
        }
        return $result;
    }

    public function getGroupValues(string $groupKey, string $scope = 'default'): array
    {
        $group = $this->groupRepository->findByKey($groupKey);
        if (! $group) {
            return [];
        }

        $fields = $this->fieldRepository->getByGroupId($group->id);
        $values = $this->valueRepository->getByScope($scope);

        $result = [];
        foreach ($fields as $field) {
            $value = $values[$field['id']]['value'] ?? $field['default_value'];
            $result[$field['key']] = $this->parseValue($value, $field['type']);
        }

        return $result;
    }

    public function set(string $path, mixed $value, string $scope = 'default'): bool
    {
        $field = $this->fieldRepository->findByPath($path);
        if (! $field) {
            return false;
        }

        return $this->setByField($field, $value, $scope);
    }

    public function setByField(ConfigItem $field, mixed $value, string $scope = 'default'): bool
    {
        $oldValueObj = $this->valueRepository->findByFieldAndScope($field->id, $scope);
        $oldValue = $oldValueObj?->value;

        if ($field->isEncrypted()) {
            $value = $this->encrypt($value);
        }

        $this->valueRepository->setValue(
            $field->id,
            (string) $value,
            $scope,
            $this->currentUser->id()
        );

        $this->logRepository->log(
            $field->id,
            $scope,
            $oldValue,
            (string) $value,
            $this->currentUser->id(),
            IPHelper::getClientIp()
        );

        $this->cacheService->forget($field->getPath(), $scope);

        return true;
    }

    public function batchUpdate(array $data, string $scope = 'default'): void
    {
        foreach ($data as $path => $value) {
            $this->set($path, $value, $scope);
        }
    }

    public function batchUpdateByGroup(int $groupId, array $data, string $scope = 'default'): void
    {
        $group = $this->groupRepository->findById($groupId);
        if (! $group) {
            return;
        }

        $fields = $this->fieldRepository->list(['group_id' => $groupId, 'status' => 1]);
        $fieldMap = $fields->keyBy('key');

        foreach ($data as $fieldKey => $value) {
            $field = $fieldMap->get($fieldKey);
            if ($field) {
                $this->setByField($field, $value, $scope);
            }
        }
    }

    public function getChangeLogs(int $fieldId, string $scope = 'default', int $limit = 50): array
    {
        return $this->logRepository->getFieldHistory($fieldId, $scope, $limit);
    }

    public function refreshCache(): void
    {
        $this->cacheService->flush();
    }

    public function getModuleTree(): Collection
    {
        $result = $this->cacheService->remember('config:modules:tree', 3600, function () {
            $modules = ConfigModule::with(['groups.items'])->where('is_enabled', 1)->orderBy('sort')->get();
            return $this->formatModuleTree($modules)->toArray();
        });

        return collect($result);
    }

    public function getModuleGroups(string $moduleKey): array
    {
        $module = ConfigModule::where('key', $moduleKey)->first();
        if (! $module) {
            return [];
        }

        return $this->cacheService->remember(
            "config:module:{$moduleKey}:groups",
            3600,
            static fn () => ConfigGroup::where('module_id', $module->id)->where('status', 1)->orderBy('sort')->get()->toArray()
        );
    }

    public function getModuleValues(string $moduleKey, string $scope = 'default'): array
    {
        $module = ConfigModule::where('key', $moduleKey)->first();
        if (! $module) {
            return [];
        }

        return $this->cacheService->remember(
            "config:module:{$moduleKey}:values:{$scope}",
            1800,
            function () use ($module, $scope) {
                $groups = ConfigGroup::with(['items'])
                    ->where('module_id', $module->id)
                    ->where('status', 1)
                    ->orderBy('sort')
                    ->get();

                $values = $this->valueRepository->getByScope($scope);
                $result = [];

                foreach ($groups as $group) {
                    $groupValues = [];
                    foreach ($group->items as $item) {
                        $value = $values[$item->id]['value'] ?? $item->default_value;
                        $groupValues[$item->key] = $this->parseValue($value, $item->type);
                    }
                    $result[$group->key] = $groupValues;
                }

                return $result;
            }
        );
    }

    public function batchUpdateByModule(string $moduleKey, array $data, string $scope = 'default'): void
    {
        $module = ConfigModule::where('key', $moduleKey)->first();
        if (! $module) {
            return;
        }

        $updates = [];
        foreach ($data as $groupKey => $groupData) {
            if (! \is_array($groupData)) {
                continue;
            }

            $group = ConfigGroup::where('module_id', $module->id)
                ->where('key', $groupKey)
                ->first();

            if (! $group) {
                continue;
            }

            $items = ConfigItem::where('group_id', $group->id)
                ->where('status', 1)
                ->get()
                ->keyBy('key');

            foreach ($groupData as $itemKey => $value) {
                $item = $items->get($itemKey);
                if ($item) {
                    $updates[] = [
                        'item' => $item,
                        'value' => $value,
                        'path' => "{$moduleKey}.{$groupKey}.{$itemKey}",
                    ];
                }
            }
        }

        if (empty($updates)) {
            return;
        }

        Db::beginTransaction();
        try {
            foreach ($updates as $update) {
                $item = $update['item'];
                $value = $update['value'];

                $oldValueObj = $this->valueRepository->findByFieldAndScope($item->id, $scope);
                $oldValue = $oldValueObj?->value;

                if ($item->isEncrypted()) {
                    $value = $this->encrypt((string) $value);
                }

                $this->valueRepository->setValue(
                    $item->id,
                    (string) $value,
                    $scope,
                    $this->currentUser->id()
                );

                $this->logRepository->log(
                    $item->id,
                    $scope,
                    $oldValue,
                    (string) $value,
                    $this->currentUser->id(),
                    IPHelper::getClientIp()
                );
            }

            Db::commit();
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }

        $this->cacheService->forgetKey("config:module:{$moduleKey}:values:{$scope}");
        foreach ($updates as $update) {
            $this->cacheService->forget($update['path'], $scope);
        }
    }

    protected function isLegacyPath(string $path): bool
    {
        $segments = explode('.', $path);
        return \count($segments) === 2;
    }

    protected function convertLegacyPath(string $legacyPath): string
    {
        [$groupKey, $itemKey] = explode('.', $legacyPath);

        $group = ConfigGroup::where('key', $groupKey)->first();
        if (! $group || ! $group->module_id) {
            return $legacyPath;
        }

        $module = ConfigModule::find($group->module_id);
        return $module ? "{$module->key}.{$groupKey}.{$itemKey}" : $legacyPath;
    }

    protected function formatGroupTree(Collection $groups): Collection
    {
        return $groups->map(function ($group) {
            $item = [
                'id' => $group->id,
                'key' => $group->key,
                'name' => $group->name,
                'description' => $group->description,
                'icon' => $group->icon,
                'sort' => $group->sort,
            ];

            if ($group->fields->isNotEmpty()) {
                $item['fields'] = $group->fields->map(fn ($field) => $this->formatField($field));
            }

            if ($group->children->isNotEmpty()) {
                $item['children'] = $this->formatGroupTree($group->children);
            }

            return $item;
        });
    }

    protected function formatField(ConfigField $field): array
    {
        return [
            'id' => $field->id,
            'key' => $field->key,
            'name' => $field->name,
            'description' => $field->description,
            'type' => $field->type,
            'options' => $field->options,
            'validation' => $field->validation,
            'default_value' => $field->default_value,
            'placeholder' => $field->placeholder,
            'tooltip' => $field->tooltip,
            'sort' => $field->sort,
            'is_encrypted' => $field->is_encrypted,
        ];
    }

    protected function parseValue(mixed $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'number' => is_numeric($value) ? (int) $value : 0,
            'switch' => \in_array($value, [1, '1', true, 'true'], true),
            'multiSelect', 'checkbox' => \is_string($value) ? json_decode($value, true) : $value,
            'json' => \is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    protected function encrypt(string $value): string
    {
        return base64_encode($value);
    }

    protected function formatModuleTree(Collection $modules): Collection
    {
        return $modules->map(function ($module) {
            return [
                'id' => $module->id,
                'key' => $module->key,
                'name' => $module->name,
                'description' => $module->description,
                'icon' => $module->icon,
                'sort' => $module->sort,
                'groups' => $module->groups->map(fn ($group) => $this->formatGroup($group)),
            ];
        });
    }

    protected function formatGroup($group): array
    {
        return [
            'id' => $group->id,
            'key' => $group->key,
            'name' => $group->name,
            'description' => $group->description,
            'icon' => $group->icon,
            'sort' => $group->sort,
            'items' => $group->items->map(fn ($item) => $this->formatItem($item)),
        ];
    }

    protected function formatItem($item): array
    {
        return [
            'id' => $item->id,
            'key' => $item->key,
            'name' => $item->name,
            'description' => $item->description,
            'type' => $item->type,
            'options' => $item->options,
            'validation' => $item->validation,
            'default_value' => $item->default_value,
            'placeholder' => $item->placeholder,
            'tooltip' => $item->tooltip,
            'sort' => $item->sort,
            'is_encrypted' => $item->is_encrypted,
        ];
    }
}
