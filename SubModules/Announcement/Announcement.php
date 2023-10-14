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
        return $this->translate(
            'Announcement',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module that make application support announcements',
            context: 'module'
        );
    }
}
