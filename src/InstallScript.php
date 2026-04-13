<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig;

use Mine\Support\Filesystem;

class InstallScript
{
    public function __invoke(): void
    {
        echo "安装系统配置插件...\n";

        // 1. 复制配置模块定义文件到 config/modules/
        $this->publishModules();

        echo "系统配置插件安装成功\n";
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
                echo "  已发布配置定义: {$fileName}\n";
            } else {
                echo "  配置定义已存在，跳过: {$fileName}\n";
            }
        }
    }
}
