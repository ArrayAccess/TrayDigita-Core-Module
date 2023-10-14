<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Controllers\Abstracts;

use ArrayAccess\TrayDigita\Routing\AbstractController;
use Psr\Http\Message\ServerRequestInterface;

class AbstractUserController extends AbstractController
{
    public function beforeDispatch(ServerRequestInterface $request, string $method, ...$arguments) : void
    {
    }
}
