<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Database\Seeders;

use Hyperf\DbConnection\Db;

/**
 * 配置权限Seeder.
 */
class ConfigPermissionSeeder
{
    public function run(): void
    {
        $permissions = [
            ['code' => 'config:list', 'name' => '查看配置', 'sort' => 1],
            ['code' => 'config:update', 'name' => '更新配置', 'sort' => 2],
        ];

        foreach ($permissions as $perm) {
            Db::table('permission')->updateOrInsert(
                ['code' => $perm['code']],
                [
                    'name' => $perm['name'],
                    'sort' => $perm['sort'],
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]
            );
        }
    }
}
