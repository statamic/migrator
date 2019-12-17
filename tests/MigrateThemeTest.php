<?php

namespace Tests;

use Tests\TestCase;

class MigrateThemeTest extends TestCase
{
    protected function path()
    {
        return resource_path('views');
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

        $this->assertCount(33, $this->files->allFiles($this->viewsPath()));
        $this->assertCount(7, $this->files->directories($this->viewsPath()));
        $this->assertCount(10, $this->files->files($this->viewsPath()));
        $this->assertCount(2, $this->files->files($this->viewsPath('layouts')));
        $this->assertCount(4, $this->files->files($this->viewsPath('partials')));
        $this->assertCount(1, $this->files->files($this->viewsPath('partials/articles')));

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

    /** @test */
    function it_leaves_blade_extension_alone()
    {
        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileExists($this->viewsPath('not-antlers.blade.php'));
    }

    /** @test */
    function it_migrates_blade_global_modify_calls()
    {
        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $file = $this->files->get($this->viewsPath('not-antlers.blade.php'));

        $expected = '{{ \Statamic\Modifiers\Modify::value($content)->striptags() }}';

        $this->assertContains($expected, $file);
    }
}
