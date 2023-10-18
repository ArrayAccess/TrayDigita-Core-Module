<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers;

use ArrayAccess\TrayDigita\Http\RequestResponseExceptions\NotFoundException;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use ArrayAccess\TrayDigita\Util\Filter\MimeType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function filemtime;
use function is_file;
use function is_string;
use const PHP_INT_MIN;

// @todo add installation
#[Group('')]
class InstallController extends AbstractServiceController
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
    public function any() : ResponseInterface
    {
        return $this->redirect(
            $this->getView()->getBaseURI('/install')
        );
    }

    #[Any(
        pattern: '/install',
        priority: PHP_INT_MIN
    )]
    public function doInstall(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        return $this->render(
            'install',
            [
                'request' => $request
            ],
            $response
        );
    }
}
