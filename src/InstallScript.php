<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig;

use Hyperf\Command\Concerns\InteractsWithIO;
use Hyperf\DbConnection\Db;
use Symfony\Component\Console\Output\ConsoleOutput;

class InstallScript
{
    use InteractsWithIO;

    public function __invoke(): void
    {
        $this->output = new ConsoleOutput();

        $this->info('========================================');
        $this->info('系统配置插件安装');
        $this->info('========================================');

        try {
            $this->publishModules();
            $this->seedI18n();

            $this->info('插件安装成功！');
        } catch (\Throwable $e) {
            $this->error('插件安装失败：' . $e->getMessage());
            throw $e;
        }
    }

    protected function publishModules(): void
    {
        $source = \dirname(__DIR__) . '/publish/modules';
        $target = BASE_PATH . '/config/modules';

        if (! is_dir($target)) {
            mkdir($target, 0755, true);
        }

        $files = glob($source . '/*.php');
        foreach ($files as $file) {
            $fileName = basename($file);
            $targetPath = $target . '/' . $fileName;

            if (! file_exists($targetPath)) {
                copy($file, $targetPath);
                $this->info("已发布配置定义: {$fileName}");
            } else {
                $this->info("配置定义已存在，跳过: {$fileName}");
            }
        }
    }

    protected function seedI18n(): void
    {
        $localeDir = BASE_PATH . '/web/src/modules/base/locales';
        if (! is_dir($localeDir)) {
            return;
        }

        $translations = [
            'zh_CN[简体中文].yaml' => ['index' => '系统配置', 'config' => '配置管理'],
            'zh_TW[繁體中文].yaml' => ['index' => '系統配置', 'config' => '配置管理'],
            'en[English].yaml' => ['index' => 'System Config', 'config' => 'Config Management'],
        ];

        foreach ($translations as $file => $keys) {
            $path = $localeDir . '/' . $file;
            if (! file_exists($path)) {
                continue;
            }

            $content = file_get_contents($path);
            if (str_contains($content, 'systemConfig')) {
                continue;
            }

            $entry = "\n  systemConfig:\n";
            foreach ($keys as $key => $value) {
                $entry .= "    {$key}: {$value}\n";
            }

            file_put_contents($path, rtrim($content) . $entry);
            $this->info("已添加国际化翻译: {$file}");
        }
    }
}
