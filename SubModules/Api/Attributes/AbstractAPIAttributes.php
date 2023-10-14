<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Api\Attributes;

use ArrayAccess\TrayDigita\Kernel\Decorator;
use function is_string;

abstract class AbstractAPIAttributes extends RouteAPI
{
    public static function subPrefix(): string
    {
        $manager = Decorator::manager();
        $subPrefixOriginal = trim(static::API_SUB_PREFIX, '/');
        $subPrefix = $manager->dispatch(
            'apiRoute.subPrefix',
            $subPrefixOriginal,
            static::class
        );
        return trim(is_string($subPrefix) ? $subPrefix : $subPrefixOriginal, '/');
    }
}
