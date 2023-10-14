<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Controllers;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Controllers extends CoreSubmoduleAbstract
{
    protected string $name = 'Controller Module';

    public function getName(): string
    {
        return $this->translate(
            'Controller Module',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to controller working properly',
            context: 'module'
        );
    }
}
