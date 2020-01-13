<?php
declare(strict_types=1);

namespace Karthus\Config;

use Karthus\Config\Listener\RegisterPropertyHandlerListener;
use Karthus\Contract\ConfigInterface;

class ConfigProvider {
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ConfigInterface::class => ConfigFactory::class,
            ],
            'listeners' => [
                RegisterPropertyHandlerListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
