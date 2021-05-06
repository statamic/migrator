<?php

namespace Tests;

class MigrateRolesTest extends TestCase
{
    protected function path()
    {
        return resource_path('users/roles.yaml');
    }

    /** @test */
    public function it_migrates_roles()
    {
        $this->assertFileNotExists($this->path());

        $this->artisan('statamic:migrate:roles');

        $this->assertFileExists($this->path());
        $this->assertParsedYamlNotHasKey('d32e14fb-08c9-44c2-aaf8-21200852bafd', $this->path());
        $this->assertParsedYamlNotHasKey('e9dd93f8-b83a-426c-8d09-c01acfd269f6', $this->path());
        $this->assertParsedYamlHasKey('super_admin', $this->path());
        $this->assertParsedYamlHasKey('author', $this->path());
    }

    /** @test */
    public function it_migrates_general_permissions()
    {
        $this->assertPermissionMigratesTo('super', 'super');
        $this->assertPermissionMigratesTo('cp:access', 'access cp');
        $this->assertPermissionMigratesTo('updater', 'view updates');
        $this->assertPermissionMigratesTo('updater:update', 'perform updates');
        $this->assertPermissionMigratesTo('resolve_duplicates', 'resolve duplicate ids');

        // TODO: Do we need to migrate these from v2?
        // - 'content:view_drafts_on_frontend'
        // - importer
    }

    /** @test */
    public function it_migrates_pages_permissions()
    {
        $this->assertPermissionMigratesTo('pages:view', 'view pages entries');
        $this->assertPermissionMigratesTo('pages:edit', 'edit pages entries');
        $this->assertPermissionMigratesTo('pages:create', ['create pages entries', 'publish pages entries']);
        $this->assertPermissionMigratesTo('pages:delete', 'delete pages entries');
        $this->assertPermissionMigratesTo('pages:reorder', 'reorder pages entries');
    }

    /** @test */
    public function it_migrates_collection_permissions()
    {
        $this->assertPermissionMigratesTo('collections:blog:view', 'view blog entries');
        $this->assertPermissionMigratesTo('collections:blog:edit', ['edit blog entries', 'reorder blog entries']);
        $this->assertPermissionMigratesTo('collections:blog:create', ['create blog entries', 'publish blog entries']);
        $this->assertPermissionMigratesTo('collections:blog:delete', 'delete blog entries');

        $this->assertPermissionMigratesTo('collections:diary:view', 'view diary entries');
        $this->assertPermissionMigratesTo('collections:diary:edit', ['edit diary entries', 'reorder diary entries']);
        $this->assertPermissionMigratesTo('collections:diary:create', ['create diary entries', 'publish diary entries']);
        $this->assertPermissionMigratesTo('collections:diary:delete', 'delete diary entries');
    }

    /** @test */
    public function it_migrates_taxonomy_permissions()
    {
        $this->assertPermissionMigratesTo('taxonomies:tags:view', 'view tags terms');
        $this->assertPermissionMigratesTo('taxonomies:tags:edit', 'edit tags terms');
        $this->assertPermissionMigratesTo('taxonomies:tags:create', 'create tags terms');
        $this->assertPermissionMigratesTo('taxonomies:tags:delete', 'delete tags terms');

        $this->assertPermissionMigratesTo('taxonomies:colours:view', 'view colours terms');
        $this->assertPermissionMigratesTo('taxonomies:colours:edit', 'edit colours terms');
        $this->assertPermissionMigratesTo('taxonomies:colours:create', 'create colours terms');
        $this->assertPermissionMigratesTo('taxonomies:colours:delete', 'delete colours terms');
    }

    /** @test */
    public function it_migrates_asset_container_permissions()
    {
        $this->assertPermissionMigratesTo('assets:main:view', 'view main assets');
        $this->assertPermissionMigratesTo('assets:main:edit', ['edit main assets', 'move main assets', 'rename main assets']);
        $this->assertPermissionMigratesTo('assets:main:create', 'upload main assets');
        $this->assertPermissionMigratesTo('assets:main:delete', 'delete main assets');
    }

    /** @test */
    public function it_migrates_global_set_permissions()
    {
        $this->assertPermissionMigratesTo('globals:global:edit', 'edit global globals');
        $this->assertPermissionMigratesTo('globals:social:edit', 'edit social globals');
    }

    /** @test */
    public function it_migrates_user_management_permissions()
    {
        $this->assertPermissionMigratesTo('users:view', 'view users');
        $this->assertPermissionMigratesTo('users:edit', 'edit users');
        $this->assertPermissionMigratesTo('users:create', 'create users');
        $this->assertPermissionMigratesTo('users:delete', 'delete users');
        $this->assertPermissionMigratesTo('users:edit-passwords', 'change passwords');
        $this->assertPermissionMigratesTo('users:edit-roles', 'edit roles');
    }

    /** @test */
    public function it_migrates_form_permissions()
    {
        $this->files->copy($this->sitePath('settings/formsets/contact.yaml'), $this->sitePath('settings/formsets/job_application.yaml'));

        $this->assertPermissionMigratesTo('forms', [
            'view contact form submissions',
            'delete contact form submissions',
            'view job_application form submissions',
            'delete job_application form submissions',
        ]);
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

        $this->artisan('statamic:migrate:roles', ['--force' => true]);

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
