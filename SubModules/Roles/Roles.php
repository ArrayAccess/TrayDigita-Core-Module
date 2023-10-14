<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Roles;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Roles extends CoreSubmoduleAbstract
{
    protected int $priority = -9999;

    protected string $name = 'Role & Capabilities';

    public function getName(): string
    {
        return $this->translate(
            'Role & Capabilities',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to make application support role capabilities / permissions',
            context: 'module'
        );
    }
}
