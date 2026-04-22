<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig;

use Hyperf\Command\Concerns\InteractsWithIO;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Output\ConsoleOutput;

class UninstallScript
{
    use InteractsWithIO;

    public function __invoke(): void
    {
        $this->output = new ConsoleOutput();

        $this->info('========================================');
        $this->info('即将卸载系统配置插件');
        $this->info('========================================');

        try {
            $this->removeMenus();
            $this->removeModules();
            $this->clearCache();
            $this->removeI18n();

            $this->info('插件卸载成功！');
        } catch (\Throwable $e) {
            $this->error('插件卸载失败：' . $e->getMessage());
            throw $e;
        }
    }

    protected function removeMenus(): void
    {
        Db::table('menu')->where('name', 'systemConfig')->delete();
        Db::table('menu')->where('name', 'systemConfig:config')->delete();
        $this->info('菜单数据清理成功');
    }

    protected function removeModules(): void
    {
        $modules = ['order.php', 'payment.php', 'notification.php'];
        $target = BASE_PATH . '/config/modules';

        foreach ($modules as $file) {
            $path = $target . '/' . $file;
            if (file_exists($path)) {
                unlink($path);
                $this->info("已删除配置定义: {$file}");
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
                $this->info('配置缓存清理成功');
            }
        } catch (\Throwable $e) {
            $this->error('清除缓存失败: ' . $e->getMessage());
        }
    }

    protected function removeI18n(): void
    {
        $localeFiles = glob(BASE_PATH . '/web/src/modules/base/locales/*.yaml');
        if (empty($localeFiles)) {
            return;
        }

        foreach ($localeFiles as $file) {
            $content = file_get_contents($file);
            if ($content === false || ! str_contains($content, 'systemConfig')) {
                continue;
            }

            $content = preg_replace('/\n  systemConfig:\n    index: .+\n    config: .+/', '', $content);
            file_put_contents($file, $content);
        }
        $this->info('国际化翻译清理成功');
    }
}
