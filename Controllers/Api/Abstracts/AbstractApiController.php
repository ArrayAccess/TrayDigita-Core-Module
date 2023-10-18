<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers\Api\Abstracts;

use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Collection\Config;
use ArrayAccess\TrayDigita\Http\Code;
use ArrayAccess\TrayDigita\Kernel\Decorator;
use ArrayAccess\TrayDigita\Routing\AbstractController;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use const JSON_PRETTY_PRINT;

abstract class AbstractApiController extends AbstractController
{
    const TYPE_USER = 'user';
    const TYPE_ADMIN = 'admin';

    protected ?string $authenticationMethod = null;

    protected function getAuthenticationMethod() : ?string
    {
        return $this->authenticationMethod;
    }

    final public function beforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    ) {

        $this->statusCode = 404;
        // set result as json if return is not string
        $this->asJSON = true;

        // pretty
        $env = ContainerHelper::use(Config::class)?->get('environment');
        $env = $env instanceof Config ? $env : null;
        if ($env?->get('prettyJson') === true) {
            $this->getManager()?->attach(
                'jsonResponder.encodeFlags',
                static fn ($flags) => JSON_PRETTY_PRINT|$flags
            );
        }
        $response = $this->doBeforeDispatch($request, $method, ...$arguments);
        if ($response instanceof ResponseInterface) {
            return $response;
        }
        $method = $this->getAuthenticationMethod();
        if ($method === null) {
            return null;
        }
        $jsonResponder = $this->getJsonResponder();
        $auth = Decorator::module(Users::class);
        $match = match ($method) {
            self::TYPE_USER => $auth->isUserLoggedIn()
                ? null
                : $jsonResponder->serve(Code::UNAUTHORIZED),
            self::TYPE_ADMIN => $auth->isAdminLoggedIn()
                ? null
                : $jsonResponder->serve(Code::UNAUTHORIZED),
            default => $auth->isAdminLoggedIn() || $auth->isUserLoggedIn()
                ? null
                : $jsonResponder->serve(Code::UNAUTHORIZED),
        };
        return $match ?? $this->doAfterBeforeDispatch(
            $request,
            $method,
            ...$arguments
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $method
     * @param ...$arguments
     * @return mixed
     * @noinspection PhpReturnDocTypeMismatchInspection
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function doBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    ) {
    }

    public function doAfterBeforeDispatch(
        ServerRequestInterface $request,
        string $method,
        ...$arguments
    ) {
    }
}
