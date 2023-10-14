<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Option\Option;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates\Middlewares\TemplateLoaderMiddleware;
use ArrayAccess\TrayDigita\Kernel\Interfaces\KernelInterface;
use ArrayAccess\TrayDigita\Templates\Wrapper;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;
use function is_string;

final class Templates extends CoreSubmoduleAbstract
{
    const ACTIVE_TEMPLATE_KEY = 'active_template';

    protected int $priority = 0;

    private TemplateRule $templateRule;

    protected string $name = 'Templates Helper';

    public function getName(): string
    {
        return $this->translate(
            'Templates Helper',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to support templating',
            context: 'module'
        );
    }

    public function getTemplateRule(): TemplateRule
    {
        return $this->templateRule;
    }

    protected function doInit(): void
    {
        // @attach(kernel.afterInitModules)
        $this->getManager()->attach('kernel.afterInitModules', [$this, 'initSetTemplate']);
    }

    private function initSetTemplate(KernelInterface $kernel): void
    {
        // @detach(kernel.afterInitModules)
        $this->getManager()->detach('kernel.afterInitModules', [$this, 'initSetTemplate']);
        if ($kernel->getConfigError()) {
            return;
        }
        $wrapper = ContainerHelper::decorate(Wrapper::class, $this->getContainer());
        $view    = ContainerHelper::decorate(ViewInterface::class, $this->getContainer());
        $this->templateRule = new TemplateRule($wrapper);
        $this->templateRule->initialize();
        $view?->setTemplateRule($this->templateRule);

        $option = $this->getModules()->get(Option::class);
        $active = $option?->get(self::ACTIVE_TEMPLATE_KEY)?->getValue();
        if (is_string($active)) {
            $this->templateRule->setActive($active);
        }
        $template = $this->templateRule->getActive();
        if ($template) {
            if ($template->getBasePath() !== $active && $option) {
                $option->set(self::ACTIVE_TEMPLATE_KEY, $template->getBasePath(), true);
            }
            $view->setViewsDirectory([]);
        }

        // add middleware to load templates.php
        $kernel->getHttpKernel()->addMiddleware(
            ContainerHelper::resolveCallable(
                TemplateLoaderMiddleware::class,
                $this->getContainer()
            )
        );
    }
}