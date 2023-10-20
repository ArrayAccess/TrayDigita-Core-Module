<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers\Api;

use ArrayAccess\TrayDigita\App\Modules\Core\Controllers\Abstracts\AbstractApiController;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Api\Attributes\RouteAPI;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use const PHP_INT_MAX;

#[RouteAPI]
class GlobalApi extends AbstractApiController
{
    /** @noinspection PhpUnusedParameterInspection */
    #[Any(
        pattern: '(/:any:)?', // regexp: ~^/api(.*)?$~
        priority: PHP_INT_MAX
    )]
    public function wildcard(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        return $this->getJsonResponder()->serve(
            404,
            null,
            $response
        );
    }
}
