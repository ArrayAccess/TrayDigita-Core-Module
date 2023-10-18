<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers;

use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Post;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\Posts;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Abstracts\ArchiveBasedPostAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Abstracts\SinglePostAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Archive\TypeArchive;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Posts\PostTypes\Singular\SingularFinder;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\AvailabilityStatusEntityInterface;
use ArrayAccess\TrayDigita\Http\RequestResponseExceptions\NotFoundException;
use ArrayAccess\TrayDigita\Routing\AbstractController;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function ceil;
use function is_int;
use function max;
use function rtrim;
use function str_ends_with;

#[Group('/(?P<type>'.Post::TYPE_POST.'|'.Post::TYPE_PAGE.')')]
class PostPage extends AbstractController
{
    private ?SinglePostAbstract $post = null;

    private ?ArchiveBasedPostAbstract $archive = null;

    public function beforeDispatch(ServerRequestInterface $request, string $method, ...$arguments): void
    {
        $this->getModule(Users::class)->setRequest($request);
    }

    #[Any(
        pattern: '/(?P<wrapper>(?P<slug>[^/]+)[/]*)',
        priority: 21,
        name: 'post'
    )]
    public function post(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params
    ) : ResponseInterface {
        $type = $params['type'];
        $slug = $params['slug']??null;
        if (!$slug) {
            throw new NotFoundException(
                $request
            );
        }

        $postModule = $this->getModule(Posts::class);
        $post = SingularFinder::create($postModule, true, $slug);
        if (!$post
            || $post->getNormalizeType() !== $type
            || !$post->getPost()
            || !$post->permitted()
        ) {
            throw new NotFoundException(
                $request
            );
        }

        $this->post = $post;
        $postSlug = trim($this->post->getSlug(), '/');
        if ($postSlug !== $params['wrapper']) {
            return $this->redirect(
                $this
                    ->getView()
                    ->getBaseURI("/$type/$postSlug")
                    ->withQuery($request->getUri()->getQuery()),
                response: $response
            );
        }

        $this->getManager()?->attach(
            'view.bodyAttributes',
            [$this, 'eventBodyAttributes']
        );

        return $this->render(
            "templates/article",
            [
                'postType' => $type,
                'post' => $this->post,
                'title' => $this->post->getTitle()
            ],
            $response
        );
    }

    #[Any(
        pattern: '(?:/(?:p/(?:(?P<page>[0-9]+)[/]*)?))?',
        priority: 20,
        name: 'post'
    )]
    public function posts(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params
    ) : ResponseInterface {
        $page = $params['page']??1;
        $page = (int) $page;
        $page = max($page, 1);
        $type = $params['type'];
        if (empty($params['page'])) {
            return $this->redirect(
                $this
                ->getView()
                ->getBaseURI($type.'/p/1')
            );
        }
        if (str_ends_with($params[0], '/')) {
            return $this->redirect(
                $request
                    ->getUri()
                    ->withPath(
                        rtrim($request->getUri()->getPath(), '/')
                    )
            );
        }
        $perPage = $this
            ->getManager()
            ?->dispatch('post.perPage', 10);
        $perPage = is_int($perPage) ? $perPage : 10;
        $perPage = max($perPage, 1);
        // maximum page is 100
        $perPage = max($perPage, 100);
        $archive = new TypeArchive(
            $this->getModule(Posts::class),
            true
        );
        $status = AvailabilityStatusEntityInterface::PUBLISHED;
        $totalPost = $archive->getTotalPosts(
            $type,
            $status
        );
        $totalPage = ceil($totalPost/$perPage);
        if ($totalPage < $page) {
            throw new NotFoundException($request);
        }
        $offset = ($page - 1) * $perPage;
        $posts = $archive->getPosts(
            $offset,
            $perPage,
            $type,
            [
                'published_at' => 'DESC',
                'id' => 'DESC',
            ],
            $status
        );
        // not found
        if (count($posts) < 1) {
            throw new NotFoundException($request);
        }

        $this->getManager()?->attach(
            'view.bodyAttributes',
            [$this, 'eventBodyAttributes']
        );
        $this->archive = $archive;
        return $this->render(
            "templates/articles",
            [
                'postType' => $type,
                'currentPage' => $page,
                'perPage' => $perPage,
                'totalPage' => $totalPage,
                'totalPosts' => $totalPost,
                'archive' => $archive,
                'posts' => $posts
            ],
            $response
        );
    }

    private function eventBodyAttributes($attributes)
    {
        if (!$this->post && !$this->archive) {
            return $attributes;
        }

        if ($this->post) {
            $attributes['data-post-type'] = $this->post->getNormalizeType();
            $attributes['data-post-id'] = $this->post->getId();
            $attributes['data-post-status'] = $this->post->normalizeStatus($this->post->getStatus());
        }
        if ($this->archive) {
            $attributes['data-post-type'] = $this->archive->getType();
        }
        return $attributes;
    }
}
