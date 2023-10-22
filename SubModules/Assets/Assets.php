<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Assets;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Assets\TwigExtensions\AssetsExtension;
use ArrayAccess\TrayDigita\Assets\AssetsJsCssQueue;
use ArrayAccess\TrayDigita\Http\Uri;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\View\Engines\TwigEngine;
use function array_filter;
use function is_scalar;
use function is_string;
use function preg_match;
use function sprintf;

final class Assets extends CoreSubmoduleAbstract
{
    protected string $name = 'Assets Helper';

    protected AssetsJsCssQueue $assetQueue;

    public function getName(): string
    {
        return $this->translateContext(
            'Assets Helper',
            'module-info',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module that help assets rendering',
            'module-info',
            'core-module'
        );
    }

    protected function doInit(): void
    {
        $this->assetQueue = ContainerHelper::service(
            AssetsJsCssQueue::class,
            $this->getContainer()
        );

        $this->registerFactoryAssets();
        $twig = $this->core->getView()->getEngine('twig');
        if ($twig instanceof TwigEngine) {
            $twig->addExtension(new AssetsExtension($this));
        }
        unset($twig);

        // @attach(view.contentHeader)
        $this->getManager()?->attach(
            'view.contentHeader',
            [$this, 'contentHeaderEvent']
        );

        // @attach(view.contentFooter)
        $this->getManager()?->attach(
            'view.contentFooter',
            [$this, 'contentFooterEvent']
        );

        // $this->getAssetQueue()->queueFooterJs('grapes');
    }

    private function registerFactoryAssets(): void
    {
        $manager = $this->getManager();
        // @dispatch(moduleAssets.beforeRegisterAssets);
        $manager->dispatch('moduleAssets.beforeRegisterAssets', $this);
        $css = $this->getAssetQueue()->getCSS();
        $js  = $this->getAssetQueue()->getJS();
        // do register assets
        foreach (['css', 'js'] as $key) {
            $script = $key === 'css' ? $css : $js;
            $assets = $key === 'css' ? self::CSS : self::JS;
            foreach ($assets as $name => $list) {
                if (!is_string($list['url'] ?? null)) {
                    continue;
                }
                $url = $list['url'];
                $version = $list['version'] ?? null;
                $inherits = $list['inherits'] ?? [];
                if (!preg_match('~(https?:)?//~', $url)) {
                    $url = $this->core->getView()->getBaseURI($url)??$url;
                } else {
                    $url = new Uri($url);
                }
                if (is_scalar($version)) {
                    $url = $url->withQuery(sprintf('v=%s', $version));
                }
                unset($list['url'], $list['version'], $list['inherits']);
                $attributes = $list;
                $script->registerURL(
                    $name,
                    $url,
                    $attributes,
                    ...array_filter($inherits, 'is_string')
                );
            }
        }
        // @dispatch(moduleAssets.registerAssets);
        $manager->dispatch('moduleAssets.registerAssets', $this);
        // @dispatch(moduleAssets.afterRegisterAssets);
        $manager->dispatch('moduleAssets.afterRegisterAssets', $this);

        // do register packages
        // @dispatch(moduleAssets.beforeRegisterPackage);
        $manager->dispatch('moduleAssets.beforeRegisterPackage', $this);
        foreach (self::PACKAGE as $jsName => $item) {
            $js = $item['js']??[];
            $css = $item['css']??[];
            $this->getAssetQueue()->registerPackage(
                $jsName,
                $js,
                $css
            );
        }

        // @dispatch(moduleAssets.registerPackage);
        $manager->dispatch('moduleAssets.registerPackage', $this);
        // @dispatch(moduleAssets.afterRegisterPackage);
        $manager->dispatch('moduleAssets.afterRegisterPackage', $this);
    }

    public function getAssetQueue(): AssetsJsCssQueue
    {
        return $this->assetQueue;
    }

    public function contentHeaderEvent($args)
    {
        // @detach(view.contentHeader)
        $this->getManager()?->detach(
            'view.contentHeader',
            [$this, 'contentHeaderEvent']
        );
        echo $this->getAssetQueue()->renderHeader();
        return $args;
    }

    private function contentFooterEvent($args)
    {
        // @detach(view.contentFooter)
        $this->getManager()?->detach(
            'view.contentFooter',
            [$this, 'contentFooterEvent']
        );

        // do render unregistered css
        echo $this->getAssetQueue()->renderLastCss();
        echo $this->getAssetQueue()->renderFooter();
        echo $this->getAssetQueue()->renderLastScript();
        return $args;
    }

    final const CSS = [
        // https://grapesjs.com/
        "grapesjs" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.12.17/css/grapes.min.css',
            'version' => '0.12.17',
            'inherits' => [],
        ],
        // https://fengyuanchen.github.io/cropperjs/
        "cropper" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css',
            'version' => '1.6.1',
            'inherits' => [],
        ],
        // https://quilljs.com/
        "quill-snow" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.snow.min.css',
            'version' => '1.3.7',
            'inherits' => [],
        ],
        "quill-bubble" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.bubble.min.css',
            'version' => '1.3.7',
            'inherits' => [],
        ],
        // http://codemirror.net/
        "codemirror" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.css',
            'version' => '6.65.7',
            'inherits' => [],
        ],
        // https://getuikit.com/
        "uikit" => [
            'url'  => 'https://cdn.jsdelivr.net/npm/uikit@3.17.1/dist/css/uikit.min.css',
            'version' => '3.17.1',
            'inherits' => [],
        ],
    ];

    final const JS = [
        // http://jquery.com/
        'jquery' => [
            'url' => 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js',
            'version' => '3.7.1',
            'inherits' => [],
        ],
        // https://grapesjs.com/
        "grapesjs" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/grapesjs/0.12.17/grapes.min.js',
            'version' => '0.12.17',
            'inherits' => [],
        ],
        // https://fengyuanchen.github.io/cropperjs/
        "cropper" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js',
            'version' => '1.6.1',
            'inherits' => [],
        ],
        // https://quilljs.com/
        "quill" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/quill/1.3.7/quill.min.js',
            'version' => '1.3.7',
            'inherits' => [],
        ],
        // http://codemirror.net/
        "codemirror" => [
            'url'  => 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/6.65.7/codemirror.min.js',
            'version' => '6.65.7',
            'inherits' => [],
        ],
        // https://getuikit.com/
        "uikit" => [
            'url'  => 'https://cdn.jsdelivr.net/npm/uikit@3.17.1/dist/js/uikit.min.js',
            'version' => '3.17.1',
            'inherits' => [],
        ],
    ];

    final const PACKAGE = [
        'grapesjs' => [
            'css' => [
                'grapesjs' => []
            ],
        ],
        'uikit' => [
            'css' => [
                'uikit' => []
            ],
        ],
        'cropper' => [
            'css' => [
                'cropper' => []
            ],
        ],
        'quill' => [
            'css' => [
                'quill-snow' => []
            ],
        ],
        'codemirror' => [
            'css' => [
                'codemirror' => []
            ],
        ],
    ];
}
