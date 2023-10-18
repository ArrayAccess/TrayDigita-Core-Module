<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Sites;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Site;
use ArrayAccess\TrayDigita\Database\Helper\Expression;
use ArrayAccess\TrayDigita\Http\ServerRequest;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use function preg_match;
use const PHP_INT_MAX;

final class Sites extends CoreSubmoduleAbstract
{
    protected Site|null|false $site = null;

    protected ?ServerRequestInterface $request = null;

    protected string $name = 'Site';

    public function getName(): string
    {
        return $this->translateContext(
            'Site',
            'module-info',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module for site',
            'module-info',
            'core-module'
        );
    }

    protected function doInit(): void
    {
        $this->doAddMiddleware();
    }

    private function doAddMiddleware(): void
    {
        $container = $this->getContainer();
        $this->getKernel()?->getHttpKernel()->addMiddleware(
            new class($container, $this) extends AbstractMiddleware {
                protected int $priority = PHP_INT_MAX - 10;
                public function __construct(
                    ContainerInterface $container,
                    private readonly Sites $sites
                ) {
                    parent::__construct($container);
                }

                protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
                {
                    $this->sites->setRequest($request);
                    return $request;
                }
            }
        );
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function getSite(): ?Site
    {
        if ($this->site !== null) {
            return $this->site?:null;
        }
        $container = $this->getContainer();
        $request = $this->getRequest()??
            ServerRequest::fromGlobals(
                ContainerHelper::use(ServerRequestFactoryInterface::class, $container),
                ContainerHelper::use(StreamFactoryInterface::class, $container)
            );
        $this->site = false;
        $host = $request->getUri()->getHost();
        preg_match(
            '~^([^.]+)\.(.+\.[^.]+)$~',
            $host,
            $match
        );
        $mainDomain = $match[2]??null;
        $subDomain = $match[1]??null;
        $expression = [
            Expression::eq(
                'domain',
                $host
            )
        ];
        if ($mainDomain && $subDomain) {
            $expression[] = Expression::andX(
                Expression::eq('domain', $mainDomain),
                Expression::eq('domain_alias', $subDomain),
            );
        }
        $this->site = $this
            ->core
            ->getConnection()
            ->getRepository(Site::class)
            ->matching(
                Expression::criteria()
                ->where(
                    count($expression) > 1
                        ? Expression::orX(...$expression)
                        : $expression[0]
                )->setMaxResults(1)
            )->first()?:false;
        return $this->site?:null;
    }
}
