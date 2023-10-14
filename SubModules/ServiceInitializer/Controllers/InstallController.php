<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Controllers;

use ArrayAccess\TrayDigita\Http\RequestResponseExceptions\NotFoundException;
use ArrayAccess\TrayDigita\L10n\Languages\Locale;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\TranslatorInterface;
use ArrayAccess\TrayDigita\Routing\AbstractController;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\Util\Filter\MimeType;
use ArrayAccess\TrayDigita\View\Engines\PhpEngine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function filemtime;
use function is_file;
use function is_string;
use const PHP_INT_MIN;

// @todo add installation
#[Group('')]
class InstallController extends AbstractController
{
    public function beforeDispatch(ServerRequestInterface $request, string $method, ...$arguments): void
    {
    }

    #[Any(
        pattern: '/assets@install/(js|css|png|svg)/([^/]+\.\1)',
        priority: PHP_INT_MIN + 9
    )]
    public function renderAssets(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params
    ): ResponseInterface {
        $file = __DIR__ . "/Views/assets/$params[1]/$params[2]";
        if (!is_file($file)) {
            throw new NotFoundException($request);
        }
        $stream = $this->getStreamFactory()->createStreamFromFile($file);
        return $response
            ->withBody(
                $stream
            )->withHeader(
                'Content-Type',
                MimeType::fromExtension($params[1])
            )->withHeader(
                'Last-Modified-Since',
                gmdate('Y-m-d H:i:s \G\M\T', filemtime($file))
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
        $this->doInit($request);
        return $this->render(
            'install',
            [
                'request' => $request
            ],
            $response
        );
    }

    private function doInit(ServerRequestInterface $request): void
    {
        $view = $this->getView();
        $view->setRequest($request);
        $view->addEngine(new PhpEngine($view));
        $view->prependViewsDirectory(__DIR__ .'/Views');
        $language = $request->getQueryParams()['lang']??null;
        if (is_string($language)) {
            $language = Locale::normalizeLocale($language);
        }
        if ($language) {
            $translator = ContainerHelper::service(TranslatorInterface::class, $this->getContainer());
            $view->setParameter('language', $language);
            $translator?->setLanguage($language);
        }
    }
}
