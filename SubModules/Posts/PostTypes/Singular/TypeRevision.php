<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Singular;

use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Abstracts\SinglePostAbstract;
use function is_int;

class TypeRevision extends SinglePostAbstract
{
    private bool $postInit = false;

    public function getPost(): ?Post
    {
        if ($this->postInit) {
            return $this->post;
        }
        $this->postInit = true;
        if ($this->post) {
            return $this->post;
        }
        $this->postInit = true;
        $this->post = null;
        $post = is_int($this->identity)
            ? $this->module->findPostById($this->identity)
            : $this->module->findPostBySlug($this->identity);
        if ($post?->isRevision()) {
            $this->post = $post;
        }
        return $this->post;
    }
}
