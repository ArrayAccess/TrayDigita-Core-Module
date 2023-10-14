<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Singular;

use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Post;

class TypePage extends TypePost
{
    protected string $postType = Post::TYPE_PAGE;
}
