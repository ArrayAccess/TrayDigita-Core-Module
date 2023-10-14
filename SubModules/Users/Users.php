<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Users;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\Admin;
use ArrayAccess\TrayDigita\App\Modules\Core\Entities\User;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Users\Factory\AdminEntityFactory;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Users\Factory\CapabilityFactory;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Users\Factory\UserEntityFactory;
use ArrayAccess\TrayDigita\Auth\Cookie\UserAuth;
use ArrayAccess\TrayDigita\Auth\Roles\Interfaces\PermissionInterface;
use ArrayAccess\TrayDigita\Collection\Config;
use ArrayAccess\TrayDigita\Container\Container;
use ArrayAccess\TrayDigita\Database\Connection;
use ArrayAccess\TrayDigita\Database\Entities\Abstracts\AbstractUser;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\CapabilityEntityFactoryInterface;
use ArrayAccess\TrayDigita\Database\Entities\Interfaces\UserEntityInterface;
use ArrayAccess\TrayDigita\Database\Wrapper\PermissionWrapper;
use ArrayAccess\TrayDigita\Exceptions\Runtime\RuntimeException;
use ArrayAccess\TrayDigita\Http\Factory\ServerRequestFactory;
use ArrayAccess\TrayDigita\Http\ServerRequest;
use ArrayAccess\TrayDigita\Http\SetCookie;
use ArrayAccess\TrayDigita\Kernel\Interfaces\KernelInterface;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\Util\Filter\DataNormalizer;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function filter_var;
use function is_array;
use function is_numeric;
use function is_string;
use function max;
use function preg_replace;
use function trim;
use const FILTER_VALIDATE_DOMAIN;
use const PHP_INT_MIN;

/**
 * @method Container getContainer()
 */
final class Users extends CoreSubmoduleAbstract
{
    protected string $name = 'Users & Auth';

    /**
     * @var int -> very important
     */
    protected int $priority = PHP_INT_MIN;

    protected PermissionInterface $permission;

    private bool $didInit = false;

    const ADMIN_MODE = 'admin';

    const USER_MODE = 'user';

    private array $cookieNames = [
        self::USER_MODE => [
            'name' => 'auth_user',
            'lifetime' => 0,
            'wildcard' => false
        ],
        self::ADMIN_MODE => [
            'name' => 'auth_admin',
            'lifetime' => 0,
            'wildcard' => false
        ]
    ];

    private ?User $userAccount = null;

    private ?Admin $adminAccount = null;

    private ?ServerRequestInterface $request = null;

    private bool $authProcessed = false;

    private string $currentMode = self::ADMIN_MODE;

    public function getName(): string
    {
        return $this->translate(
            'Users & Auth',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module that support users & authentication',
            context: 'module'
        );
    }

    protected function doInit(): void
    {
        if ($this->didInit) {
            return;
        }

        $this->didInit = true;
        // $this->doResolvePermission();
        // $this->doResolveCookieName();
        $this->doAddMiddleware();
        // stop here if config error
        if ($this->getKernel()?->getConfigError()) {
            return;
        }
        $this->getManager()?->attach(
            'view.beforeRender',
            [$this, 'viewBeforeRender']
        );
        $this->getManager()?->attach(
            'view.bodyAttributes',
            [$this, 'viewBodyAttributes']
        );
    }

    private function viewBodyAttributes($attributes): array
    {
        $this->getManager()?->detach(
            'view.bodyAttributes',
            [$this, 'viewBodyAttributes']
        );

        $attributes = !is_array($attributes) ? $attributes : [];
        $attributes['class'] = DataNormalizer::splitStringToArray($attributes['class']??null)??[];
        $user = $this->getUserAccount();
        $admin = $this->getAdminAccount();
        if (!$user && !$admin) {
            return $attributes;
        }
        $attributes['data-user-logged-in'] = true;
        return $attributes;
    }

