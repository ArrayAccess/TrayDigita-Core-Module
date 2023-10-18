<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Announcement;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;

final class Announcement extends CoreSubmoduleAbstract
{
    protected string $name = 'Announcement';

    /**
     * @var bool
     */
    protected bool $important = true;

    public function getName(): string
    {
        return $this->translateContext(
            'Announcement',
            'module-info',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module that make application support announcements',
            'module-info',
            'core-module'
        );
    }
}
