<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Middlewares\ErrorMiddleware;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Middlewares\InitMiddleware;
use ArrayAccess\TrayDigita\Cache\Entities\CacheItem;
use ArrayAccess\TrayDigita\Collection\Config;
use ArrayAccess\TrayDigita\L10n\Languages\Locale;
use ArrayAccess\TrayDigita\Logger\Entities\LogItem;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;
use function is_array;
use function is_string;

final class Service extends CoreSubmoduleAbstract
{
    protected int $priority = 9999;

    protected string $name = 'Service Initializer';

    public const LANGUAGE_COOKIE = 'language';

    /**
     * @var array<class-string<MiddlewareInterface>>
     */
    protected array $middlewares = [
        ErrorMiddleware::class,
        InitMiddleware::class
    ];

    public function getName(): string
    {
        return $this->translateContext(
            'Service Initializer',
            'core-module/service',
            'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application service run properly',
            'core-module/service',
            'module'
        );
    }

    protected function doInit(): void
    {
        $this->doRegisterMiddlewares();
        // set default language from config
        $this->doSetLanguage();
        if (Consolidation::isCli()) {
            $this
                ->getManager()
                ->attach(
                    'console.databaseBlackListOptimize',
                    [$this, 'eventBlackListedCommand']
                );
        }
    }

    /**
     * Disable optimization for logs & cache
     * @param $args
     * @return array
     */
    private function eventBlackListedCommand($args): array
    {
        $this
            ->getManager()
            ->detach(
                'console.databaseBlackListOptimize',
                [$this, 'eventBlackListedCommand']
            );
        $args = !is_array($args) ? [] : $args;
        $logItem = $this
            ->core
            ->getConnection()
            ->getEntityManager()
            ->getClassMetadata(
                LogItem::class
            )->getTableName();
        $cacheItem = $this
            ->core
            ->getConnection()
            ->getEntityManager()
            ->getClassMetadata(
                CacheItem::class
            )->getTableName();
        $args[$logItem] = true;
        $args[$cacheItem] = true;
        return $args;
    }

    /**
     * Register Middleware
     */
    private function doRegisterMiddlewares(): void
    {
        $kernel = $this->getKernel()?->getHttpKernel();
        if (!$kernel) {
            return;
        }
        foreach ($this->middlewares as $middleware) {
            try {
                $kernel->addDeferredMiddleware(
                    ContainerHelper::resolveCallable(
                        $middleware,
                        $this->getContainer(),
                        ['serviceInitializer' => $this]
                    )
                );
            } catch (Throwable) {
            }
        }
    }

    /**
     * Set language from environment
     *
     * @return void
     */
    private function doSetLanguage(): void
    {
        $env = ContainerHelper::use(Config::class, $this->getContainer())
            ->get('environment');
        $env = $env  instanceof Config ? $env : new Config();
        $language = $env->get('defaultLanguage');
        if (!is_string($language)) {
            return;
        }
        $language = Locale::normalizeLocale($language);
        if (!$language) {
            return;
        }
        $this->core->getTranslator()?->setLanguage($language);
        $this->core->getView()?->setParameter('language', $language);
    }
}
