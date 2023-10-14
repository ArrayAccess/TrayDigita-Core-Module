<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Middlewares;

use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function is_file;
use const DIRECTORY_SEPARATOR;
use const PHP_INT_MAX;

class TemplateLoaderMiddleware extends AbstractMiddleware
{
    protected int $priority = PHP_INT_MAX - 10000;

    protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        // register
        $active = ContainerHelper::use(ViewInterface::class, $this->getContainer())
            ?->getTemplateRule()
            ->getActive();
        $file = null;
        if ($active) {
            $file = $active->getTemplateDirectory() . DIRECTORY_SEPARATOR . 'templates.php';
            if (is_file($file)) {
                try {
                    (fn($file) => include_once $file)->call($active, $file);
                } catch (Throwable $e) {
                    $logger = ContainerHelper::use(
                        LoggerInterface::class,
                        $this->getContainer()
                    );
                    $logger->notice($e, context: ['mode' => 'templates_include']);
                    throw $e;
                }
            }
        }
        $this->getManager()->dispatch(
            'templates.templateFileLoaded',
            $active,
            $file
        );
        return $request;
    }
}
