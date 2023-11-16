<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Middlewares;

use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Service;
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
        public readonly Service $serviceInitializer,
        ?bool $displayErrorDetails = null
    ) {
        parent::__construct($container, $displayErrorDetails);
    }
}
