<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\Templates;

use ArrayAccess\TrayDigita\Templates\Abstracts\AbstractTemplateRule;

class TemplateRule extends AbstractTemplateRule
{
    /**
     * @var array<string>
     */
    protected array $requiredFiles = [
        'base.twig',
        'errors/404.twig',
        'errors/500.twig',
        'templates/home.twig',
        'templates/articles.twig',
        'templates/article.twig',
        'templates/maintenance.twig',
        'templates/page.twig',
        'templates/search.twig',

        // dashboard
        'dashboard/login.twig',
        'dashboard/register.twig',
        'dashboard/reset-password.twig',

        // user
        'dashboard/login.twig',
        'dashboard/register.twig',
        'dashboard/reset-password.twig',
    ];
}
