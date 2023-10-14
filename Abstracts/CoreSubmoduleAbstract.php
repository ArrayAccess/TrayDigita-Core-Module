<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Abstracts;

use ArrayAccess\TrayDigita\App\Modules\Core\Core;
use ArrayAccess\TrayDigita\Exceptions\Runtime\UnsupportedRuntimeException;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\TranslatorInterface;
use ArrayAccess\TrayDigita\Module\Interfaces\ModuleInterface;
use ArrayAccess\TrayDigita\Module\Modules;
use ArrayAccess\TrayDigita\Module\Traits\ModuleTrait;
use function debug_backtrace;
use function sprintf;
use const DEBUG_BACKTRACE_IGNORE_ARGS;

abstract class CoreSubmoduleAbstract implements ModuleInterface
{
    use ModuleTrait;

    public readonly Core $core;

    final public function __construct(
        protected readonly Modules $modules,
        Core $core = null
    ) {
        if ((debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class']??null) !== Core::class) {
            throw new UnsupportedRuntimeException(
                sprintf(
                    'Module only can be instantiated inside of %s',
                    Core::class
                )
            );
        }

        $this->important = true;
        $this->core = $core;
    }

    final public function isCore(): bool
    {
        return true;
    }

    public function getTranslator() : ?TranslatorInterface
    {
        return $this->core->getTranslator();
    }

    public function translate(
        string $original,
        string $domain = TranslatorInterface::DEFAULT_DOMAIN,
        ?string $context = null
    ): string {
        return $this->core->translate(...func_get_args());
    }

    public function translatePlural(
        string $singular,
        string $plural,
        int|float $number,
        string $domain = TranslatorInterface::DEFAULT_DOMAIN,
        ?string $context = null
    ): string {
        return $this->core->translatePlural(...func_get_args());
    }
}
