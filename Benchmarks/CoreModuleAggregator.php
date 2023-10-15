<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Benchmarks;

use ArrayAccess\TrayDigita\App\Modules\Core\Core;
use ArrayAccess\TrayDigita\Benchmark\Aggregate\AbstractAggregator;
use ArrayAccess\TrayDigita\Benchmark\Interfaces\RecordInterface;
use function dirname;

class CoreModuleAggregator extends AbstractAggregator
{
    protected string $name = 'Core Module';

    protected string $groupName = 'coreModule';

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

    private bool $translated = false;

    public function getName(): string
    {
        if ($this->translated) {
            return $this->name;
        }
        $this->translated = true;
        return $this->name = $this
            ->getCore()
            ?->translate('Event', context: 'benchmark')??$this->name;
    }

    public function accepted(RecordInterface $record): bool
    {
        if ($record->getGroup()->getName() === $this->groupName) {
            return true;
        }
        if (!$this->core) {
            return false;
        }
        $eventName = $record->getName();
        $trace = ($this->core->getManager()->getDispatcherTrace($eventName) ?? [])['file'] ?? null;
        if (!$trace) {
            return false;
        }
        $this->coreDirectory ??= dirname(__DIR__);
        return str_starts_with($trace, $this->coreDirectory);
    }
}
