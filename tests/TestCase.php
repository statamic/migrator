<?php

namespace Tests;

use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Concerns\PreparesPathFolder;
use Statamic\Migrator\YAML;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use PreparesPathFolder;

    protected $siteFixture = 'site';

    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            \Statamic\Migrator\ServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return ['Statamic' => 'Statamic\Statamic'];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->files = app(Filesystem::class);

        $this->getPaths()->each(function ($path) {
            $this->deleteFolder($path);
            $this->prepareFolder($path);
        });

        $this->files->copyDirectory(__DIR__.'/Fixtures/'.$this->siteFixture, base_path('site'));

        if (! $this->files->exists(config_path('filesystems-original.php'))) {
            $this->files->copy(config_path('filesystems.php'), config_path('filesystems-original.php'));
        }

        $this->restoreFilesystemConfig();
        $this->restoreStatamicConfigs();
    }

    protected function tearDown(): void
    {
        $this->getPaths()->each(function ($path) {
            $this->deleteFolder($path);
        });

        $this->restoreFilesystemConfig();
        $this->restoreStatamicConfigs();

        parent::tearDown();
    }

    protected function getPaths()
    {
        if (method_exists($this, 'path')) {
            $paths = collect($this->path());
        } elseif (method_exists($this, 'paths')) {
            $paths = collect($this->paths());
        } else {
            $paths = collect();
        }

        $paths->push(base_path('site'));

        return $paths;
    }

    protected function restoreFilesystemConfig()
    {
        $this->files->copy(config_path('filesystems-original.php'), config_path('filesystems.php'));
    }

    protected function restoreStatamicConfigs()
    {
        $this->files->copyDirectory(__DIR__.'/../vendor/statamic/cms/config', config_path('statamic'));
    }

    protected function assertParsedYamlEquals($expected, $path)
    {
        return $this->assertEquals($expected, YAML::parse($this->files->get($path)));
    }

    protected function assertParsedYamlContains($expected, $path)
    {
        $parsed = collect(YAML::parse($this->files->get($path)));

        if (! is_array($expected)) {
            return $this->assertContains($expected, $parsed->all());
        }

        $key = key($expected);

        return $this->assertEquals($expected[$key], $parsed[$key]);
    }

    protected function assertParsedYamlHasKey($key, $path)
    {
        $parsed = collect(YAML::parse($this->files->get($path)));

        return $this->assertTrue($parsed->has($key));
    }

    protected function assertParsedYamlNotHasKey($key, $path)
    {
        $parsed = collect(YAML::parse($this->files->get($path)));

        return $this->assertFalse($parsed->has($key));
    }

    protected function assertContainsIgnoringLineEndings($expected, $actual)
    {
        $actual = str_replace("\r\n", "\n", $actual);

        $this->assertStringContainsString($expected, $actual);
    }

    protected function assertFileHasContent($expected, $path)
    {
        $this->assertFileExists($path);

        $this->assertStringContainsString($expected, $this->files->get($path));
    }

    protected function sitePath($append = null)
    {
        return collect([base_path('site'), $append])->filter()->implode('/');
    }

    protected static function normalizeMultilineString($string)
    {
        return str_replace("\r\n", "\n", $string);
    }

    /**
     * Normalize line endings before performing assertion in windows.
     */
    public static function assertStringContainsString($needle, $haystack, $message = '') : void
    {
        parent::assertStringContainsString(
            static::normalizeMultilineString($needle),
            static::normalizeMultilineString($haystack),
            $message
        );
    }

    /**
     * @deprecated
     */
    public static function assertFileNotExists(string $filename, string $message = '') : void
    {
        method_exists(static::class, 'assertFileDoesNotExist')
            ? static::assertFileDoesNotExist($filename, $message)
            : parent::assertFileNotExists($filename, $message);
    }
}
