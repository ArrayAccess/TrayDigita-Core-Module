<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\EducationalInstitution;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class EducationalInstitution extends CoreSubmoduleAbstract
{
    protected string $name = 'Educational Institution';

    public function getName(): string
    {
        return $this->translate(
            'Educational Institution',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to make application support educational structure',
            context: 'module'
        );
    }
}
