<?php
/** @noinspection PhpUnusedParameterInspection */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers\Api\User;

use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\UserAPI;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers\AbstractApiController;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Routing\Attributes\Abstracts\HttpMethodAttributeAbstract;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function sprintf;

/**
 * #[Group('/path')] -> is a prefix
 * When the sub route contains fully regex & group is not empty
 * Group prefix must be start as delimiter (#|~)
 * and Sub Route should use delimiter at the end, eg:
 * #[Group('#/prefix-regex')]
 * #[Method('/after/delimiter#')]
 */
#[UserAPI('')]
class User extends AbstractApiController
{
//    protected ?string $authenticationMethod = self::TYPE_ADMIN;

    /**
     * RegExP: #/api/(?<id>[1-9][0-9]{0,2})?[/]*$
     * @use Any('/pattern')
     * @see HttpMethodAttributeAbstract::ANY_METHODS
     *
     * @see \ArrayAccess\TrayDigita\Routing\Attributes\All for Using all methods (include any methods)
     * @see \ArrayAccess\TrayDigita\Routing\Attributes\Cli for Using cli methods
     */
    #[Any(
        pattern: '/(?P<id>[1-9][0-9]{0,10})',
        name: 'admin-user'
    )]
    public function user(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params,
        string $prefixSlash,
        string $suffixSlash
    ) : ResponseInterface {
        $id = (int) ($params['id']?:1);
        $module = $this->getModule(Users::class);
        $user = $module->getUserById($id);
        $this->statusCode = $user ? 200 : 404;
        return $this
            ->getJsonResponder()
            ->serve(
                $this->statusCode,
                $user??sprintf('User (%d) not found', $id),
                $response
            );
        /*return $response ?? [
            'message' => sprintf('USER ID (%d) NOT FOUND', $id)
        ];*/
    }
}
