<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Service\Controllers;

use ArrayAccess\TrayDigita\App\Modules\Core\Core;
use ArrayAccess\TrayDigita\Http\RequestResponseExceptions\NotFoundException;
use ArrayAccess\TrayDigita\L10n\Languages\Locale;
use ArrayAccess\TrayDigita\Module\Modules;
use ArrayAccess\TrayDigita\Routing\AbstractController;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\Util\Filter\DataType;
use ArrayAccess\TrayDigita\Util\Filter\MimeType;
use ArrayAccess\TrayDigita\View\Engines\PhpEngine;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function filemtime;
use function func_get_args;
use function gmdate;
use function is_file;
use function is_string;

abstract class AbstractServiceController extends AbstractController
{
    public function beforeDispatch(ServerRequestInterface $request, string $method, ...$arguments): void
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
            $core = ContainerHelper::service(Modules::class, $this->getContainer())
                ->get(Core::class);
            $view->setParameter('language', $language);
            $core->getTranslator()?->setLanguage($language);
        }
    }

    /**
     * Render override with no cache & no index
     *
     * @param string $path
     * @param array $variable
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function render(string $path, array $variable = [], ?ResponseInterface $response = null): ResponseInterface
    {
        return DataType::appendNoCache(
            DataType::appendNoIndex(
                parent::render(...func_get_args())
            )
        );
    }

    protected function renderAssets(
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
}
