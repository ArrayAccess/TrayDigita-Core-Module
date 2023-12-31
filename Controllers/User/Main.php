<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers\User;

use ArrayAccess\TrayDigita\App\Modules\Users\Route\Attributes\User;
use ArrayAccess\TrayDigita\App\Modules\Users\Route\Controllers\AbstractUserController;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use Psr\Http\Message\ResponseInterface;
use const PHP_INT_MAX;

#[User]
class Main extends AbstractUserController
{
    #[Any(
        pattern: '(/.*)?',
        priority: PHP_INT_MAX - 1000
    )]
    public function dashboard(
        ResponseInterface $response
    ) : ResponseInterface {
        return $this->render(
            '/user/dashboard',
            [],
            $response
        );
    }
}
