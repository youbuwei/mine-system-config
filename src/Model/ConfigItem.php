<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Collection;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\Database\Model\Relations\HasMany;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Model\Model;

/**
 * 配置项模型.
 *
 * @property int $id
 * @property int $module_id 所属模块ID（冗余字段）
 * @property int $group_id 所属分组ID
 * @property string $key 配置项标识
 * @property string $name 配置项名称
 * @property string $description 配置项说明
 * @property string $type 字段类型
 * @property array $options 选项配置
 * @property array $validation 验证规则
 * @property string $default_value 默认值
 * @property string $placeholder 占位提示
 * @property string $tooltip 帮助提示
 * @property int $sort 排序
 * @property int $is_encrypted 是否加密
 * @property int $is_system 是否系统字段
 * @property int $status 状态
 * @property string $definition_path 定义文件路径
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 * @property ConfigModule $module
 * @property ConfigGroup $group
 * @property Collection|ConfigValue[] $values
 */
class ConfigItem extends Model
{
    use SoftDeletes;

    protected ?string $table = 'config_item';

    protected array $fillable = [
        'module_id', 'group_id', 'key', 'name', 'description', 'type', 'options',
        'validation', 'default_value', 'placeholder', 'tooltip',
        'sort', 'is_encrypted', 'is_system', 'status', 'definition_path',
    ];

    protected array $casts = [
        'id' => 'integer',
        'module_id' => 'integer',
        'group_id' => 'integer',
        'sort' => 'integer',
        'is_encrypted' => 'integer',
        'is_system' => 'integer',
        'status' => 'integer',
        'options' => 'array',
        'validation' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 获取完整配置路径（3级：module.group.item）.
     */
    public function getPath(): string
    {
        if ($this->relationLoaded('module') && $this->relationLoaded('group') && $this->module && $this->group) {
            return $this->module->key . '.' . $this->group->key . '.' . $this->key;
        }

        $module = $this->module_id ? ConfigModule::find($this->module_id) : null;
        $group = $this->group_id ? ConfigGroup::find($this->group_id) : null;

        $moduleKey = $module?->key ?? 'unknown';
        $groupKey = $group?->key ?? 'unknown';

        return $moduleKey . '.' . $groupKey . '.' . $this->key;
    }

    /**
     * 所属模块.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(ConfigModule::class, 'module_id', 'id');
    }

    /**
     * 所属分组.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ConfigGroup::class, 'group_id', 'id');
    }

    /**
     * 配置值.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ConfigValue::class, 'field_id', 'id');
    }

    /**
     * 是否需要加密.
     */
    public function isEncrypted(): bool
    {
        return $this->is_encrypted === 1;
    }

    /**
     * 是否系统字段.
     */
    public function isSystem(): bool
    {
        return $this->is_system === 1;
    }

    /**
     * 是否启用.
     */
    public function isEnabled(): bool
    {
        return $this->status === 1;
    }
}
