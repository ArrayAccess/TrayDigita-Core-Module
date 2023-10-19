<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\CacheItem;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\LogItem;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares\ErrorMiddleware;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares\InitMiddleware;
use ArrayAccess\TrayDigita\Collection\Config;
use ArrayAccess\TrayDigita\L10n\Languages\Locale;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Psr\Http\Server\MiddlewareInterface;
use Throwable;
use function is_array;
use function is_string;

final class ServiceInitializer extends CoreSubmoduleAbstract
{
    protected int $priority = 9999;

    protected string $name = 'Service Initializer';

    const LANGUAGE_COOKIE = 'language';

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
            'module-info',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application run properly',
            'module-info',
            'core-module'
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
            $kernel->addDeferredMiddleware(new $middleware($this->getContainer(), $this));
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
