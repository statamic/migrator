<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Facades\YAML;
use Statamic\Migrator\FieldsetMigrator;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateCollectionTest extends TestCase
{
    protected function path($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_can_migrate_a_collection()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/collections/blog', $this->path('blog'));

        $this->assertFileExists($this->path('blog/folder.yaml'));
        $this->assertFileNotExists($this->path('blog.yaml'));

        $this->artisan('statamic:migrate:collection', ['handle' => 'blog']);

        $this->assertFileNotExists($this->path('blog/folder.yaml'));
        $this->assertFileExists($this->path('blog.yaml'));
    }
}
