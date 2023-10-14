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
        return $this->translate(
            'Extended Translator',
            context: 'module'
        );
    }

    public function getDescription(): string
    {
        return $this->translate(
            'Core module to support translation',
            context: 'module'
        );
    }

    /**
     * Register translation directory
     *
     * @param string $textDomain
     * @param string|array $directory
     * @return bool
     */
    public function registerDomain(string $textDomain, string|array $directory): bool
    {
        // filter
        if (trim($textDomain) === '') {
            return false;
        }
        $succeed = false;
        foreach ($this->getTranslator()->getAdapters() as $adapter) {
            if ($adapter instanceof AdapterBasedFileInterface) {
                $res = $adapter->registerDirectory($directory, $textDomain, true);
                if ($res) {
                    $succeed = true;
                }
            }
        }
        return $succeed;
    }
}
