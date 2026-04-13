<?php

declare(strict_types=1);
return [
    // 模块元数据
    'module' => [
        'key' => 'order',
        'name' => '订单配置',
        'description' => '订单相关配置，包括超时时间、自动确认等',
        'icon' => 'icon-park-outline:transaction-order',
        'sort' => 1,
    ],

    // 配置分组
    'groups' => [
        [
            'key' => 'basic',
            'name' => '基础设置',
            'description' => '订单基础配置',
            'icon' => 'icon-park-outline:settings',
            'sort' => 1,
            'items' => [
                [
                    'key' => 'timeout_minutes',
                    'name' => '订单超时时间',
                    'description' => '订单创建后未支付自动取消的时间（分钟）',
                    'type' => 'number',
                    'default_value' => '30',
                    'validation' => ['min' => 1, 'max' => 1440, 'required' => true],
                    'tooltip' => '建议设置为 15-60 分钟',
                    'sort' => 1,
                ],
                [
                    'key' => 'auto_confirm_days',
                    'name' => '自动确认天数',
                    'description' => '订单完成后自动确认收货的天数',
                    'type' => 'number',
                    'default_value' => '7',
                    'validation' => ['min' => 1, 'max' => 30, 'required' => true],
                    'sort' => 2,
                ],
                [
                    'key' => 'auto_cancel_enabled',
                    'name' => '启用自动取消',
                    'description' => '是否启用订单自动取消功能',
                    'type' => 'switch',
                    'default_value' => '1',
                    'sort' => 3,
                ],
            ],
        ],
        [
            'key' => 'advanced',
            'name' => '高级设置',
            'description' => '订单高级配置',
            'icon' => 'icon-park-outline:config',
            'sort' => 2,
            'items' => [
                [
                    'key' => 'max_items_per_order',
                    'name' => '单订单最大商品数',
                    'description' => '单个订单最多包含的商品数量',
                    'type' => 'number',
                    'default_value' => '20',
                    'validation' => ['min' => 1, 'max' => 100],
                    'sort' => 1,
                ],
                [
                    'key' => 'allow_partial_refund',
                    'name' => '允许部分退款',
                    'description' => '订单是否允许部分商品退款',
                    'type' => 'switch',
                    'default_value' => '1',
                    'sort' => 2,
                ],
                [
                    'key' => 'refund_deadline_days',
                    'name' => '退款截止天数',
                    'description' => '订单完成后可申请退款的天数（0表示不限）',
                    'type' => 'number',
                    'default_value' => '15',
                    'validation' => ['min' => 0, 'max' => 365],
                    'sort' => 3,
                ],
            ],
        ],
    ],
];
