<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig\Service;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Redis\Redis;
use Hyperf\Redis\RedisFactory;

/**
 * 配置缓存服务.
 */
class ConfigCacheService
{
    #[Inject]
    protected RedisFactory $redisFactory;

    protected string $prefix = 'youbuwei:config:';

    protected int $ttl = 3600;

    protected string $poolName = 'default';

    public function getValue(string $path, string $scope = 'default'): mixed
    {
        $key = $this->getCacheKey($path, $scope);
        $cached = $this->getRedis()->get($key);

        if ($cached !== false && $cached !== null) {
            return json_decode($cached, true);
        }

        return null;
    }

    public function setValue(string $path, mixed $value, string $scope = 'default'): bool
    {
        $key = $this->getCacheKey($path, $scope);
        return (bool) $this->getRedis()->setex($key, $this->ttl, json_encode($value, \JSON_UNESCAPED_UNICODE));
    }

    public function forget(string $path, string $scope = 'default'): bool
    {
        $key = $this->getCacheKey($path, $scope);
        return (bool) $this->getRedis()->del($key);
    }

    public function forgetKey(string $key): bool
    {
        $fullKey = $this->prefix . $key;
        return (bool) $this->getRedis()->del($fullKey);
    }

    public function flush(): void
    {
        $pattern = $this->prefix . '*';
        $redis = $this->getRedis();
        $keys = $redis->keys($pattern);

        if (! empty($keys)) {
            $redis->del($keys);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $fullKey = $this->prefix . $key;
        $redis = $this->getRedis();
        $cached = $redis->get($fullKey);

        if ($cached !== false && $cached !== null) {
            return json_decode($cached, true);
        }

        $value = $callback();
        $redis->setex($fullKey, $ttl, json_encode($value, \JSON_UNESCAPED_UNICODE));

        return $value;
    }

    protected function getRedis(): Redis
    {
        return $this->redisFactory->get($this->poolName);
    }

    protected function getCacheKey(string $path, string $scope): string
    {
        return \sprintf('%svalue:%s:%s', $this->prefix, $scope, str_replace('.', ':', $path));
    }
}
