<?php

declare(strict_types=1);

namespace Plugin\NsTicket\SystemConfig\Model;

/**
 * 配置字段模型（向后兼容别名）.
 *
 * @deprecated 请使用 ConfigItem 代替
 * @see ConfigItem
 */
class ConfigField extends ConfigItem
{
    // ConfigField 现在是 ConfigItem 的别名
    // 保持向后兼容性，旧代码可以继续使用 ConfigField
}
