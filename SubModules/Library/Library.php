<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Library;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Library extends CoreSubmoduleAbstract
{
    protected string $name = 'Library & Books';

    public function getName(): string
    {
        return $this->translateContext(
            'Library & Books',
            'module',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to make application support library application',
            'module',
            'core-module'
        );
    }
}
