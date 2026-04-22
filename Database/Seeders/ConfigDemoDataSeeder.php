<?php

declare(strict_types=1);

use Hyperf\Database\Seeders\Seeder;
use Hyperf\DbConnection\Db;

class ConfigDemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (Db::table('config_module')->where('key', 'demo')->exists()) {
            echo "  演示数据已存在，跳过\n";
            return;
        }

        $now = date('Y-m-d H:i:s');

        $moduleId = Db::table('config_module')->insertGetId([
            'key' => 'demo',
            'name' => '演示模块',
            'description' => '系统配置插件演示数据',
            'icon' => 'ri:experiment-line',
            'sort' => 99,
            'is_enabled' => 1,
            'definition_path' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $groupId = Db::table('config_group')->insertGetId([
            'module_id' => $moduleId,
            'key' => 'basic',
            'name' => '基础设置',
            'description' => '基础配置项',
            'icon' => '',
            'sort' => 0,
            'status' => 1,
            'definition_path' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $items = [
            ['key' => 'site_name', 'name' => '站点名称', 'type' => 'text', 'default_value' => 'BTW Admin'],
            ['key' => 'site_description', 'name' => '站点描述', 'type' => 'textarea', 'default_value' => '基于 MineAdmin 的管理系统'],
            ['key' => 'enable_registration', 'name' => '开放注册', 'type' => 'switch', 'default_value' => '1'],
            ['key' => 'max_upload_size', 'name' => '最大上传大小(MB)', 'type' => 'number', 'default_value' => '10'],
        ];

        foreach ($items as $i => $item) {
            $fieldId = Db::table('config_item')->insertGetId([
                'module_id' => $moduleId,
                'group_id' => $groupId,
                'key' => $item['key'],
                'name' => $item['name'],
                'type' => $item['type'],
                'default_value' => $item['default_value'],
                'sort' => $i,
                'status' => 1,
                'is_encrypted' => 0,
                'is_system' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            Db::table('config_value')->insert([
                'field_id' => $fieldId,
                'scope' => 'default',
                'value' => $item['default_value'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        echo "  已插入演示配置数据\n";
    }
}
