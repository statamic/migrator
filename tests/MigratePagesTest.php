<?php

namespace Tests;

use Tests\TestCase;
use Tests\Console\Foundation\InteractsWithConsole;

class MigratePagesTest extends TestCase
{
    protected function paths()
    {
        return [
            base_path('content/collections'),
            base_path('content/structures'),
        ];
    }

    protected function collectionsPath($append = null)
    {
        return collect([base_path('content/collections'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_can_migrate_pages_to_a_collection_with_structure()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/site/content/pages', $this->collectionsPath('pages'));

        $this->assertFileNotExists($this->collectionsPath('../structures/pages.yaml'));
        $this->assertFileNotExists($this->collectionsPath('pages.yaml'));
        $this->assertCount(1, $this->files->files($this->collectionsPath('pages')));
        $this->assertCount(5, $this->files->directories($this->collectionsPath('pages')));

        $this->artisan('statamic:migrate:pages');

        $this->assertFileExists($this->collectionsPath('../structures/pages.yaml'));
        $this->assertFileExists($this->collectionsPath('pages.yaml'));
        $this->assertCount(9, $this->files->files($this->collectionsPath('pages')));
        $this->assertCount(0, $this->files->directories($this->collectionsPath('pages')));
    }
}
