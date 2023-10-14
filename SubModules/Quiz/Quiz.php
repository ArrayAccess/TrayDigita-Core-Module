<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Quiz;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Quiz extends CoreSubmoduleAbstract
{
    protected string $name = 'Quiz';

    public function getName(): string
    {
        return $this->translate(
            'Quiz',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to make application support quiz & course',
            context: 'module'
        );
    }
}