    /** @noinspection PhpUnusedParameterInspection */
    private function viewBeforeRender(
        $path,
        $parameters,
        ViewInterface $view
    ) {
        $this->getManager()?->detach(
            'view.beforeRender',
            [$this, 'viewBeforeRender']
        );
        $view->setParameter('user_user', $this->getUserAccount());
        $view->setParameter('admin_user', $this->getAdminAccount());
        return $path;
    }

    private bool $permissionResolved = false;

    private function doResolvePermission(): self
    {
        if ($this->permissionResolved) {
            return $this;
        }
        $this->permissionResolved = true;
        $container = $this->getContainer();
        $connection = ContainerHelper::use(Connection::class, $container);
        $manager = $this->getManager();
        if (!$container->has(CapabilityEntityFactoryInterface::class)) {
            $container->set(
                CapabilityEntityFactoryInterface::class,
                static fn () => new CapabilityFactory()
            );
        }
        $hasPermission = $container->has(PermissionInterface::class);
        if ($hasPermission) {
            $permission = $container->get(PermissionInterface::class);
            if (!$permission instanceof PermissionInterface) {
                $container->remove(PermissionInterface::class);
                $hasPermission = false;
            }
        }
        if (!$hasPermission) {
            $permission = new PermissionWrapper(
                $connection,
                $container,
                $manager
            );
            if ($container instanceof Container) {
                $container->set(PermissionInterface::class, $permission);
            } else {
                $container->set(PermissionInterface::class, fn () => $permission);
            }
        }

        $permission ??= $container->get(PermissionInterface::class);
        if (!$permission instanceof PermissionWrapper) {
            $container->remove(PermissionInterface::class);
            $permission = new PermissionWrapper(
                $connection,
                $container,
                $manager,
                $permission
            );
            $container->set(PermissionInterface::class, $permission);
        }
        $this->permission = $permission;
        if ($this->permission instanceof PermissionWrapper
            && !$this->permission->getCapabilityEntityFactory()
        ) {
            $this->permission->setCapabilityEntityFactory(new CapabilityFactory());
        }
        return $this;
    }

    private function doAddMiddleware(): void
    {
        $container = $this->getContainer();
        ContainerHelper::use(
            KernelInterface::class,
            $container
        )?->getHttpKernel()->addMiddleware(
            new class($container, $this) extends AbstractMiddleware {
                protected int $priority = PHP_INT_MAX - 10;
                public function __construct(
                    ContainerInterface $container,
                    private readonly Users $auth
                ) {
                    parent::__construct($container);
                }

                protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
                {
                    $this->auth->setRequest($request);
                    return $request;
                }
            }
        );
    }

    private bool $cookieResolved = false;

    /**
     * @return self
     */
    private function doResolveCookieName(): self
    {
        if ($this->cookieResolved) {
            return $this;
        }

        $this->cookieResolved = true;
        $config = ContainerHelper::use(Config::class, $this->getContainer());
        $cookie = $config->get('cookie');
        if (!$cookie instanceof Config) {
            $cookie = new Config();
            $config->set('cookie', $cookie);
        }
        foreach ($this->cookieNames as $key => $names) {
            $cookieData = $cookie->get($key);
            $cookieData = $cookieData instanceof Config
                ? $cookieData
                : new Config();
            // replace
            $cookie->set($key, $cookieData);
            $cookieName = $cookieData->get('name');
            $cookieName = is_string($cookieName) && trim($cookieName) !== ''
                ? trim($cookieName)
                : $names['name'];
            $cookieName = preg_replace(
                '~[^!#$%&\'*+-.^_`|\~a-z0-9]~i',
                '',
                $cookieName
            );

            $cookieName = $cookieName === '' ? $names['name'] : $cookieName;
            $cookieLifetime = $cookieData->get('lifetime');
            $cookieLifetime = is_numeric($cookieLifetime) ? $cookieLifetime : 0;
            $cookieLifetime = max((int) $cookieLifetime, 0);
            $cookieWildcard = $cookieData->get('wildcard') === true;
            $this->cookieNames[$key]['name'] = $cookieName;
            $this->cookieNames[$key]['wildcard'] = $cookieWildcard;
            $this->cookieNames[$key]['lifetime'] = $cookieLifetime;
        }

        return $this;
    }

