<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Api;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Api\TwigExtensions\UrlExtension;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Engines\TwigEngine;
use ArrayAccess\TrayDigita\View\Interfaces\ViewInterface;

final class Api extends CoreSubmoduleAbstract
{
    protected string $name = 'API';

    protected int $priority = -9998;

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to help api controller working properly',
            'module',
            'core-module'
        );
    }

    protected function doInit(): void
    {
        $twig = ContainerHelper::use(
            ViewInterface::class,
            $this->getContainer()
        )?->getEngine('twig');
        if ($twig instanceof TwigEngine) {
            $twig->addExtension(new UrlExtension($twig));
        }
        unset($twig);
    }
}
