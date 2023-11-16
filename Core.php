<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Benchmarks\CoreModuleAggregator;
use ArrayAccess\TrayDigita\App\Modules\Core\Benchmarks\CoreModuleBenchmarkSubscriber;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Assets\Assets;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Scheduler\Scheduler;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Service;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Templates;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Translator\Translator;
use ArrayAccess\TrayDigita\App\Modules\Media\Entities\Attachment;
use ArrayAccess\TrayDigita\App\Modules\Media\Entities\UserAttachment;
use ArrayAccess\TrayDigita\App\Modules\Media\Media;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\AdminLog;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\AdminMeta;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\AdminOnlineActivity;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\Options;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\User as UserEntity;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserLog;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserMeta;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserOnlineActivity;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserTerm;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserTermGroup;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserTermGroupMeta;
use ArrayAccess\TrayDigita\App\Modules\Users\Entities\UserTermMeta;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Benchmark\Aggregator\EventAggregator;
use ArrayAccess\TrayDigita\Benchmark\Injector\ManagerProfiler;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Module\AbstractModule;
use ArrayAccess\TrayDigita\Module\Interfaces\ModuleInterface;
use ArrayAccess\TrayDigita\Traits\Database\ConnectionTrait;
use ArrayAccess\TrayDigita\Traits\Service\TranslatorTrait;
use ArrayAccess\TrayDigita\Traits\View\ViewTrait;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use Doctrine\DBAL\Exception;
use function array_flip;
use function array_map;
use function class_exists;
use function dirname;
use function strtolower;
use function var_dump;
use const PHP_INT_MIN;

final class Core extends AbstractModule
{
    use TranslatorTrait,
        ViewTrait,
        ConnectionTrait;

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
    final public const MODULES = [
        Assets::class,
        Scheduler::class,
        Service::class,
        Templates::class,
        Translator::class,
    ];

    final public const ENTITY_CHECKING = [
        'required' => [
            Admin::class,
            AdminLog::class,
            AdminMeta::class,
            AdminOnlineActivity::class,
            Attachment::class,
            Options::class,
            UserEntity::class,
            UserAttachment::class,
            UserLog::class,
            UserMeta::class,
            UserOnlineActivity::class,
            UserTerm::class,
            UserTermGroup::class,
            UserTermGroupMeta::class,
            UserTermMeta::class,
        ],
        'optional' => [
        ],
        'additional' => [
        ]
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
            'core-module',
            'module'
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
            'core-module',
            'module'
        );
    }

    protected function doInit(): void
    {
        if ($this->didInit) {
            return;
        }
        Consolidation::registerAutoloader(__NAMESPACE__, __DIR__);
        $this->didInit = true;
        $kernel = $this->getKernel();
        $kernel->registerControllerDirectory(__DIR__ .'/Controllers');
        $this->getTranslator()?->registerDirectory('module', __DIR__ . '/Languages');
        $this->getConnection()->registerEntityDirectory(__DIR__ . '/Entities');

        $this->doRegisterSubModules();
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

    /**
     * @var ?array
     */
    private ?array $entityChecking = null;

    /**
     * @throws Exception
     * @return array{
     *     required: array<string, bool>,
     *     optionsl: array<string, bool>,
     *     additional: array<string, bool>,
     *     tables: array<string, string>,
     * }
     */
    public function checkEntity(): array
    {
        if ($this->entityChecking !== null) {
            return $this->entityChecking;
        }

        $em = $this->getEntityManager();
        $tables = $this->getConnection()->createSchemaManager()->listTableNames();
        $tables = array_flip(array_map('strtolower', $tables));
        $this->entityChecking = [];
        foreach (self::ENTITY_CHECKING as $type => $entities) {
            $this->entityChecking[$type] = [];
            foreach ($entities as $entity) {
                $table = $em->getClassMetadata($entity)->getTableName();
                $this->entityChecking[$type][$entity] = isset($tables[strtolower($table)]);
                $this->entityChecking['tables'][$entity] = $table;
            }
        }

        return $this->entityChecking;
    }
}
