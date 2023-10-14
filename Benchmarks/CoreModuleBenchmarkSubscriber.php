<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Benchmarks;

use ArrayAccess\TrayDigita\App\Modules\Core\Core;
use ArrayAccess\TrayDigita\Benchmark\Injector\Injection\AbstractBasedCoreInjector;
use ArrayAccess\TrayDigita\Event\Interfaces\ManagerInterface;
use function dirname;
use function str_starts_with;

class CoreModuleBenchmarkSubscriber extends AbstractBasedCoreInjector
{
    private ?Core $core = null;

    private ?string $coreDirectory = null;

    public function setCoreModule(Core $module): static
    {
        $this->core = $module;
        return $this;
    }

    public function getCore(): ?Core
    {
        return $this->core;
    }

    protected function appendToRecord(): bool
    {
        return true;
    }

    /**
     * @param ManagerInterface $manager
     * @param string $eventName
     * @param string|null $id
     * @return bool
     */
    protected function acceptedRecord(ManagerInterface $manager, string $eventName, ?string $id): bool
    {
        if (!$this->getProfilerManager()->getProfiler()->isEnable()) {
            return false;
        }
        $trace = ($manager->getDispatcherTrace($eventName) ?? [])['file'] ?? null;
        if (!$trace) {
            return false;
        }
        $this->coreDirectory ??= dirname(__DIR__);
        if (!str_starts_with($trace, $this->coreDirectory)) {
            return false;
        }
        return true;
    }

    protected function isAllowedGroup(string $group): bool
    {
        return true;
    }
}
