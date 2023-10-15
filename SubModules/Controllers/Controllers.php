<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Controllers;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Controllers extends CoreSubmoduleAbstract
{
    protected string $name = 'Controller Module';

    public function getName(): string
    {
        return $this->translateContext(
            'Controller Module',
            'module',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to controller working properly',
            'module',
            'core-module'
        );
    }
}
