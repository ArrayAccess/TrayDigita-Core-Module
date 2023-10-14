<?php
declare(strict_types=1);

namespace ArrayAccess\TrayDigita\App\Modules\Core\SubModules\ServiceInitializer\Middlewares;

use ArrayAccess\TrayDigita\Benchmark\Interfaces\ProfilerInterface;
use ArrayAccess\TrayDigita\Benchmark\Waterfall;
use ArrayAccess\TrayDigita\Collection\Config;
use ArrayAccess\TrayDigita\Event\Interfaces\ManagerInterface;
use ArrayAccess\TrayDigita\Middleware\AbstractMiddleware;
use ArrayAccess\TrayDigita\Traits\Http\StreamFactoryTrait;
use ArrayAccess\TrayDigita\Util\Filter\Consolidation;
use ArrayAccess\TrayDigita\Util\Filter\ContainerHelper;
use ArrayAccess\TrayDigita\Util\Filter\DataType;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use function memory_get_usage;
use function microtime;
use function preg_replace;
use function str_contains;
use const PHP_INT_MAX;
use const PHP_INT_MIN;

class DebuggingMiddleware extends AbstractMiddleware
{
    protected int $priority = PHP_INT_MIN;

    use StreamFactoryTrait;

    private bool $registered = false;

    private bool $darkMode = false;
    private ?float $requestFloat = null;

    protected function doProcess(
        ServerRequestInterface $request
    ): ServerRequestInterface {
        if (!$this->registered) {
            $this->requestFloat = $request->getServerParams()['REQUEST_TIME_FLOAT']??null;
            $this->registered = true;
            $this->registerBenchmarkDebugBar();
        }
        return $request;
    }

    protected function registerBenchmarkDebugBar(): void
    {
        // do not run if cli
        if (Consolidation::isCli()) {
            return;
        }
        $container = $this->getContainer();
        $config = ContainerHelper::use(Config::class, $container);
        $manager = ContainerHelper::use(ManagerInterface::class, $container);
        if (!$config || !$manager) {
            return;
        }
        if (!$config instanceof Config
            || !($config = $config->get('environment')) instanceof Config
        ) {
            return;
        }
        if ($config->get('showPerformance') === true) {
            $manager->attach(
                'response.final',
                [$this, 'printPerformance'],
                priority: PHP_INT_MAX - 5
            );
        }

        if ($config->get('profiling') !== true
            || $config->get('debugBar') !== true
        ) {
            return;
        }

        $this->darkMode = $config->get('debugBarDarkMode') === true;
        $manager->attach(
            'response.final',
            [$this, 'renderDebugBar'],
            priority: PHP_INT_MAX - 100
        );
    }

    private function renderDebugBar($response) : mixed
    {
        $this->getManager()?->detach(
            'response.final',
            [$this, 'renderDebugBar']
        );

        if (!$response instanceof ResponseInterface) {
            return $response;
        }

        // if profiler disabled, stop here!
        $profiler = ContainerHelper::use(ProfilerInterface::class, $this->getContainer());
        $waterfall = ContainerHelper::use(Waterfall::class, $this->getContainer());
        if (!$profiler?->isEnable() || ! $waterfall || !DataType::isHtmlContentType($response)) {
            return $response;
        }

        // DO STOP BENCHMARK
        $benchmark = ($profiler->getGroup('response')
            ??$profiler->getGroup('manager'))
            ?->get('response.final');
        $benchmark?->stop([
            'duration' => $benchmark->convertMicrotime(microtime(true)) - $benchmark->getStartTime(),
            'stopped' => true
        ]);
        $benchmark = $profiler
            ->getGroup('httpKernel')
            ?->get('httpKernel.dispatch');
        $benchmark?->stop([
            'duration' => $benchmark->convertMicrotime(microtime(true)) - $benchmark->getStartTime()
        ]);
        $benchmark?->setMetadataRecord('stopped', true);
        // END BENCHMARK
        $startTime = (
            $this->requestFloat
            ??$_SERVER['REQUEST_TIME_FLOAT']
            ??$profiler->getStartTime()
        );
        // get origin performance
        $performanceOrigin = microtime(true) - $startTime;
        $memoryOrigin = Consolidation::sizeFormat(memory_get_usage(), 3);

        // start
        $body = (string) $response->getBody();
        $streamFactory = $this->getStreamFactory();
        $darkMode = $this->darkMode;
        $found = false;
        if (str_contains($body, '<!--(waterfall)-->')) {
            $found = true;
            $body = preg_replace(
                '~<!--\(waterfall\)-->~',
                $waterfall->render(darkMode: $darkMode),
                $body,
                1
            );
        } else {
            $regexes = [
                '~(<(body)[^>]*>.*)(</\2>\s*</html>\s*(?:<\!\-\-.*)?)$~ism',
                '~(<(head)[^>]*>.*)(</\2>\s*<body>)~ism',
                '~(<(html)[^>]*>.*)(</\2>\s*(?:<\!\-\-.*)?)~ism',
            ];
            foreach ($regexes as $regex) {
                if (!preg_match($regex, $body)) {
                    continue;
                }
                $body = preg_replace_callback(
                    $regex,
                    static function ($match) use ($waterfall, $darkMode) {
                        return $match[1] . "\n" . $waterfall->render(darkMode: $darkMode) . "\n" . $match[3];
                    },
                    $body
                );
                $found = true;
                break;
            }
        }

        if (!$found) {
            $body .= "\n".$waterfall->render(darkMode: $darkMode)."\n";
        }
        // stop
        $waterfall = null;
        unset($waterfall);

        // doing clear
        $profiler->clear();
        $performanceEnd = microtime(true) - $startTime;
        return $response->withBody(
            $streamFactory->createStream(
                $body
                .sprintf(
                    '%s<!-- (rendered : %s ms / memory: %s) ~ (+waterfall ~ rendered : %s ms / memory: %s) -->',
                    "\n",
                    $profiler->convertMicrotime($performanceOrigin),
                    $memoryOrigin,
                    $profiler->convertMicrotime($performanceEnd),
                    Consolidation::sizeFormat(memory_get_usage(), 3),
                )
            )
        );
    }

    private function printPerformance(ResponseInterface $response): ResponseInterface
    {
        $this->getManager()?->detach(
            'response.final',
            [$this, 'printPerformance']
        );
        if (!DataType::isHtmlContentType($response)
            || ! $response->getBody()->isWritable()
        ) {
            return $response;
        }
        try {
            $response->getBody()->seek($response->getBody()->getSize());
        } catch (Throwable) {
            // pass
        }
        $startTime = (
            $this->requestFloat
            ??$_SERVER['REQUEST_TIME_FLOAT']
            ??$_SERVER['REQUEST_TIME']
        );
        $str = sprintf(
            '%s<!-- %s -->',
            "\n",
            sprintf(
                '(time: %s ms / peak memory usage: %s / memory usage: %s)',
                round(
                    (microtime(true) * 1000 - $startTime * 1000),
                    4
                ),
                Consolidation::sizeFormat(memory_get_peak_usage(), 3),
                Consolidation::sizeFormat(memory_get_usage(), 3)
            )
        );
        $response->getBody()->write($str);
        return $response;
    }
}
