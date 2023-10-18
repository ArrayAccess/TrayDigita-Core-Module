<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Benchmarks\CoreModuleAggregator;
use ArrayAccess\TrayDigita\App\Modules\Core\Benchmarks\CoreModuleBenchmarkSubscriber;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Announcement\Announcement;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Api\Api;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Assets\Assets;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Controllers\Controllers;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\EducationalInstitution\EducationalInstitution;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Library\Library;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Option\Option;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\Posts;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Quiz\Quiz;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Scheduler\Scheduler;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\ServiceInitializer;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Templates;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Translator\Translator;
use ArrayAccess\TrayDigita\App\Modules\Media\Media;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Benchmark\Aggregator\EventAggregator;
use ArrayAccess\TrayDigita\Benchmark\Injector\ManagerProfiler;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\HttpKernel\BaseKernel;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\AdapterBasedFileInterface;
use ArrayAccess\TrayDigita\Module\AbstractModule;
use ArrayAccess\TrayDigita\Module\Interfaces\ModuleInterface;
use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use function class_exists;
use function strtolower;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_MIN;

final class Core extends AbstractModule
{
    use TranslatorTrait;

    /**
     * @var string
     */
    protected string $name = 'Core';

    /**
     * @var bool
     */
    protected bool $important = true;

    /**
     * @var int -> very important
     */
    protected int $priority = PHP_INT_MIN;

    /**
     * @var bool
     */
    private bool $didInit = false;

    /**
     * @var class-string<CoreSubmoduleAbstract>
     */
    const MODULES = [
        Announcement::class,
        Api::class,
        Assets::class,
        Controllers::class,
        EducationalInstitution::class,
        Library::class,
        Option::class,
        Posts::class,
        Quiz::class,
        Scheduler::class,
        ServiceInitializer::class,
        Templates::class,
        Translator::class,
    ];

    /**
     * @var array<string, CoreSubmoduleAbstract>
     */
    private array $subModules = [];

    /**
     * @var array<string, int>
     */
    private array $priorities = [];

    /**
     * @var ViewInterface
     */
    private ViewInterface $view;

    /**
     * @var ?Connection
     */
    private ?Connection $connection = null;

    /**
     * @var array<class-string<ModuleInterface>
     */
    private array $requiredModules = [
        Users::class,
        Media::class,
    ];

    public function getName(): string
    {
        return $this->translateContext(
            'Core',
            'module-info',
            'core-module'
        );
    }

