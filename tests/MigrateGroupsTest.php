<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Support\Arr;
use Statamic\Facades\YAML;

class MigrateGroupsTest extends TestCase
{
    protected function path()
    {
        return resource_path('users/groups.yaml');
    }

    /** @test */
    function it_migrates_groups()
    {
        $this->assertFileNotExists($this->path());

        $this->artisan('statamic:migrate:groups');

        $this->assertFileExists($this->path());
        $this->assertParsedYamlNotHasKey('bc8b131b-4e01-4325-9fbc-598dff152855', $this->path());
        $this->assertParsedYamlHasKey('scribblers', $this->path());

        $groups = YAML::parse($this->files->get($this->path()));

        $expectedRoles = [
            'super_admin',
            'author',
        ];

        $this->assertEquals($expectedRoles, Arr::get($groups, 'scribblers.roles'));
    }
}
