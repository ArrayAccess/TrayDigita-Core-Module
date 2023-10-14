<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\Finder;

use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\PostCategory;
use ArrayAccess\TrayDigita\Database\Result\AbstractRepositoryFinder;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;
use function is_int;
use function is_string;

class CategoryFinder extends AbstractRepositoryFinder
{
    protected ?string $columnSearch = 'name';

    /**
     * @return ObjectRepository&Selectable<Post>
     */
    public function getRepository() : ObjectRepository&Selectable
    {
        return $this->connection->getRepository(
            PostCategory::class
        );
    }

    public function find($id) : ?PostCategory
    {
        if (is_int($id)) {
            return $this->findById($id);
        }
        if (is_string($id)) {
            return $this->findBySlug($id);
        }
        return null;
    }

    public function findById(int $id) : ?PostCategory
    {
        return $this->getRepository()->find($id);
    }

    public function findBySlug(string $slug) : ?PostCategory
    {
        return $this
            ->getRepository()
            ->findOneBy([
                'slug' => $slug
            ]);
    }
}
