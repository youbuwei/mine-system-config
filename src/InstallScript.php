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
        $targetDir = BASE_PATH . '/web/src/modules/base/locales';
        $sourceDir = \dirname(__DIR__) . '/web/locales';

        if (! is_dir($targetDir) || ! is_dir($sourceDir)) {
            return;
        }

        $localeMap = [
            'zh_CN.yaml' => 'zh_CN[简体中文].yaml',
            'zh_TW.yaml' => 'zh_TW[繁體中文].yaml',
            'en.yaml' => 'en[English].yaml',
        ];

        foreach ($localeMap as $sourceFile => $targetFile) {
            $sourcePath = $sourceDir . '/' . $sourceFile;
            $targetPath = $targetDir . '/' . $targetFile;

            if (! file_exists($sourcePath) || ! file_exists($targetPath)) {
                continue;
            }

            $content = file_get_contents($targetPath);
            if (str_contains($content, 'systemConfig')) {
                continue;
            }

            $entry = "\n" . rtrim(file_get_contents($sourcePath));
            file_put_contents($targetPath, rtrim($content) . $entry);
            $this->info("已添加国际化翻译: {$targetFile}");
        }
    }
}