    public function getRequest(): ?ServerRequestInterface
    {
        return $this->request;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function isAuthProcessed(): bool
    {
        return $this->authProcessed;
    }

    private function doProcessAuth(): self
    {
        if (!$this->request || $this->authProcessed) {
            return $this;
        }

        $this->authProcessed = true;
        $container = $this->getContainer();
        $userAuth = ContainerHelper::service(UserAuth::class, $container);

        $request = $this->getManager()->dispatch('auth.request', $this->request);
        $request = $request instanceof ServerRequestInterface
            ? $request
            : $this->request;
        $userAuth->getHashIdentity()->setUserAgent(
            $request->getHeaderLine('User-Agent')
        );

        $cookieNames = $this->getCookieNames();
        $cookieParams = $request->getCookieParams();
        $adminCookie = $cookieParams[$cookieNames[self::ADMIN_MODE]['name']]??null;
        $adminCookie = !is_string($adminCookie) ? $adminCookie : null;
        $userCookie  = $cookieParams[$cookieNames[self::USER_MODE]['name']]??null;
        $userCookie = is_string($userCookie) ? $userCookie : null;

        $this->userAccount = $userCookie ? $userAuth->getUser(
            $userCookie,
            $this->getUserEntityFactory()
        ) : null;
        $this->adminAccount = $adminCookie ? $userAuth->getUser(
            $adminCookie,
            $this->getAdminEntityFactory()
        ) : null;
        return $this;
    }

    private function createEntityFactoryContainer(): self
    {
        $container = $this->getContainer();
        $hasUserEntity = $container->has(UserEntityFactory::class);
        $hasAdminEntity = $container->has(AdminEntityFactory::class);
        if ($hasUserEntity && $hasAdminEntity) {
            return $this;
        }
        if ($container instanceof Container) {
            if (!$hasUserEntity) {
                $container->set(UserEntityFactory::class, UserEntityFactory::class);
            }
            if (!$hasUserEntity) {
                $container->set(AdminEntityFactory::class, AdminEntityFactory::class);
            }
            return $this;
        }
        if (!$hasUserEntity) {
            $container->set(
                UserEntityFactory::class,
                fn() => ContainerHelper::resolveCallable(UserEntityFactory::class, $container)
            );
        }
        if (!$hasAdminEntity) {
            $container->set(
                AdminEntityFactory::class,
                fn() => ContainerHelper::resolveCallable(AdminEntityFactory::class, $container)
            );
        }
        return $this;
    }

    public function getAdminEntityFactory() : AdminEntityFactory
    {
        return $this
            ->createEntityFactoryContainer()
            ->getContainer()
            ->get(AdminEntityFactory::class);
    }

    public function getUserEntityFactory() : UserEntityFactory
    {
        return $this
            ->createEntityFactoryContainer()
            ->getContainer()
            ->get(UserEntityFactory::class);
    }

    public function getPermission(): PermissionInterface
    {
        $container = $this->doResolvePermission()->getContainer();
        $permission = ContainerHelper::service(PermissionInterface::class, $container);
        return $permission instanceof PermissionWrapper
            ? $permission
            : $this->permission;
    }

    public function setAsAdminMode(): void
    {
        $this->currentMode = self::ADMIN_MODE;
    }

    public function setAsUserMode(): void
    {
        $this->currentMode = self::ADMIN_MODE;
    }

    public function getCurrentMode(): string
    {
        return $this->currentMode;
    }

    public function isLoggedIn() : bool
    {
        return match ($this->getCurrentMode()) {
            self::ADMIN_MODE => $this->isAdminLoggedIn(),
            self::USER_MODE => $this->isUserLoggedIn(),
            default => false
        };
    }

    public function getAccount() : User|Admin|null
    {
        return match ($this->getCurrentMode()) {
            self::ADMIN_MODE => $this->getAdminAccount(),
            self::USER_MODE => $this->getUserAccount(),
            default => null
        };
    }

    public function getUserAccount(): ?User
    {
        return $this->doProcessAuth()->userAccount;
    }

    public function getAdminAccount(): ?Admin
    {
        return $this->doProcessAuth()->adminAccount;
    }

    /**
     * @return array{
     *      user: array{name:string, lifetime: int, wildcard: bool},
     *      admin: array{name:string, lifetime: int, wildcard: bool}
     *     }
     */
    public function getCookieNames(): array
    {
        return $this->doResolveCookieName()->cookieNames;
    }

    /**
     * @param string $type
     * @return ?array{name:string, lifetime: int, wildcard: bool}
     */
    public function getCookieNameData(string $type): ?array
    {
        return $this->getCookieNames()[$type]??null;
    }

    public function sendAuthCookie(
        AbstractUser $userEntity,
        ResponseInterface $response
    ) : ResponseInterface {
        $container = $this->getContainer();
        $userAuth = ContainerHelper::service(UserAuth::class, $container);

        if (!$userAuth instanceof UserAuth) {
            throw new RuntimeException(
                'Can not determine use auth object'
            );
        }
        $cookieName = $userEntity instanceof Admin
            ? 'admin'
            : ($userEntity instanceof User ? 'user' : null);
        $settings = $cookieName ? $this->getCookieNameData($cookieName) : null;
        if ($settings === null) {
            throw new RuntimeException(
                'Can not determine cookie type'
            );
        }
        $request = $this->request??ServerRequest::fromGlobals(
            $container->has(ServerRequestFactory::class)
                ?$container->get(ServerRequestFactory::class)
                : null
        );
        $domain = $request->getUri()->getHost();
        $newDomain = $this->getManager()?->dispatch(
            'auth.cookieDomain',
            $domain
        );

        $domain = is_string($newDomain) && filter_var(
            $newDomain,
            FILTER_VALIDATE_DOMAIN
        ) ? $newDomain : $domain;

        if ($settings['wildcard']) {
            $domain = DataNormalizer::splitCrossDomain($domain);
        }
        $cookie = new SetCookie(
            name: $settings['name'],
            value: $userAuth->getHashIdentity()->generate($userEntity->getId()),
            expiresAt: $settings['lifetime'] === 0 ? 0 : $settings['lifetime'] + time(),
            path: '/',
            domain: $domain
        );
        $cookieObject = $this->getManager()?->dispatch(
            'auth.cookieObject',
            $cookie
        );
        $cookie = $cookieObject instanceof SetCookie
            ? $cookieObject
            : $cookie;
        return $cookie->appendToResponse($response);
    }

    public function isAdminLoggedIn(): bool
    {
        return $this->getAdminAccount() !== null;
    }

    public function isUserLoggedIn(): bool
    {
        return $this->getUserAccount() !== null;
    }

    /**
     * @param int $id
     * @return ?Admin
     */
    public function getAdminById(int $id): ?UserEntityInterface
    {
        return $this->getAdminEntityFactory()->findById($id);
    }

    /**
     * @param string $username
     * @return ?Admin
     */
    public function getAdminByUsername(string $username): ?UserEntityInterface
    {
        return $this->getAdminEntityFactory()->findByUsername($username);
    }

    /**
     * @param int $id
     * @return ?User
     */
    public function getUserById(int $id): ?UserEntityInterface
    {
        return $this->getUserEntityFactory()->findById($id);
    }

    /**
     * @param string $username
     * @return ?User
     */
    public function getUserByUsername(string $username) : ?UserEntityInterface
    {
        return $this->getUserEntityFactory()->findByUsername($username);
    }
}
