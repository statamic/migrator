<?php

namespace Tests;

use Tests\TestCase;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateThemeTest extends TestCase
{
    protected function paths()
    {
        return [
            resource_path('views'),
        ];
    }

    protected function viewsPath($append = null)
    {
        return collect([resource_path('views'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_migrates_views()
    {
        $this->assertCount(0, $this->files->allFiles($this->viewsPath()));

        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertCount(25, $this->files->allFiles($this->viewsPath()));
        $this->assertCount(5, $this->files->directories($this->viewsPath()));
        $this->assertCount(9, $this->files->files($this->viewsPath()));

        $expectedUserViews = [
            'account.antlers.html',
            'index.antlers.html',
            'profile.antlers.html',
        ];

        $migratedUserViews = collect($this->files->files($this->viewsPath('user')))
            ->map
            ->getFilename()
            ->all();

        $this->assertEquals($expectedUserViews, $migratedUserViews);
    }
}
