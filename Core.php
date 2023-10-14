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
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Media\Media;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Option\Option;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\Posts;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Quiz\Quiz;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Roles\Roles;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Scheduler\Scheduler;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\ServiceInitializer;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Templates;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Translator\Translator;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Users\Users;
use ArrayAccess\TrayDigita\Benchmark\Aggregator\EventAggregator;
use ArrayAccess\TrayDigita\Benchmark\Injector\ManagerProfiler;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Module\AbstractModule;
use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use function class_exists;
use function strtolower;
use const PHP_INT_MIN;

final class Core extends AbstractModule
{
    use TranslatorTrait;

    protected string $name = 'Core';

    /**
     * @var bool
     */
    protected bool $important = true;

    /**
     * @var int -> very important
     */
    protected int $priority = PHP_INT_MIN;

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
        Media::class,
        Option::class,
        Posts::class,
        Quiz::class,
        Roles::class,
        Scheduler::class,
        ServiceInitializer::class,
        Templates::class,
        Translator::class,
        Users::class,
    ];

    /**
     * @var array<string, CoreSubmoduleAbstract>
     */
    private array $subModules = [];

    /**
     * @var array<string, int>
     */
    private array $priorities = [];

    public function getName(): string
    {
        return $this->translate(
            'Core',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Main core module',
            context: 'module'
        );
    }

    protected function doInit(): void
    {
        if ($this->didInit) {
            return;
        }

        $this->didInit = true;

        $this->doRegisterEntities();

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
     * Register entities, change from sub modules to core module
     * and prevent longer latencies
     * @return void
     */
    private function doRegisterEntities(): void
    {
        $metadata = ContainerHelper::use(Connection::class, $this->getContainer())
            ?->getDefaultConfiguration()
            ->getMetadataDriverImpl();
        if ($metadata instanceof AttributeDriver) {
            $metadata->addPaths([
                __DIR__ . '/Entities'
            ]);
        }
    }

    private function doInitSubModules(): void
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
                $manager->dispatch('coreModule.beforeInitModule', $this, $module, $p);
                try {
                    $this->subModules[$module]->init();
                    // @detach(coreModule.initModule)
                    $manager->dispatch('coreModule.initModule', $this, $module, $p);
                } finally {
                    // @detach(coreModule.afterInitModule)
                    $manager->dispatch('coreModule.afterInitModule', $this, $module, $p);
                }
            }
            // @detach(coreModule.afterInitModules)
            $manager->dispatch('coreModule.initModules', $this);
            unset($this->subModules, $this->priorities);
        } finally {
            // @detach(coreModule.afterInitModules)
            $manager->dispatch('coreModule.afterInitModules', $this);
        }
    }
}
