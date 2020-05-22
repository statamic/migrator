<?php

namespace Tests;

class MigrateThemeTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'views' => resource_path('views'),
            'macros' => resource_path('macros.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    protected function viewsPath($append = null)
    {
        return collect([resource_path('views'), $append])->filter()->implode('/');
    }

    protected function redwoodPath($append)
    {
        return $this->sitePath("themes/redwood/{$append}");
    }

    /** @test */
    public function it_migrates_views()
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
    public function it_migrates_default_layout()
    {
        $this->assertCount(0, $this->files->allFiles($this->viewsPath()));

        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileNotExists($this->viewsPath('layouts/default.antlers.html'));
        $this->assertFileExists($this->viewsPath('layouts/layout.antlers.html'));
    }

    /** @test */
    public function it_migrates_custom_default_layout()
    {
        $this->files->put($this->sitePath('settings/theming.yaml'), 'default_layout: custom');

        $this->files->move(
            $this->sitePath('themes/redwood/layouts/default.html'),
            $this->sitePath('themes/redwood/layouts/custom.html')
        );

        $this->assertCount(0, $this->files->allFiles($this->viewsPath()));

        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileNotExists($this->viewsPath('layouts/default.antlers.html'));
        $this->assertFileNotExists($this->viewsPath('layouts/custom.antlers.html'));
        $this->assertFileExists($this->viewsPath('layouts/layout.antlers.html'));
    }

    /** @test */
    public function it_leaves_blade_extension_alone()
    {
        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileExists($this->viewsPath('not-antlers.blade.php'));
    }

    /** @test */
    public function it_migrates_theme_partial_tags()
    {
        $this->assertFileHasContent('{{ theme:partial src="nav" }}', $this->redwoodPath('layouts/default.html'));

        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileHasContent('{{ partial:nav }}', $this->viewsPath('layouts/layout.antlers.html'));
    }

    /** @test */
    public function it_migrates_blade_global_modify_calls()
    {
        $this->assertFileHasContent('{{ modify($content)->striptags() }}', $this->redwoodPath('templates/not-antlers.blade.php'));

        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileHasContent('{{ \Statamic\Modifiers\Modify::value($content)->striptags() }}', $this->viewsPath('not-antlers.blade.php'));
    }

    /** @test */
    public function it_migrates_macros()
    {
        $this->assertFileNotExists($this->paths('macros'));

        $this->artisan('statamic:migrate:theme', ['handle' => 'redwood']);

        $this->assertFileExists($this->paths('macros'));
        $this->assertParsedYamlHasKey('jake', $this->paths('macros'));
    }
}
