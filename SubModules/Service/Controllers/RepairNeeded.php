<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Controllers;

use ArrayAccess\TrayDigita\Http\Code;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use const PHP_INT_MIN;

#[Group('')]
class RepairNeeded extends AbstractServiceController
{
    #[Any(
        pattern: '/assets@core/(js|css|png|svg)/([^/]+\.\1)',
        priority: PHP_INT_MIN + 9
    )]
    public function renderAssets(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params
    ): ResponseInterface {
        return parent::renderAssets(
            $request,
            $response,
            $params
        );
    }

    #[Any(
        pattern: '/.*',
        priority: PHP_INT_MIN + 10
    )]
    public function any(
        ServerRequestInterface $request,
        ResponseInterface $response
    ) : ResponseInterface {
        return $this->render(
            'repair',
            [
                'request' => $request
            ],
            $response
        )->withStatus(Code::SERVICE_UNAVAILABLE);
    }
}
