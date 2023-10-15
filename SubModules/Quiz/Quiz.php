<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Quiz;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Quiz extends CoreSubmoduleAbstract
{
    protected string $name = 'Quiz';

    public function getName(): string
    {
        return $this->translateContext(
            'Quiz',
            'module',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application support quiz & course',
            'module',
            'core-module'
        );
    }
}
