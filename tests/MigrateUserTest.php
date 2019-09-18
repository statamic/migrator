<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Facades\YAML;
use Statamic\Migrator\Migrators\FieldsetMigrator;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateUserTest extends TestCase
{
    protected function paths($key = null)
    {
        $paths = [
            'old' => base_path('users/jack.yaml'),
            'new' => base_path('users/jack@example.com.yaml'),
        ];

        return $key ? $paths[$key] : $paths;
    }

    private function migrateUser($userConfig)
    {
        $this->files->put($this->paths('old'), YAML::dump($userConfig));

        $this->assertTrue($this->files->exists($this->paths('old')));
        $this->assertFalse($this->files->exists($this->paths('new')));

        $this->artisan('statamic:migrate:user', ['handle' => 'jack']);

        $this->assertFalse($this->files->exists($this->paths('old')));
        $this->assertTrue($this->files->exists($this->paths('new')));

        return YAML::parse($this->files->get($this->paths('new')));
    }

    /** @test */
    function it_can_migrate_a_user()
    {
        $user = $this->migrateUser([
            'name' => 'jack',
            'email' => 'jack@example.com',
            'password' => 'synthwave',
            'super' => true,
        ]);

        $this->assertEquals($user, [
            'name' => 'jack',
            'password' => 'synthwave',
            'super' => true,
        ]);
    }
}
