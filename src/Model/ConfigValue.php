<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * 配置值模型.
 *
 * @property int $id
 * @property int $field_id 字段ID
 * @property string $scope 作用域
 * @property string $value 配置值
 * @property int $created_by 创建人
 * @property int $updated_by 最后更新人
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property ConfigItem $field
 */
class ConfigValue extends Model
{
    protected ?string $table = 'config_value';

    protected array $fillable = [
        'field_id', 'scope', 'value', 'created_by', 'updated_by',
    ];

    protected array $casts = [
        'id' => 'integer',
        'field_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * 所属配置项（向后兼容）.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(ConfigItem::class, 'field_id', 'id');
    }

    /**
     * 所属配置项（新方法）.
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(ConfigItem::class, 'field_id', 'id');
    }
}
