<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Support\Filesystem\Filesystem;
use Plugin\NsTicket\SystemConfig\Model\ConfigGroup;
use Plugin\NsTicket\SystemConfig\Model\ConfigItem;
use Plugin\NsTicket\SystemConfig\Model\ConfigModule;
use Psr\Log\LoggerInterface;

/**
 * 配置模块注册服务.
 */
class ConfigModuleRegistry
{
    #[Inject]
    protected ConfigCacheService $cacheService;

    #[Inject]
    protected LoggerInterface $logger;

    protected Filesystem $filesystem;

    protected string $modulesPath;

    public function __construct()
    {
        $this->filesystem = new Filesystem();
        $this->modulesPath = BASE_PATH . '/config/modules';
    }

    public function scanAndRegister(): array
    {
        $stats = [
            'modules' => 0,
            'groups' => 0,
            'items' => 0,
        ];

        if (! $this->filesystem->isDirectory($this->modulesPath)) {
            $this->logger->warning("Config modules directory not found: {$this->modulesPath}");
            return $stats;
        }

        $files = $this->filesystem->glob($this->modulesPath . '/*.php');

        foreach ($files as $file) {
            try {
                $definition = require $file;
                $moduleStats = $this->registerModule($definition, $file);
                $stats['modules'] += $moduleStats['modules'];
                $stats['groups'] += $moduleStats['groups'];
                $stats['items'] += $moduleStats['items'];
            } catch (\Throwable $e) {
                $this->logger->error("Failed to register config module from file: {$file}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->cacheService->flush();

        return $stats;
    }

    public function getModuleFiles(): array
    {
        if (! $this->filesystem->isDirectory($this->modulesPath)) {
            return [];
        }

        return $this->filesystem->glob($this->modulesPath . '/*.php');
    }

    public function hasModuleFile(string $moduleKey): bool
    {
        $filePath = $this->modulesPath . "/{$moduleKey}.php";
        return $this->filesystem->exists($filePath);
    }

    public function getModuleDefinition(string $moduleKey): ?array
    {
        $filePath = $this->modulesPath . "/{$moduleKey}.php";

        if (! $this->filesystem->exists($filePath)) {
            return null;
        }

        return require $filePath;
    }

    public function sync(): array
    {
        $this->logger->info('Starting config module synchronization...');
        $stats = $this->scanAndRegister();
        $this->logger->info('Config module synchronization completed', $stats);
        return $stats;
    }

    protected function registerModule(array $definition, string $filePath): array
    {
        $stats = [
            'modules' => 0,
            'groups' => 0,
            'items' => 0,
        ];

        if (! isset($definition['module']) || ! isset($definition['module']['key'])) {
            throw new \InvalidArgumentException('Invalid module definition: missing module.key');
        }

        $moduleData = $definition['module'];
        $relativePath = str_replace(BASE_PATH . '/', '', $filePath);

        $module = ConfigModule::updateOrCreate(
            ['key' => $moduleData['key']],
            [
                'name' => $moduleData['name'] ?? $moduleData['key'],
                'description' => $moduleData['description'] ?? '',
                'icon' => $moduleData['icon'] ?? '',
                'sort' => $moduleData['sort'] ?? 0,
                'is_enabled' => $moduleData['is_enabled'] ?? 1,
                'definition_path' => $relativePath,
            ]
        );

        $stats['modules'] = 1;
        $this->logger->info("Registered config module: {$module->key}");

        foreach ($definition['groups'] ?? [] as $groupData) {
            $groupStats = $this->registerGroup($module, $groupData, $relativePath);
            $stats['groups'] += $groupStats['groups'];
            $stats['items'] += $groupStats['items'];
        }

        return $stats;
    }

    protected function registerGroup(ConfigModule $module, array $groupData, string $definitionPath): array
    {
        $stats = [
            'groups' => 0,
            'items' => 0,
        ];

        if (! isset($groupData['key'])) {
            throw new \InvalidArgumentException("Invalid group definition in module {$module->key}: missing key");
        }

        $group = ConfigGroup::updateOrCreate(
            ['module_id' => $module->id, 'key' => $groupData['key']],
            [
                'name' => $groupData['name'] ?? $groupData['key'],
                'description' => $groupData['description'] ?? '',
                'icon' => $groupData['icon'] ?? '',
                'sort' => $groupData['sort'] ?? 0,
                'status' => $groupData['status'] ?? 1,
                'definition_path' => $definitionPath,
            ]
        );

        $stats['groups'] = 1;
        $this->logger->debug("Registered config group: {$module->key}.{$group->key}");

        foreach ($groupData['items'] ?? [] as $itemData) {
            $this->registerItem($module, $group, $itemData, $definitionPath);
            ++$stats['items'];
        }

        return $stats;
    }

    protected function registerItem(ConfigModule $module, ConfigGroup $group, array $itemData, string $definitionPath): void
    {
        if (! isset($itemData['key'])) {
            throw new \InvalidArgumentException("Invalid item definition in group {$group->key}: missing key");
        }

        ConfigItem::updateOrCreate(
            ['module_id' => $module->id, 'group_id' => $group->id, 'key' => $itemData['key']],
            [
                'name' => $itemData['name'] ?? $itemData['key'],
                'description' => $itemData['description'] ?? '',
                'type' => $itemData['type'] ?? 'text',
                'options' => $itemData['options'] ?? null,
                'validation' => $itemData['validation'] ?? null,
                'default_value' => $itemData['default_value'] ?? null,
                'placeholder' => $itemData['placeholder'] ?? '',
                'tooltip' => $itemData['tooltip'] ?? '',
                'sort' => $itemData['sort'] ?? 0,
                'is_encrypted' => $itemData['is_encrypted'] ?? 0,
                'is_system' => $itemData['is_system'] ?? 0,
                'status' => $itemData['status'] ?? 1,
                'definition_path' => $definitionPath,
            ]
        );

        $this->logger->debug("Registered config item: {$module->key}.{$group->key}.{$itemData['key']}");
    }
}
