<?php

namespace Tests;

use Tests\TestCase;
use Tests\Console\Foundation\InteractsWithConsole;

class MigrateRolesTest extends TestCase
{
    protected function path()
    {
        return resource_path('users/roles.yaml');
    }

    /** @test */
    function it_migrates_roles()
    {
        $this->assertFileNotExists($this->path());

        $this->artisan('statamic:migrate:roles');

        $this->assertFileExists($this->path());
        $this->assertParsedYamlNotHasKey('d32e14fb-08c9-44c2-aaf8-21200852bafd', $this->path());
        $this->assertParsedYamlNotHasKey('e9dd93f8-b83a-426c-8d09-c01acfd269f6', $this->path());
        $this->assertParsedYamlHasKey('admin', $this->path());
        $this->assertParsedYamlHasKey('author', $this->path());
    }

    /** @test */
    function it_migrates_permissions()
    {
        $this->assertPermissionMigratesTo('super', 'super');
        $this->assertPermissionMigratesTo('cp:access', 'access cp');

        $this->assertPermissionMigratesTo('pages:view', 'view pages entries');
        $this->assertPermissionMigratesTo('pages:edit', ['edit pages entries', 'reorder pages entries']);
        $this->assertPermissionMigratesTo('pages:create', ['create pages entries', 'publish pages entries']);
        $this->assertPermissionMigratesTo('pages:delete', 'delete pages entries');

        $this->assertPermissionMigratesTo('collections:blog:view', 'view blog entries');
        $this->assertPermissionMigratesTo('collections:blog:edit', ['edit blog entries', 'reorder blog entries']);
        $this->assertPermissionMigratesTo('collections:blog:create', ['create blog entries', 'publish blog entries']);
        $this->assertPermissionMigratesTo('collections:blog:delete', 'delete blog entries');

        $this->assertPermissionMigratesTo('taxonomies:blog:view', 'view blog terms');
        $this->assertPermissionMigratesTo('taxonomies:blog:edit', 'edit blog terms');
        $this->assertPermissionMigratesTo('taxonomies:blog:create', 'create blog terms');
        $this->assertPermissionMigratesTo('taxonomies:blog:delete', 'delete blog terms');

        $this->assertPermissionMigratesTo('globals:social:edit', 'edit social globals');

        $this->assertPermissionMigratesTo('updater', 'view updates');
        $this->assertPermissionMigratesTo('updater:update', 'perform updates');

        $this->assertPermissionMigratesTo('users:view', 'view users');
        $this->assertPermissionMigratesTo('users:edit', 'edit users');
        $this->assertPermissionMigratesTo('users:create', 'create users');
        $this->assertPermissionMigratesTo('users:delete', 'delete users');
        $this->assertPermissionMigratesTo('users:edit-passwords', 'change passwords');
        $this->assertPermissionMigratesTo('users:edit-roles', 'edit roles');
    }

    protected function assertPermissionMigratesTo($from, $to)
    {
        $this->files->put($this->sitePath('settings/users/roles.yaml'), <<<EOT
d32e14fb-08c9-44c2-aaf8-21200852bafd:
  title: 'Example Role'
  permissions:
    - $from
EOT
        );

        $this->artisan('statamic:migrate:roles');

        $this->assertParsedYamlNotHasKey('d32e14fb-08c9-44c2-aaf8-21200852bafd', $this->path());

        $expected = [
            'example_role' => [
                'title' => 'Example Role',
                'permissions' => collect($to)->all(),
            ],
        ];

        $this->assertParsedYamlContains($expected, $this->path());
    }
}
