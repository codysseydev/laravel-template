<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * modules.php is hand-maintained. A module folder without an entry drops out of
 * isolation, static analysis and provider registration. An entry without a
 * folder breaks boot. This test keeps both in step.
 */
final class ModuleListTest extends TestCase
{
    #[Test]
    public function it_lists_every_module_folder_and_nothing_else(): void
    {
        $root = dirname(__DIR__, 2);
        $folders = [];

        foreach (new FilesystemIterator($root.'/'.ProductModules::namespace(), FilesystemIterator::SKIP_DOTS) as $entry) {
            if ($entry->isDir()) {
                $folders[] = $entry->getFilename();
            }
        }

        $modules = ProductModules::names();

        sort($folders);
        sort($modules);

        self::assertSame($folders, $modules);
    }

    #[Test]
    public function it_gives_every_module_a_service_provider_that_is_loaded(): void
    {
        foreach (ProductModules::names() as $module) {
            $provider = ProductModules::namespace().'\\'.$module.'\\'.$module.'ServiceProvider';

            self::assertTrue(class_exists($provider), $provider.' does not exist');
            self::assertTrue($this->app->providerIsLoaded($provider), $provider.' is not registered');
        }
    }

    #[Test]
    public function it_maps_the_product_namespace_in_composer(): void
    {
        $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, 512, JSON_THROW_ON_ERROR);

        self::assertIsArray($composer);
        self::assertIsArray($composer['autoload']);
        self::assertIsArray($composer['autoload']['psr-4']);
        self::assertSame(ProductModules::namespace().'/', $composer['autoload']['psr-4'][ProductModules::namespace().'\\'] ?? null);
    }
}
