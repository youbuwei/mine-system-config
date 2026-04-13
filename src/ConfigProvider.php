<?php

declare(strict_types=1);

namespace Plugin\Youbuwei\SystemConfig;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'swagger' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
