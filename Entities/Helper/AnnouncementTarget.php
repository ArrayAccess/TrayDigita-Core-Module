<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Announcement\Helper;

// @todo completion
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Announcement;

class AnnouncementTarget
{
    protected Announcement $announcement;

    protected ?string $target;
    protected ?string $target_id;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = clone $announcement;
        $this->target = $this->announcement->getTarget();
        $this->target_id = $this->announcement->getTargetId();
    }

    public function getAnnouncement(): Announcement
    {
        return $this->announcement;
    }
}
