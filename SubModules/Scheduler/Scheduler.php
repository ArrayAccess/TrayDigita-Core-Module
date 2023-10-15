<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Scheduler;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Scheduler\Scheduler as CoreScheduler;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;

/**
 * Module scheduler
 * @uses \ArrayAccess\TrayDigita\Kernel\Decorator::module(Scheduler::class);
 */
final class Scheduler extends CoreSubmoduleAbstract
{

    protected CoreScheduler $scheduler;

    protected string $name = 'Database Scheduler';

    public function getName(): string
    {
        return $this->translateContext(
            'Database Scheduler',
            'module',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application support database storage record for scheduler',
            'module',
            'core-module'
        );
    }

    protected function doInit(): void
    {
        $this->scheduler = ContainerHelper::service(
            CoreScheduler::class,
            $this->getContainer()
        );

        $this->scheduler->setRecordLoader(
            new EntityLoader(
                ContainerHelper::service(
                    Connection::class,
                    $this->getContainer()
                )
            )
        );
    }

    public function getScheduler(): CoreScheduler
    {
        return $this->scheduler;
    }
}
