<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares;

use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\ServiceInitializer;
use ArrayAccess\TrayDigita\Middleware\ErrorMiddleware as CoreErrorMiddleware;
use Psr\Container\ContainerInterface;
use const PHP_INT_MAX;

class ErrorMiddleware extends CoreErrorMiddleware
{
    /**
     * @var int set as highest priority
     */
    protected int $priority = PHP_INT_MAX;

    public function __construct(
        ContainerInterface $container,
        public readonly ServiceInitializer $serviceInitializer
    ) {
        parent::__construct($container);
    }
}
