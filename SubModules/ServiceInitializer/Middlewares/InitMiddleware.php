<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares;

use ArrayAccess\TrayDigita\App\Modules\Core\Core;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers\InstallController;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers\RequireModuleController;
use ArrayAccess\TrayDigita\Http\Interfaces\HttpExceptionInterface;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\TranslatorInterface;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Module\Modules;
use ArrayAccess\TrayDigita\Routing\Interfaces\RouterInterface;
use ArrayAccess\TrayDigita\Routing\MatchedRoute;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use const PHP_INT_MAX;

class InitMiddleware extends AbstractMiddleware
{
    protected int $priority = PHP_INT_MAX - 10000;

    /**
     * @throws HttpExceptionInterface
     */
    protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        // enhance views
        $container = $this->getContainer();
        $core = ContainerHelper::service(Modules::class)
            ->get(Core::class);
        $translator = $core->getTranslator()??ContainerHelper::use(TranslatorInterface::class, $container);
        $view = $core->getView();
        if ($translator) {
            $view->setRequest($request);
            $view->setParameter('language', $translator->getLanguage());
        }

        if (Consolidation::isCli()) {
            return $request;
        }

        // REGISTER CONTROLLER IF CONFIG ERRORS
        $router = $core->getKernel()->getHttpKernel()->getRouter();
        if ($core->getKernel()->getConfigError()) {
            $router->addRouteController(InstallController::class);
            return $this->doHandle($router, $request);
        }

        foreach ($core->getRequiredModules() as $module) {
            if (!$core->getModules()->has($module)) {
                $router->addRouteController(RequireModuleController::class);
                return $this->doHandle($router, $request);
            }
        }

        return $request;
    }

    /**
     * @throws HttpExceptionInterface
     */
    private function doHandle(RouterInterface $router, ServerRequestInterface $request)
    {
        $matchedRoute = $router->dispatch($request);
        if ($matchedRoute instanceof MatchedRoute) {
            return $matchedRoute->handle($request);
        }
        throw $matchedRoute;
    }
}
