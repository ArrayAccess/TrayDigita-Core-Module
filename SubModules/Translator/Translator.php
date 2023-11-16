<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Translator;

use ArrayAccess\TrayDigita\App\Modules\Core\Abstracts\CoreSubmoduleAbstract;
use ArrayAccess\TrayDigita\L10n\Translations\Interfaces\AdapterBasedFileInterface;
use function trim;

class Translator extends CoreSubmoduleAbstract
{

    protected string $name = 'Extended Translator';

    public function getName(): string
    {
        return $this->translateContext(
            'Extended Translator',
            'module-info',
            'core-module'
        );
    }

    public function getDescription(): string
    {
        return $this->translateContext(
            'Core module to support translation',
            'module-info',
            'core-module'
        );
    }

    /**
     * Register translation directory
     *
     * @param string $textDomain
     * @param string ...$directory
     * @return bool
     */
    public function registerDomain(string $textDomain, string ...$directory): bool
    {
        // filter
        if (trim($textDomain) === '') {
            return false;
        }
        return $this->getTranslator()?->registerDirectory($textDomain, ...$directory);
    }
}
