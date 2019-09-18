<?php

namespace Tests;

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

        if (method_exists($this, 'path')) {
            $this->prepareFolder($this->path());
        }
    }

    public function tearDown(): void
    {
        if (method_exists($this, 'path')) {
            $this->deleteFolder($this->path());
        }

        parent::tearDown();
    }

    protected function prepareFolder($path)
    {
        $folder = $this->getFolderFromPath($path);

        if (! $this->files->exists($folder)) {
            $this->files->makeDirectory($folder);
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
        return preg_replace('/(.*)\/[^\/]*/', '$1', $path);
    }
}
