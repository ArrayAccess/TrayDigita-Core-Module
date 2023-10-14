<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Assets\TwigExtensions;

use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Assets\Assets;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetsExtension extends AbstractExtension
{
    public function __construct(public readonly Assets $assets)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'render_css',
                fn ($name = null) => $name ? $this->assets->getAssetQueue()->getCSS()->render((string) $name) : '',
                ['is_safe' => ['html']]
            ),
            new TwigFunction(
                'render_js',
                fn ($name = null) => $name ? $this->assets->getAssetQueue()->getJS()->render((string) $name) : '',
                ['is_safe' => ['html']]
            )
        ];
    }
}
