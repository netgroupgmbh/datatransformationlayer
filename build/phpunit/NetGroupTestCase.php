<?php

/**
 * @since       2026-02-26 - 06:21
 *
 * @author      Patrick Froch <info@netgroup.de>
 *
 * @see         http://www.netgroup.de
 *
 * @copyright   NetGroup GmbH 2026
 */
declare(strict_types=1);

namespace NetGroup\DataTransformationLayer;

use Contao\ManagerBundle\HttpKernel\ContaoKernel;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

class NetGroupTestCase extends ContaoTestCase
{


    /**
     * @var Container|null
     */
    protected ?ContainerInterface $container = null;


    /**
     * @var string
     */
    protected string $rootDir;


    /**
     * @param null   $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->rootDir = \dirname(__DIR__, 5);
    }


    /**
     * setup the environment
     */
    protected function setUp(): void
    {
    }


    /**
     * tear down the environment
     */
    protected function tearDown(): void
    {
    }


    /**
     * Bootet den Kernel und erstellt den Container.
     *
     * @return void
     */
    protected function createContainer(): void
    {
        if (!$this->container instanceof Container) {
            $kernel = new ContaoKernel('dev', true);
            $kernel::setDataTransformationLayerDir($this->rootDir);
            $kernel->boot();

            /** @var Container $container */
            $this->container = $kernel->getContainer();
        }
    }


    /**
     * Gbit die Namen der Services zurück.
     *
     * @return array
     */
    protected function getServices(): array
    {
        $this->createContainer();

        $removed    = $this->container->getRemovedIds() ?: [];
        $services   = $this->container->getServiceIds() ?: [];

        if (!empty($removed)) {
            $removed = \array_keys($removed);
        }

        $sorted = \array_merge($services, $removed);
        $sorted = \array_map(static fn($elem): string => \strtolower($elem), $sorted);
        sort($sorted);

        return $sorted;
    }


    /**
     * Gibt die Dateien unter Services zurück.
     *
     * @param string[] $files
     *
     * @return string[]
     */
    protected function getServiceFiles(array $files = []): array
    {
        $parts      = \explode('/build/', __DIR__);
        $pattern    = '|[[:alnum:]]*.php|';

        if (!empty($parts[0])) {
            $path           = $parts[0] . '/Classes/Services';
            $directory      = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
            $iterator       = new \RecursiveIteratorIterator($directory);
            $rexpIterator   = new \RegexIterator($iterator, $pattern);

            foreach ($rexpIterator as $file) {
                if (!\in_array($file->getFilename(), $files, true)) {
                    $files[] = \str_replace($this->rootDir . '/', '', $file->getPathname());
                }
            }
        }

        foreach ($files as $i => $file) {
            $files[$i] = \strtolower(\str_replace(['src/', 'vendor/', '.php', '/'], ['', '', '', '\\'], $file));
        }

        return $files;
    }


    /**
     * Ersetzt withConsecutive()
     * @param array ...$args
     * @return array
     * @see https://gist.github.com/ziadoz/370fe63e24f31fd1eb989e7477b9a472
     *
     * @example
     * $mock = $this->getMockBuilder(SomeClass::class)->getMock();
     *
     * $mock->expects($this->exactly(4))
     *      ->method('add')
     *      ->with(... $this->consecutiveParams(
     *          ['meta'],
     *          ['title'],
     *          ['caption'],
     *          ['alt']
     *      ))
     *      ->willReturnOnConsecutiveCalls(
     *          $meta,
     *          '',
     *          '',
     *          ''
     *      );
     */
    public function consecutiveParams(array ...$args): array
    {
        $callbacks = [];
        $count = count(max($args));

        for ($index = 0; $index < $count; $index++) {
            $returns = [];

            foreach ($args as $arg) {
                if (! array_is_list($arg)) {
                    throw new \InvalidArgumentException('Every array must be a list');
                }

                if (! isset($arg[$index])) {
                    throw new \InvalidArgumentException(sprintf('Every array must contain %d parameters', $count));
                }

                $returns[] = $arg[$index];
            }

            $callbacks[] = $this->callback(new class ($returns) {
                public function __construct(protected array $returns)
                {
                }

                public function __invoke(mixed $actual): bool
                {
                    if (count($this->returns) === 0) {
                        return true;
                    }

                    $next = array_shift($this->returns);
                    if ($next instanceof Constraint) {
                        $next->evaluate($actual);
                        return true;
                    }

                    return $actual === $next;
                }
            });
        }

        return $callbacks;
    }
}

