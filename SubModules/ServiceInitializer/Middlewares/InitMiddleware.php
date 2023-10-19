<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares;

use ArrayAccess\TrayDigita\App\Modules\Core\Core;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Option as OptionEntity;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Option\Option;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers\InstallController;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers\RequireModuleController;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\ServiceInitializer;
use ArrayAccess\TrayDigita\Http\Interfaces\HttpExceptionInterface;
use ArrayAccess\TrayDigita\Http\SetCookie;
use ArrayAccess\TrayDigita\L10n\Languages\Locale;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\TranslatorInterface;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Routing\Interfaces\RouterInterface;
use ArrayAccess\TrayDigita\Routing\MatchedRoute;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Doctrine\DBAL\Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function array_filter;
use function is_string;
use const PHP_INT_MAX;

class InitMiddleware extends AbstractMiddleware
{
    protected int $priority = PHP_INT_MAX - 1000;

    public function __construct(
        ContainerInterface $container,
        public readonly ServiceInitializer $serviceInitializer
    ) {
        parent::__construct($container);
    }

    /**
     * @throws HttpExceptionInterface|Exception
     */
    protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        if (Consolidation::isCli()) {
            return $request;
        }

        // REGISTER CONTROLLER IF CONFIG ERRORS
        $router = $this->serviceInitializer->getKernel()->getHttpKernel()->getRouter();
        foreach ($this->serviceInitializer->core->getRequiredModules() as $module) {
            if (!$this->serviceInitializer->core->getModules()->has($module)) {
                $router->addRouteController(RequireModuleController::class);
                return $this->doHandle($router, $request);
            }
        }

        if ($this->serviceInitializer->core->getKernel()->getConfigError()) {
            $router->addRouteController(InstallController::class);
            return $this->doHandle($router, $request);
        }
        $countRequired = count(Core::ENTITY_CHECKING['required']);
        $entities = array_filter(
            $this->serviceInitializer->core->checkEntity()['required'],
            static fn ($e) => $e === false
        );
        if ($countRequired === count($entities)) {
            $router->addRouteController(InstallController::class);
            return $this->doHandle($router, $request);
        }
        $option = $this->serviceInitializer->getModule(Option::class);
        $translator = $this
            ->serviceInitializer
            ->core
            ->getTranslator() ?? ContainerHelper::use(
                TranslatorInterface::class,
                $this->getContainer()
            );
        $optionLanguage = $option?->get('language');
        $language       = $optionLanguage?->getValue();
        $language = $option && $language ? Locale::normalizeLocale($language) : null;
        if (!$language) {
            $optionLanguage ??= $option
                ?->createNewOptionEntityObject('language')
                ??new OptionEntity();
            $language = $translator?->getLanguage()??$this
                ->serviceInitializer
                ->core
                ->getView()?->getParameter('language');
            $optionLanguage->setName('language');
            $language = is_string($language) ? Locale::normalizeLocale($language) : 'en';
            $optionLanguage->setValue($language);
            $em = $this->serviceInitializer->core->getConnection()->getEntityManager();
            $optionLanguage->setEntityManager($em);
            $em->persist($optionLanguage);
            $em->flush();
        }

        // set user language
        $originSelectedLanguage = $request->getCookieParams()[ServiceInitializer::LANGUAGE_COOKIE]??null;
        $selectedLanguage = $language;
        if ($originSelectedLanguage) {
            $selectedLanguage = Locale::normalizeLocale($originSelectedLanguage)??$language;
        }
        if ($originSelectedLanguage !== $selectedLanguage) {
            $this->getManager()?->attach(
                'response.final',
                static function ($response) use ($language) {
                    if (!$response instanceof ResponseInterface) {
                        return $response;
                    }
                    return $response->withAddedHeader(
                        'Set-Cookie',
                        (string) new SetCookie(
                            ServiceInitializer::LANGUAGE_COOKIE,
                            $language,
                            path: '/'
                        )
                    );
                }
            );
        }

        $translator?->setLanguage($selectedLanguage);
        $view = $this->serviceInitializer->core->getView();
        $view->setRequest($request);
        $view->setParameter('language', $selectedLanguage);
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
