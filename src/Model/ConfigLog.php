<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Model;

use Carbon\Carbon;
use Hyperf\Database\Model\Relations\BelongsTo;
use Hyperf\DbConnection\Model\Model;

/**
 * 配置变更日志模型.
 *
 * @property int $id
 * @property int $field_id 字段ID
 * @property string $scope 作用域
 * @property string $old_value 旧值
 * @property string $new_value 新值
 * @property int $changed_by 操作人
 * @property Carbon $changed_at 操作时间
 * @property string $ip 操作IP
 * @property ConfigItem $field
 */
class ConfigLog extends Model
{
    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected ?string $table = 'config_log';

    protected array $fillable = [
        'field_id', 'scope', 'old_value', 'new_value', 'changed_by', 'changed_at', 'ip',
    ];

    protected array $casts = [
        'id' => 'integer',
        'field_id' => 'integer',
        'changed_by' => 'integer',
        'changed_at' => 'datetime',
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
