<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Controllers\Attributes;

use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use ArrayAccess\TrayDigita\Routing\Router;
use ArrayAccess\TrayDigita\Util\Filter\DataNormalizer;
use Attribute;
use function in_array;
use function substr;

#[Attribute(Attribute::TARGET_CLASS)]
class Dashboard extends Group
{
    protected static string $prefix = 'dashboard';

    public function __construct(string $pattern = '')
    {
        $prefix = substr($pattern, 0, 1);
        // use static prefix
        $prefixRoute = static::getPrefix();
        // if contains delimiter
        if (in_array($prefix, Router::REGEX_DELIMITER)) {
            $prefixRoute = "$prefix$prefixRoute";
            $pattern = substr($pattern, 1);
        }
        $pattern = $prefixRoute . $pattern;
        parent::__construct($pattern);
    }

    public static function getPrefix(): string
    {
        return static::$prefix;
    }

    public static function setPrefix(string $prefix): void
    {
        static::$prefix = trim(
            DataNormalizer::normalizeUnixDirectorySeparator($prefix),
            '/'
        );
    }
}
