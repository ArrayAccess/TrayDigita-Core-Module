<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares;

use ArrayAccess\TrayDigita\Middleware\ErrorMiddleware as CoreErrorMiddleware;
use const PHP_INT_MAX;

class ErrorMiddleware extends CoreErrorMiddleware
{
    /**
     * @var int set as highest priority
     */
    protected int $priority = PHP_INT_MAX;
}
