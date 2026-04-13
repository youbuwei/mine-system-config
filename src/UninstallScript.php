<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig;

class UninstallScript
{
    public function __invoke(): void
    {
        echo "卸载系统配置插件...\n";

        // 1. 删除发布的配置定义文件
        $this->removeModules();

        // 2. 清除配置缓存
        $this->clearCache();

        echo "系统配置插件卸载成功\n";
    }

    protected function removeModules(): void
    {
        $modules = ['order.php', 'payment.php', 'notification.php'];
        $target = BASE_PATH . '/config/modules';

        foreach ($modules as $file) {
            $path = $target . '/' . $file;
            if (file_exists($path)) {
                unlink($path);
                echo "  已删除配置定义: {$file}\n";
            }
        }
    }

    protected function clearCache(): void
    {
        try {
            $redis = \Hyperf\Context\ApplicationContext::getContainer()
                ->get(\Hyperf\Redis\RedisFactory::class)
                ->get('default');

            $keys = $redis->keys('youbuwei:config:*');
            if (! empty($keys)) {
                $redis->del($keys);
                echo "  已清除配置缓存\n";
            }
        } catch (\Throwable $e) {
            echo "  清除缓存失败: {$e->getMessage()}\n";
        }
    }
}
