<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\Controllers;

use ArrayAccess\TrayDigita\Routing\AbstractController;
use ArrayAccess\TrayDigita\Routing\Attributes\All;
use ArrayAccess\TrayDigita\Routing\Attributes\Any;
use ArrayAccess\TrayDigita\Routing\Attributes\Group;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function base64_decode;

#[Group('/')]
class MainPage extends AbstractController
{
    #[Any('')]
    public function main() : ResponseInterface
    {
        return $this
            ->render('templates/home');
    }

    // handle favicon

    /** @noinspection PhpUnusedParameterInspection */
    #[All(
        '(?:favicon\.(?:ico|png))',
        name: 'favicon'
    )]
    public function favicon(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $this->getManager()
            ?->attach('response.sendPreviousBuffer', fn () => false);
        $this->getManager()
            ?->attach('response.reduceError', fn () => true);
        /** @noinspection SpellCheckingInspection */
        $response->getBody()->write(
            base64_decode(
                'iVBORw0KGgoAAAANSUhEUgAAABAAAAAQAQMAAAAlPW0iAAAAA1BMVEUAAACn'
                . 'ej3aAAAAAXRSTlMAQObYZgAAAAtJREFUCNdjIBEAAAAwAAFletZ8AAAAAElFTkSuQmCC'
            )
        );

        return $response->withHeader(
            'Content-Type',
            'image/png'
        )->withHeader(
            'Cache-Control',
            'max-age=604800, public'
        )->withHeader(
            'Expires',
            gmdate('D, d M Y H:i:s \G\M\T', time() + 86400)
        )->withHeader('Pragma', 'public');
    }
}
