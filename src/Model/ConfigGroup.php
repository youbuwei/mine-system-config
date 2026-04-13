<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * 配置分组模型.
 *
 * @property int $id
 * @property int $module_id 所属模块ID
 * @property string $key 分组标识
 * @property string $name 分组名称
 * @property string $description 分组描述
 * @property string $icon 图标
 * @property int $sort 排序
 * @property int $status 状态
 * @property string $definition_path 定义文件路径
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property ConfigModule $module
 * @property Collection|ConfigItem[] $items
 */
class ConfigGroup extends Model
{
    use SoftDeletes;

    protected ?string $table = 'config_group';

    protected array $fillable = [
        'module_id', 'key', 'name', 'description', 'icon', 'sort', 'status',
        'definition_path',
    ];

    protected array $casts = [
        'id' => 'integer',
        'module_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取所属模块.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(ConfigModule::class, 'module_id', 'id');
    }

    /**
     * 获取分组下的配置项.
     */
    public function items(): HasMany
    {
        return $this->hasMany(ConfigItem::class, 'group_id', 'id')
            ->orderBy('sort')
            ->orderBy('id');
    }

    /**
     * 兼容旧代码：获取分组下的字段（等同于 items）.
     * @deprecated 请使用 items() 方法
     */
    public function fields(): HasMany
    {
        return $this->items();
    }

    /**
     * 是否启用.
     */
    public function isEnabled(): bool
    {
        return $this->status === 1;
    }
}