    /**
     * @return array<class-string<ModuleInterface>
     */
    public function getRequiredModules(): array
    {
        return $this->requiredModules;
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Main core module',
            'module-info',
            'core-module'
        );
    }

    protected function doInit(): void
    {
        if ($this->didInit) {
            return;
        }

        $this->didInit = true;
        foreach ($this->getTranslator()?->getAdapters()??[] as $adapter) {
            if ($adapter instanceof AdapterBasedFileInterface) {
                $adapter->registerDirectory(
                    __DIR__ .'/Languages',
                    'core-module'
                );
            }
        }

        // register autoloader
        Consolidation::registerAutoloader(__NAMESPACE__, __DIR__);
        $this->view = ContainerHelper::service(ViewInterface::class, $this->getContainer());
        $this->doOverrideController();
        $this->doRegisterEntities();
        $this->doRegisterSubModules();
    }

    private function doOverrideController(): void
    {
        $factoryControllerNamespace = null;
        $kernel = $this->getKernel();
        (function (Core $core) use (&$factoryControllerNamespace) {
            $kernel = $core->getKernel();
            if (!$kernel instanceof BaseKernel) {
                return;
            }
            $factoryControllerNamespace = $core->getKernel()->getControllerNameSpace();
            $controllerNamespace = __NAMESPACE__ .'\\Controller\\';
            $controllerDir = __DIR__ . DIRECTORY_SEPARATOR . 'Controllers';
            Consolidation::registerAutoloader($controllerNamespace, $controllerDir);
            $this->{'controllerNameSpace'} = $controllerNamespace;
            $this->{'registeredDirectories'}[$controllerNamespace] = $controllerDir;
        })->call($kernel, $this);
        if (!$factoryControllerNamespace) {
            return;
        }
        $manager = $this->getManager();
        $idBefore = $manager->attach(
            'console.beforeConfigureCommands',
            static function ($e) use (&$idBefore, $factoryControllerNamespace, $manager, $kernel) {
                $manager->detachByEventNameId(
                    'console.beforeConfigureCommands',
                    $idBefore
                );
                (function ($factoryControllerNamespace) {
                    $this->{'controllerNameSpace'} = $factoryControllerNamespace;
                })->call($kernel, $factoryControllerNamespace);
                return $e;
            }
        );
        $idAfter = $manager->attach(
            'console.afterConfigureCommands',
            static function ($e) use (&$idAfter, $manager, $kernel) {
                $manager->detachByEventNameId(
                    'console.afterConfigureCommands',
                    $idAfter
                );
                (function () {
                    $this->{'controllerNameSpace'} = __NAMESPACE__ .'\\Controller\\';
                })->call($kernel);
                return $e;
            }
        );
    }

    /**
     * @return ViewInterface
     */
    public function getView(): ViewInterface
    {
        return $this->view;
    }

    public function getConnection(): Connection
    {
        return $this->connection ??= ContainerHelper::service(Connection::class, $this->getContainer());
    }

    /**
     * Register entities, change from sub modules to core module
     * and prevent longer latencies
     * @return void
     */
    private function doRegisterEntities(): void
    {
        $metadata = $this->getConnection()
            ->getDefaultConfiguration()
            ->getMetadataDriverImpl();
        if ($metadata instanceof AttributeDriver) {
            $metadata->addPaths([
                __DIR__ . '/Entities'
            ]);
        }
    }

    /**
     * Register submodules
     *
     * @return void
     */
    private function doRegisterSubModules(): void
    {
        $manager = $this->getManager();
        $listener = $manager->getDispatchListener();
        if ($listener instanceof ManagerProfiler
            && ($profiler = $listener->getProfiler())->isEnable()
        ) {
            $listener->prepend((new CoreModuleBenchmarkSubscriber($listener))->setCoreModule($this));
            foreach ($profiler->getAggregators() as $aggregator) {
                if ($aggregator instanceof EventAggregator) {
                    $aggregator->addBlacklistedGroup('coreModule');
                    break;
                }
            }
            $profiler->addAggregator(
                (new CoreModuleAggregator($profiler))->setCoreModule($this)
            );
            $profiler = null;
            unset($profiler);
        }

        // internal injection
        $bind = (function (
            string $name,
            int $priority,
            CoreSubmoduleAbstract $module
        ) {
            $this->{'inits'}[$name] = true;
            $this->{'priorityRecords'}[$name] = $priority;
            $this->{'modules'}[$name] = $module;
        });

        // @dispatch(coreModule.beforeRegisterModules)
        $manager->dispatch('coreModule.beforeRegisterModules', $this);
        try {
            /**
             * @var class-string<CoreSubmoduleAbstract> $module
             */
            foreach (self::MODULES as $module) {
                if (!class_exists($module)) {
                    continue;
                }
                // @dispatch(coreModule.beforeRegisterModule)
                $manager->dispatch(
                    'coreModule.beforeRegisterModule',
                    $module,
                    $this
                );
                try {
                    $name = strtolower($module);
                    $module = new $module($this->modules, $this);
                    $this->priorities[$name] = $module->getPriority();
                    $this->subModules[$name] = $module;
                    $bind->call(
                        $this->modules,
                        $name,
                        $this->priorities[$name],
                        $module
                    );
                    // @dispatch(coreModule.registerModule)
                    $manager->dispatch(
                        'coreModule.registerModule',
                        $module::class,
                        $this
                    );
                } finally {
                    // @dispatch(coreModule.afterRegisterModule)
                    $manager->dispatch(
                        'coreModule.afterRegisterModule',
                        $module::class,
                        $this
                    );
                }
            }

            unset($bind);
            // @attach(kernel.afterInitModules)
            $manager->attach(
                'kernel.initModules',
                [$this, 'doInitSubModules'],
                0
            );
            // @dispatch(coreModule.registerModules)
            $manager->dispatch('coreModule.registerModules', $this);
        } finally {
            // @dispatch(coreModule.afterRegisterModules)
            $manager->dispatch('coreModule.afterRegisterModules', $this);
        }
    }

    /**
     * Doing init modules
     */
    private function doInitSubModules($modules)
    {
        $manager = $this->getManager();
        // @detach(kernel.afterInitModules)
        $manager->detach(
            'kernel.initModules',
            [$this, 'initModules'],
            0
        );

        // @detach(coreModule.beforeInitModules)
        $manager->dispatch('coreModule.beforeInitModules', $this);
        try {
            foreach ($this->priorities as $module => $p) {
                // @detach(coreModule.beforeInitModules)
                $manager->dispatch('coreModule.beforeInitModule', $this, $this->subModules[$module], $p);
                try {
                    $this->subModules[$module]->init();
                    // @detach(coreModule.initModule)
                    $manager->dispatch('coreModule.initModule', $this, $this->subModules[$module], $p);
                } finally {
                    // @detach(coreModule.afterInitModule)
                    $manager->dispatch('coreModule.afterInitModule', $this, $this->subModules[$module], $p);
                }
                unset($this->subModules[$module]);
            }
            // @detach(coreModule.afterInitModules)
            $manager->dispatch('coreModule.initModules', $this);
            unset($this->subModules, $this->priorities);
        } finally {
            // @detach(coreModule.afterInitModules)
            $manager->dispatch('coreModule.afterInitModules', $this);
        }
        return $modules;
    }
}
