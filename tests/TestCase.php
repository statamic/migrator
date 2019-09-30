<?php

namespace Tests;

use Statamic\Migrator\YAML;
use Illuminate\Filesystem\Filesystem;

class TestCase extends \Orchestra\Testbench\TestCase
{
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
    }

    protected function tearDown(): void
    {
        $this->getPaths()->each(function ($path) {
            $this->deleteFolder($path);
        });

        parent::tearDown();
    }

    protected function getPaths()
    {
        if (method_exists($this, 'path')) {
            $paths = collect($this->path());
        } elseif (method_exists($this, 'paths')) {
            $paths = collect($this->paths());
        }

        return $paths ?? collect();
    }

    protected function prepareFolder($path)
    {
        $folder = $this->getFolderFromPath($path);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder, 0755, true);
        }
    }

    protected function deleteFolder($path)
    {
        $folder = $this->getFolderFromPath($path);

        if ($this->files->exists($folder)) {
            $this->files->deleteDirectory($folder);
        }
    }

    protected function getFolderFromPath($path)
    {
        return preg_replace('/(.*)\/[^\/]+\.[^\/]+/', '$1', $path);
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
}
