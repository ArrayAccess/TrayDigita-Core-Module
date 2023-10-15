<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\EducationalInstitution;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class EducationalInstitution extends CoreSubmoduleAbstract
{
    protected string $name = 'Educational Institution';

    public function getName(): string
    {
        return $this->translateContext(
            'Educational Institution',
            'module',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application support educational structure',
            'module',
            'core-module'
        );
    }
}
