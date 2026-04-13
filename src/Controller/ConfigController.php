<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig\Controller;

use App\Http\Common\Controller\AbstractController;
use App\Http\Common\Middleware\AccessTokenMiddleware;
use App\Http\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Request;
use Hyperf\Swagger\Annotation\Get;
use Hyperf\Swagger\Annotation\HyperfServer;
use Hyperf\Swagger\Annotation\Post;
use Hyperf\Swagger\Annotation\Put;
use Mine\Access\Attribute\Permission;
use Mine\Swagger\Attributes\ResultResponse;
use Plugin\Youbuwei\SystemConfig\Service\ConfigModuleRegistry;
use Plugin\Youbuwei\SystemConfig\Service\ConfigService;

#[Controller(prefix: 'admin/config')]
#[HyperfServer(name: 'http')]
#[Middleware(AccessTokenMiddleware::class)]
class ConfigController extends AbstractController
{
    public function __construct(
        private readonly ConfigService $configService
    ) {}

    #[Get(
        path: '/admin/config/groups',
        operationId: 'ConfigGroups',
        summary: '获取配置分组树',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getGroups(): Result
    {
        return $this->success($this->configService->getGroupTree());
    }

    #[Get(
        path: '/admin/config/groups/{groupId}/fields',
        operationId: 'ConfigGroupFields',
        summary: '获取分组下的字段',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getGroupFields(int $groupId): Result
    {
        return $this->success($this->configService->getGroupFields($groupId));
    }

    #[Get(
        path: '/admin/config/values',
        operationId: 'ConfigValues',
        summary: '获取配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getValues(Request $request): Result
    {
        $groupKey = $request->input('group');
        $scope = $request->input('scope', 'default');

        if ($groupKey) {
            return $this->success($this->configService->getGroupValues($groupKey, $scope));
        }

        return $this->error('请指定配置分组');
    }

    #[Get(
        path: '/admin/config/values/{path}',
        operationId: 'ConfigValue',
        summary: '获取单个配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getValue(string $path, Request $request): Result
    {
        $scope = $request->input('scope', 'default');
        $default = $request->input('default');

        return $this->success([
            'path' => $path,
            'value' => $this->configService->get($path, $default, $scope),
        ]);
    }

    #[Put(
        path: '/admin/config/values/{path}',
        operationId: 'UpdateConfigValue',
        summary: '更新单个配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:update'])]
    public function updateValue(string $path, Request $request): Result
    {
        $value = $request->input('value');
        $scope = $request->input('scope', 'default');

        $result = $this->configService->set($path, $value, $scope);

        return $result
            ? $this->success([], '配置更新成功')
            : $this->error('配置更新失败');
    }

    #[Post(
        path: '/admin/config/values/batch',
        operationId: 'BatchUpdateConfigValues',
        summary: '批量更新配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:update'])]
    public function batchUpdate(Request $request): Result
    {
        $data = $request->input('data', []);
        $scope = $request->input('scope', 'default');

        if (empty($data)) {
            return $this->error('配置数据不能为空');
        }

        $this->configService->batchUpdate($data, $scope);

        return $this->success([], '配置更新成功');
    }

    #[Post(
        path: '/admin/config/groups/{groupId}/values',
        operationId: 'UpdateGroupConfigValues',
        summary: '通过分组批量更新配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:update'])]
    public function updateGroupValues(int $groupId, Request $request): Result
    {
        $data = $request->input('data', []);
        $scope = $request->input('scope', 'default');

        if (empty($data)) {
            return $this->error('配置数据不能为空');
        }

        $this->configService->batchUpdateByGroup($groupId, $data, $scope);

        return $this->success([], '配置更新成功');
    }

    #[Get(
        path: '/admin/config/logs/{fieldId}',
        operationId: 'ConfigLogs',
        summary: '获取配置变更日志',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getLogs(int $fieldId, Request $request): Result
    {
        $scope = $request->input('scope', 'default');
        $limit = (int) $request->input('limit', 50);

        return $this->success($this->configService->getChangeLogs($fieldId, $scope, $limit));
    }

    #[Post(
        path: '/admin/config/cache/refresh',
        operationId: 'RefreshConfigCache',
        summary: '刷新配置缓存',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:update'])]
    public function refreshCache(): Result
    {
        $this->configService->refreshCache();
        return $this->success([], '缓存刷新成功');
    }

    #[Get(
        path: '/admin/config/modules',
        operationId: 'ConfigModules',
        summary: '获取配置模块树',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getModules(): Result
    {
        return $this->success($this->configService->getModuleTree());
    }

    #[Get(
        path: '/admin/config/modules/{moduleKey}/groups',
        operationId: 'ConfigModuleGroups',
        summary: '获取模块下的分组',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getModuleGroups(string $moduleKey): Result
    {
        return $this->success($this->configService->getModuleGroups($moduleKey));
    }

    #[Get(
        path: '/admin/config/modules/{moduleKey}/values',
        operationId: 'ConfigModuleValues',
        summary: '获取模块下所有配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:list'])]
    public function getModuleValues(string $moduleKey, Request $request): Result
    {
        $scope = $request->input('scope', 'default');
        return $this->success($this->configService->getModuleValues($moduleKey, $scope));
    }

    #[Post(
        path: '/admin/config/modules/{moduleKey}/values',
        operationId: 'UpdateModuleConfigValues',
        summary: '通过模块批量更新配置值',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:update'])]
    public function updateModuleValues(string $moduleKey, Request $request): Result
    {
        $data = $request->input('data', []);
        $scope = $request->input('scope', 'default');

        if (empty($data)) {
            return $this->error('配置数据不能为空');
        }

        $this->configService->batchUpdateByModule($moduleKey, $data, $scope);

        return $this->success([], '配置更新成功');
    }

    #[Post(
        path: '/admin/config/sync',
        operationId: 'SyncConfig',
        summary: '同步配置定义',
        security: [['Bearer' => [], 'ApiKey' => []]],
        tags: ['系统配置']
    )]
    #[ResultResponse(new Result())]
    #[Permission(code: ['config:update'])]
    public function syncConfig(): Result
    {
        $registry = \Hyperf\Support\make(ConfigModuleRegistry::class);
        $stats = $registry->scanAndRegister();

        return $this->success($stats, '配置同步成功');
    }
}
