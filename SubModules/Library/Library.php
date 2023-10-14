<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Library;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Library extends CoreSubmoduleAbstract
{
    protected string $name = 'Library & Books';

    public function getName(): string
    {
        return $this->translate(
            'Library & Books',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to make application support library application',
            context: 'module'
        );
    }
}
