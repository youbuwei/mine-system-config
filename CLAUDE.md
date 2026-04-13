# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

MineAdmin plugin (`youbuwei/system-config`) providing a modular 3-level configuration management system (Module > Group > Config Item). Uses file-based definitions synced to database storage with Redis caching.

## Commands

```bash
# Install plugin into MineAdmin host project
php bin/hyperf.php mine-extension:install youbuwei/system-config --yes

# Uninstall
php bin/hyperf.php mine-extension:uninstall youbuwei/system-config --yes

# Sync config module definitions (scan files → database)
php bin/hyperf.php config:sync

# Dry-run sync (scan only, no writes)
php bin/hyperf.php config:sync --dry-run
```

## Architecture

### Plugin Namespace

All PHP code lives under `Plugin\Youbuwei\SystemConfig\`. The namespace maps to `src/` via PSR-4 autoloading configured in `mine.json`.

### Core Hierarchy

```
ConfigModule (config_module table)
  └── ConfigGroup (config_group table, FK: module_id)
        └── ConfigItem (config_item table, FK: module_id, group_id)
              └── ConfigValue (config_value table, FK: field_id, scoped)
```

- **ConfigModule**: Top-level container (e.g., "order", "payment")
- **ConfigGroup**: Logical grouping within a module (e.g., "basic", "advanced")
- **ConfigItem**: Individual config parameter with type, validation, default value
- **ConfigValue**: Stored values per scope ("default", tenant-specific, etc.)
- **ConfigField**: Alias of ConfigItem for backward compatibility

### Key Services

- **ConfigService**: Central business logic. Handles CRUD, batch updates, value parsing (type coercion), and legacy 2-level path compatibility.
- **ConfigCacheService**: Redis caching with prefix `youbuwei:config:`. Values cached per scope+path. Tree queries cached with `remember()` pattern.
- **ConfigModuleRegistry**: Scans `config/modules/*.php` definition files and upserts them into the database via `updateOrCreate`. Called by `config:sync` command and `/admin/config/sync` endpoint.

### File-based Config Definitions

`publish/modules/*.php` files define the configuration structure as PHP arrays:

```php
return [
    'module' => ['key' => 'order', 'name' => '...', 'icon' => '...', 'sort' => 1],
    'groups' => [
        ['key' => 'basic', 'name' => '...', 'items' => [
            ['key' => 'timeout', 'type' => 'number', 'default_value' => '30', ...],
        ]],
    ],
];
```

On install, these files are copied to `config/modules/`. The registry scans them to populate the database.

### Controller Pattern

`ConfigController` uses Hyperf annotations:
- `#[Controller(prefix: 'admin/config')]` for route grouping
- `#[Middleware(AccessTokenMiddleware::class)]` for auth
- `#[Permission(code: ['config:list'])]` / `#[Permission(code: ['config:update'])]` for authorization
- `#[ResultResponse]` with MineAdmin's `Result` wrapper for responses

### Frontend

- `web/views/config/` — Vue 3 components with Element Plus
- `web/api/config.ts` — TypeScript API client
- URL query params track active module/group selection
- Collapsible panels per group, batch save via "Save All"

### Plugin Lifecycle

- **ConfigProvider**: Registers annotation scan paths for Hyperf DI
- **InstallScript**: Copies `publish/modules/*.php` to `config/modules/`
- **UninstallScript**: Removes published files, clears Redis cache (`youbuwei:config:*`)

## Conventions

- All models use `SoftDeletes`
- Repositories extend `App\Repository\IRepository` with `handleSearch()` for query filtering
- Value types are parsed in `ConfigService::parseValue()` — add new type handling there
- Encrypted fields use base64 encoding in `ConfigService::encrypt()`
- Config paths follow `module_key.group_key.item_key` dot notation
- Legacy 2-level paths (`group.item`) auto-converted via `convertLegacyPath()`
