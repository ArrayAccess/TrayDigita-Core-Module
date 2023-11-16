<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Middlewares;

use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Option\Option;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Templates;
use ArrayAccess\TrayDigita\App\Modules\Users\Users;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Templates\Middlewares\TemplateLoaderMiddleware;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function is_string;
use const PHP_INT_MAX;

class TemplateMiddleware extends AbstractMiddleware
{
    /**
     * Priority should be lower than template loader
     * @var int
     * @see TemplateLoaderMiddleware::$priority
     */
    protected int $priority = PHP_INT_MAX - 99999;

    public function __construct(
        ContainerInterface $container,
        public readonly Templates $templates
    ) {
        parent::__construct($container);
    }

    protected function doProcess(ServerRequestInterface $request): ServerRequestInterface|ResponseInterface
    {
        if (Consolidation::isCli()) {
            return $request;
        }
        $option = $this->templates->getModule(Users::class)->getOption();
        $active = $option?->get(Templates::ACTIVE_TEMPLATE_KEY)?->getValue();
        if (is_string($active)) {
            $this->templates->getTemplateRule()->setActive($active);
        }
        $template = $this->templates->getTemplateRule()->getActive();
        if ($template) {
            if ($template->getBasePath() !== $active && $option) {
                $option->set(Templates::ACTIVE_TEMPLATE_KEY, $template->getBasePath(), true);
            }
            $this->templates->core->getView()->setViewsDirectory([]);
        }

        return $request;
    }
}
