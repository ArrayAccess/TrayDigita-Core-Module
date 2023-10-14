<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares;

use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers\InstallController;
use ArrayAccess\TrayDigita\Kernel\Interfaces\KernelInterface;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\TranslatorInterface;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Routing\Interfaces\RouterInterface;
use ArrayAccess\TrayDigita\Routing\MatchedRoute;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use const PHP_INT_MAX;

class InitMiddleware extends AbstractMiddleware
{
    protected int $priority = PHP_INT_MAX - 10000;

    protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        // enhance views
        $container = $this->getContainer();
        $translator = ContainerHelper::use(TranslatorInterface::class, $container);
        $view = ContainerHelper::use(ViewInterface::class, $container);
        if ($translator) {
            $view?->setRequest($request);
            $view?->setParameter(
                'language',
                $translator->getLanguage()
            );
        }
        $kernel = ContainerHelper::use(KernelInterface::class, $container);
        if (!$kernel->getConfigError() || Consolidation::isCli()) {
            return $request;
        }
        // REGISTER CONTROLLER IF CONFIG ERRORS
        $router = ContainerHelper::service(RouterInterface::class, $container);
        $router->addRouteController(InstallController::class);
        $matchedRoute = $router->dispatch($request);
        if ($matchedRoute instanceof MatchedRoute) {
            return $matchedRoute->handle($request);
        }
        throw $matchedRoute;
    }
}
