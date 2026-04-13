<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * 配置模块模型.
 *
 * @property int $id
 * @property string $key 模块标识
 * @property string $name 模块名称
 * @property string $description 模块描述
 * @property string $icon 图标
 * @property int $sort 排序
 * @property int $is_enabled 是否启用
 * @property string $definition_path 定义文件路径
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property Collection|ConfigGroup[] $groups
 */
class ConfigModule extends Model
{
    use SoftDeletes;

    protected ?string $table = 'config_module';

    protected array $fillable = [
        'key', 'name', 'description', 'icon', 'sort',
        'is_enabled', 'definition_path',
    ];

    protected array $casts = [
        'id' => 'integer',
        'sort' => 'integer',
        'is_enabled' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取模块下的分组.
     */
    public function groups(): HasMany
    {
        return $this->hasMany(ConfigGroup::class, 'module_id', 'id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    /**
     * 是否启用.
     */
    public function isEnabled(): bool
    {
        return $this->is_enabled === 1;
    }

    /**
     * 获取模块的所有配置项（通过分组）.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ConfigItem::class, 'module_id', 'id')
            ->orderBy('sort')
            ->orderBy('id');
    }
}
