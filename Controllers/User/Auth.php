<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers\User;

use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\User;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers\AbstractUserController;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use Psr\Http\Message\ResponseInterface;
use const PHP_INT_MIN;

#[User('/auth')]
class Auth extends AbstractUserController
{
    protected bool $doRedirect = false;

    protected bool $asJSON = false;

    #[Any(
        pattern: '/',
        priority: PHP_INT_MIN
    )]
    public function login(
        ResponseInterface $response
    ): ResponseInterface {
        return $this->render(
            'user/login',
            [
                'title' => $this->trans(
                    'Login to member area',
                    'core-module'
                ),
            ],
            $response
        );
    }
}
