<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\PostCategory;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\Finder\CategoryFinder;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\Finder\PostFinder;
use ArrayAccess\TrayDigita\Database\Result\LazyResultCriteria;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

final class Posts extends CoreSubmoduleAbstract
{
    protected ?PostFinder $postFinder = null;

    protected ?CategoryFinder $categoryFinder = null;

    protected string $name = 'Post & Articles';

    public function getName(): string
    {
        return $this->translate(
            'Post & Articles',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to make application support posts publishing',
            context: 'module'
        );
    }

    public function getPostFinder(): ?PostFinder
    {
        return $this->postFinder ??= ContainerHelper::resolveCallable(
            PostFinder::class,
            $this->getContainer()
        );
    }

    public function getCategoryFinder(): ?CategoryFinder
    {
        return $this->categoryFinder ??= ContainerHelper::resolveCallable(
            CategoryFinder::class,
            $this->getContainer()
        );
    }

    public function findPostById(int $id): ?Post
    {
        return $this->getPostFinder()->find($id);
    }

    public function findCategoryById(int $id): ?PostCategory
    {
        return $this->getCategoryFinder()->find($id);
    }

    public function findPostBySlug(string $slug): ?Post
    {
        return $this->getPostFinder()->findBySlug($slug);
    }

    public function findCategoryBySlug(string $slug): ?PostCategory
    {
        return $this->getCategoryFinder()->findBySlug($slug);
    }

    public function searchPost(
        string $searchQuery,
        int $limit = 10,
        int $offset = 0,
        array $orderBy = [],
        CompositeExpression|Comparison ...$expressions
    ): LazyResultCriteria {
        return $this->getPostFinder()->search(
            $searchQuery,
            $limit,
            $offset,
            $orderBy,
            ...$expressions
        );
    }

    public function searchCategory(
        string $searchQuery,
        int $limit = 10,
        int $offset = 0,
        array $orderBy = [],
        CompositeExpression|Comparison ...$expressions
    ): LazyResultCriteria {
        return $this->getCategoryFinder()->search(
            $searchQuery,
            $limit,
            $offset,
            $orderBy,
            ...$expressions
        );
    }
}
