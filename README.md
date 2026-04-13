# youbuwei/system-config

系统配置管理插件 - 支持模块化 3 级配置体系（模块 > 分组 > 配置项）。

## 功能

- 3 级配置体系：模块 → 分组 → 配置项
- 多种字段类型：text、textarea、number、switch、select、multi-select
- Redis 缓存支持
- 配置变更日志
- 文件定义 + 数据库存储
- 配额预占配置

## 安装

```bash
php bin/hyperf.php mine-extension:install youbuwei/system-config --yes
```

## 卸载

```bash
php bin/hyperf.php mine-extension:uninstall youbuwei/system-config --yes
```

## API 端点

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /admin/config/modules | 获取配置模块树 |
| GET | /admin/config/modules/{key}/groups | 获取模块分组 |
| GET | /admin/config/modules/{key}/values | 获取模块配置值 |
| POST | /admin/config/modules/{key}/values | 更新模块配置值 |
| GET | /admin/config/groups | 获取配置分组树 |
| GET | /admin/config/groups/{id}/fields | 获取分组字段 |
| GET | /admin/config/values | 获取配置值 |
| GET | /admin/config/values/{path} | 获取单个配置值 |
| PUT | /admin/config/values/{path} | 更新单个配置值 |
| POST | /admin/config/values/batch | 批量更新配置值 |
| POST | /admin/config/sync | 同步配置定义 |
| POST | /admin/config/cache/refresh | 刷新缓存 |

## 项目结构

```
├── mine.json                        # 插件核心配置
├── README.md                        # 说明文档
├── src/                             # 后端源码
│   ├── ConfigProvider.php           # Hyperf 配置提供者
│   ├── InstallScript.php            # 安装脚本
│   ├── UninstallScript.php          # 卸载脚本
│   ├── Command/
│   │   └── ConfigSyncCommand.php    # 配置同步命令
│   ├── Controller/
│   │   └── ConfigController.php     # 配置控制器
│   ├── Model/                       # 数据模型
│   ├── Repository/                  # 数据仓库
│   └── Service/                     # 业务服务
├── Database/
│   ├── Migrations/                  # 数据库迁移
│   └── Seeders/                     # 数据填充
├── publish/                         # 发布资源
│   └── modules/                     # 配置模块定义文件
└── web/                             # 前端源码
    ├── api/                         # API 接口
    └── views/                       # Vue 组件
```
