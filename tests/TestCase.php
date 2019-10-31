<?php

namespace Tests;

use Statamic\Migrator\YAML;
use Illuminate\Filesystem\Filesystem;
use Statamic\Migrator\Concerns\PreparesPathFolder;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use PreparesPathFolder;

    protected function getPackageProviders($app)
    {
        return [
            \Statamic\Providers\StatamicServiceProvider::class,
            \Statamic\Migrator\ServiceProvider::class
        ];
    }

    protected function getPackageAliases($app)
    {
        return ['Statamic' => 'Statamic\Statamic'];
    }

    protected function setUp(): void
    {
        require_once(__DIR__.'/ExceptionHandler.php');

        parent::setUp();

        $this->files = app(Filesystem::class);

        $this->getPaths()->each(function ($path) {
            $this->deleteFolder($path);
            $this->prepareFolder($path);
        });

        $this->files->copyDirectory(__DIR__.'/Fixtures/site', base_path('site'));

        if (! $this->files->exists(config_path('filesystems-original.php'))) {
            $this->files->copy(config_path('filesystems.php'), config_path('filesystems-original.php'));
        }

        $this->restoreFilesystemConfig();
    }

    protected function tearDown(): void
    {
        $this->getPaths()->each(function ($path) {
            $this->deleteFolder($path);
        });

        $this->restoreFilesystemConfig();

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

    protected function sitePath($append = null)
    {
        return collect([base_path('site'), $append])->filter()->implode('/');
    }
}
