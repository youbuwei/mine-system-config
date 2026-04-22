<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class ConfigMenuSeeder extends Seeder
{
    public function run(): void
    {
        if (Db::table('menu')->where('name', 'systemConfig')->exists()) {
            echo "  系统配置菜单已存在，跳过\n";
            return;
        }

        Db::table('menu')->insert([
            'parent_id' => 0,
            'name' => 'systemConfig',
            'path' => '/system/settings',
            'component' => 'youbuwei/system-config/views/config/index',
            'redirect' => '',
            'status' => 1,
            'sort' => 100,
            'created_by' => 1,
            'updated_by' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'remark' => '',
            'meta' => json_encode([
                'i18n' => 'baseMenu.systemConfig.config',
                'icon' => 'ri:settings-3-line',
                'type' => 'M',
                'affix' => false,
                'cache' => true,
                'title' => '系统配置',
                'hidden' => false,
                'copyright' => true,
                'componentPath' => 'modules/',
                'componentSuffix' => '.vue',
                'breadcrumbEnable' => true,
            ]),
        ]);

        echo "  已创建系统配置菜单\n";
    }
}
