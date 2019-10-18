<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Support\Arr;
use Statamic\Migrator\YAML;
use Tests\Console\Foundation\InteractsWithConsole;
use Statamic\Migrator\Concerns\DirectlyModifiesFilesystemConfig;

class MigrateAssetContainerTest extends TestCase
{
    use DirectlyModifiesFilesystemConfig;

    protected function setUp(): void
    {
        parent::setUp();

        // If doesn't exist, backup original config/filesystems.php.
        if (! $this->files->exists(config_path('filesystems-original.php'))) {
            $this->files->copy(config_path('filesystems.php'), config_path('filesystems-original.php'));
        }

        $this->restoreConfig();
    }

    protected function tearDown(): void
    {
        $this->restoreConfig();

        parent::tearDown();
    }

    protected function restoreConfig()
    {
        $this->files->copy(config_path('filesystems-original.php'), config_path('filesystems.php'));
    }

    protected function paths()
    {
        return [
            base_path('content/assets'),
            public_path('assets'),
        ];
    }

    protected function containerPath($append = null)
    {
        return collect([base_path('content/assets'), $append])->filter()->implode('/');
    }

    /** @test */
    function it_migrates_yaml_config()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $expected = [
            'title' => 'Main Assets',
            'disk' => 'assets',
        ];

        $this->assertParsedYamlEquals($expected, $this->containerPath('main.yaml'));
    }

    /** @test */
    function it_migrates_assets_folder()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->assertCount(0, $this->files->allFiles(public_path('assets')));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertCount(3, $this->files->allFiles(public_path('assets')));
    }

    /** @test */
    function it_migrates_multiple_assets_folders()
    {
        $this->files->put($this->sitePath('content/assets/secondary.yaml'), YAML::dump([
            'title' => 'Secondary Assets',
            'path' => 'somewhere/nested/secondary',
        ]));

        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('secondary'));

        $this->assertCount(0, $this->files->allFiles(public_path('assets')));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);
        $this->artisan('statamic:migrate:asset-container', ['handle' => 'secondary']);

        $this->assertCount(3, $this->files->allFiles(public_path('assets/main')));
        $this->assertCount(3, $this->files->allFiles(public_path('assets/secondary')));
    }

    /** @test */
    function it_can_migrate_meta()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->assertCount(0, $this->files->allFiles(public_path('assets'), true));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertCount(1, $this->files->allFiles(public_path('assets/.meta'), true));
        $this->assertCount(2, $this->files->allFiles(public_path('assets/img/.meta'), true));

        $blankMeta = YAML::parse($this->files->get(public_path('assets/.meta/harry-potter-screenplay.txt.yaml')));

        $this->assertArrayHasKey('data', $blankMeta);
        $this->assertArrayHasKey('size', $blankMeta);
        $this->assertArrayHasKey('last_modified', $blankMeta);
        $this->assertArrayHasKey('width', $blankMeta);
        $this->assertArrayHasKey('height', $blankMeta);
        $this->assertEmpty($blankMeta['data']);

        $fullMeta = YAML::parse($this->files->get(public_path('assets/img/.meta/stetson.jpg.yaml')));

        $this->assertArrayHasKey('data', $fullMeta);
        $this->assertArrayHasKey('size', $fullMeta);
        $this->assertArrayHasKey('last_modified', $fullMeta);
        $this->assertArrayHasKey('width', $fullMeta);
        $this->assertArrayHasKey('height', $fullMeta);
        $this->assertCount(2, $fullMeta['data']);
        $this->assertEquals('Fancy hat!', $fullMeta['data']['alt']);
        $this->assertEquals('15-24-1', $fullMeta['data']['focus']);
    }

    /** @test */
    function it_can_migrate_with_custom_fieldset_meta()
    {
        $this->files->put($this->sitePath('content/assets/secondary.yaml'), YAML::dump([
            'title' => 'Secondary Assets',
            'path' => 'somewhere/nested/secondary',
            'fieldset' => 'asset_fields',
            'assets' => [
                'img/stetson.jpg' => [
                    'title' => 'A Hat',
                    'alt' => 'fancy hat',
                    'purchase' => 'amazon.texas/stetson'
                ]
            ]
        ]));

        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('secondary'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'secondary']);

        $expected = [
            'title' => 'Secondary Assets',
            'disk' => 'assets_secondary',
            'blueprint' => 'asset_fields',
        ];

        $this->assertParsedYamlEquals($expected, $this->containerPath('secondary.yaml'));

        $meta = YAML::parse($this->files->get(public_path('assets/secondary/img/.meta/stetson.jpg.yaml')));

        $this->assertCount(3, $meta['data']);
        $this->assertEquals('A Hat', $meta['data']['title']);
        $this->assertEquals('fancy hat', $meta['data']['alt']);
        $this->assertEquals('amazon.texas/stetson', $meta['data']['purchase']);
    }

    /** @test */
    function it_can_migrate_only_meta()
    {
        $this->files->put($this->sitePath('content/assets/secondary.yaml'), YAML::dump([
            'title' => 'Secondary Assets',
            'path' => 'somewhere/nested/secondary',
            'fieldset' => 'asset_fields',
            'assets' => [
                'img/stetson.jpg' => [
                    'title' => 'A Hat',
                    'alt' => 'fancy hat',
                    'purchase' => 'amazon.texas/stetson'
                ]
            ]
        ]));

        $this->jamDiskIntoDrive(
            "'assets_secondary' => [
                'driver' => 'local',
                'root' => public_path('assets/secondary'),
                'url' => '/assets/secondary',
                'visibility' => 'public',
            ],"
        );

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'secondary', '--meta-only' => true]);

        $meta = YAML::parse($this->files->get(public_path('assets/secondary/img/.meta/stetson.jpg.yaml')));

        // Assert no yaml config or assets get copied over.
        $this->assertFileNotExists($this->containerPath('secondary.yaml'));
        $this->assertCount(0, $this->files->allFiles(base_path('content/assets')));

        // Assert meta is still generated.
        $this->assertCount(3, $meta['data']);
        $this->assertEquals('A Hat', $meta['data']['title']);
        $this->assertEquals('fancy hat', $meta['data']['alt']);
        $this->assertEquals('amazon.texas/stetson', $meta['data']['purchase']);
    }

    /** @test */
    function it_migrates_disk_into_default_laravel_config()
    {
        $this->files->copy(__DIR__.'/Fixtures/config/filesystem-default.php', config_path('filesystems.php'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<EOT
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'assets' => [
            'driver' => 'local',
            'root' => public_path('assets'),
            'url' => '/assets',
            'visibility' => 'public',
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets');
    }

    /** @test */
    function it_migrates_disk_into_sanely_user_edited_config()
    {
        $this->files->copy(__DIR__.'/Fixtures/config/filesystem-edited.php', config_path('filesystems.php'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<EOT
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'custom' => [
            'driver' => 'local',
            'root' => storage_path('app/custom'),
        ],

        'assets' => [
            'driver' => 'local',
            'root' => public_path('assets'),
            'url' => '/assets',
            'visibility' => 'public',
        ],

    ],
EOT
        );

        $this->assertFilesystemConfigFileContains(<<<EOT
    'extra-config' => [
        'from-some-other-package' => true
    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('custom');
        $this->assertFilesystemDiskExists('assets');
    }

    /** @test */
    function it_migrates_disk_into_weirdly_mangled_config()
    {
        $this->files->copy(__DIR__.'/Fixtures/config/filesystem-weird.php', config_path('filesystems.php'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<EOT
'disks' => [
        'assets' => [
            'driver' => 'local',
            'root' => public_path('assets'),
            'url' => '/assets',
            'visibility' => 'public',
        ],

    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
    ],
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
    ],
EOT
        );

        $this->assertFilesystemConfigFileContains(<<<EOT
'extra-config' => [
    'from-some-other-package' => true
],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets');
    }

    /** @test */
    function it_migrates_disk_with_s3_drivers()
    {
        $this->files->put($this->sitePath('content/assets/main.yaml'), YAML::dump([
            'title' => 'Main Assets',
            'driver' => 's3',
            'key' => 'some-key',
            'secret' => 'some-secret',
            'bucket' => 'some-bucket',
            'region' => 'some-region',
            'url' => '/cloud',
            'path' => 'cloud', // TODO: need to handle subfolder of the bucket?
            'cache' => 3600, // TODO: need to handle s3 filesystem caching?
        ]));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<EOT
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'assets' => [
            'driver' => 's3',
            'key' => env('ASSETS_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_AWS_BUCKET'),
            'url' => env('ASSETS_AWS_URL'),
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets');
    }

    /** @test */
    function it_migrates_disk_with_terser_key_when_assets_already_exists()
    {
        $this->files->copy(__DIR__.'/Fixtures/config/filesystem-assets-already-exists.php', config_path('filesystems.php'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<EOT
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'assets' => [
            'driver' => 'local',
            'root' => storage_path('app/some-other-user-assets-unrelated-to-statamic'),
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets');
        $this->assertFilesystemDiskExists('assets_main');
    }

    /** @test */
    function it_migrates_multiple_disks_with_terser_keys_only()
    {
        $this->files->put($this->sitePath('content/assets/cloud.yaml'), YAML::dump([
            'title' => 'Cloud Assets',
            'driver' => 's3',
            'key' => 'some-key',
            'secret' => 'some-secret',
            'bucket' => 'some-bucket',
            'region' => 'some-region',
            'url' => '/cloud',
        ]));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);
        $this->artisan('statamic:migrate:asset-container', ['handle' => 'cloud']);

        $this->assertFilesystemConfigFileContains(<<<EOT
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
        ],

        'assets_cloud' => [
            'driver' => 's3',
            'key' => env('ASSETS_CLOUD_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_CLOUD_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_CLOUD_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_CLOUD_AWS_BUCKET'),
            'url' => env('ASSETS_CLOUD_AWS_URL'),
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets_main');
        $this->assertFilesystemDiskExists('assets_cloud');
        $this->assertFilesystemDiskNotExists('assets');
    }

    /** @test */
    function it_overwrites_disks_when_forced()
    {
        $this->files->put($this->sitePath('content/assets/cloud.yaml'), YAML::dump([
            'title' => 'Cloud Assets',
            'driver' => 's3',
            'key' => 'some-key',
            'secret' => 'some-secret',
            'bucket' => 'some-bucket',
            'region' => 'some-region',
            'url' => '/cloud',
        ]));

        $this->attemptGracefulDiskInsertion(<<<EOT
        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main/edited-route',
            'visibility' => 'public',
        ],
EOT
        );

        $this->attemptGracefulDiskInsertion(<<<EOT
        'assets_cloud' => [
            'driver' => 'local',
            'root' => public_path('assets/cloud'),
            'url' => '/assets/cloud/edited-route',
            'visibility' => 'public',
        ],
EOT
        );

        $this->refreshFilesystems();

        $this->assertEquals('/assets/main/edited-route', config('filesystems.disks.assets_main.url'));
        $this->assertEquals('/assets/cloud/edited-route', config('filesystems.disks.assets_cloud.url'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main', '--force' => true]);
        $this->artisan('statamic:migrate:asset-container', ['handle' => 'cloud', '--force' => true]);

        $this->assertFilesystemConfigFileContains(<<<EOT
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
        ],

        'assets_cloud' => [
            'driver' => 's3',
            'key' => env('ASSETS_CLOUD_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_CLOUD_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_CLOUD_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_CLOUD_AWS_BUCKET'),
            'url' => env('ASSETS_CLOUD_AWS_URL'),
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets_main');
        $this->assertFilesystemDiskExists('assets_cloud');
        $this->assertFilesystemDiskNotExists('assets');
    }

    /** @test */
    function it_overwrites_disks_in_weirdly_mangled_config_when_forced()
    {
        $this->files->copy(__DIR__.'/Fixtures/config/filesystem-weird.php', config_path('filesystems.php'));

        $this->files->put($this->sitePath('content/assets/cloud.yaml'), YAML::dump([
            'title' => 'Cloud Assets',
            'driver' => 's3',
            'key' => 'some-key',
            'secret' => 'some-secret',
            'bucket' => 'some-bucket',
            'region' => 'some-region',
            'url' => '/cloud',
        ]));

        $this->jamDiskIntoDrive(<<<EOT
        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main/edited-route',
            'visibility' => 'public',
        ],
EOT
        );

        $this->jamDiskIntoDrive(<<<EOT
        'assets_cloud' => [
            'driver' => 'local',
            'root' => public_path('assets/cloud'),
            'url' => '/assets/cloud/edited-route',
            'visibility' => 'public',
        ],
EOT
        );

        $this->refreshFilesystems();

        $this->assertEquals('/assets/main/edited-route', config('filesystems.disks.assets_main.url'));
        $this->assertEquals('/assets/cloud/edited-route', config('filesystems.disks.assets_cloud.url'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main', '--force' => true]);
        $this->artisan('statamic:migrate:asset-container', ['handle' => 'cloud', '--force' => true]);

        $this->assertFilesystemConfigFileContains(<<<EOT
'disks' => [
        'assets_cloud' => [
            'driver' => 's3',
            'key' => env('ASSETS_CLOUD_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_CLOUD_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_CLOUD_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_CLOUD_AWS_BUCKET'),
            'url' => env('ASSETS_CLOUD_AWS_URL'),
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
        ],

    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
    ],
    'public' => [
        'driver' => 'local',
        'root' => storage_path('app/public'),
        'url' => env('APP_URL').'/storage',
        'visibility' => 'public',
    ],
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
    ],
],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets_main');
        $this->assertFilesystemDiskExists('assets_cloud');
        $this->assertFilesystemDiskNotExists('assets');
    }

    /**
     * Assert filesystem config file replacement is valid and contains specific content.
     *
     * @param string $content
     */
    protected function assertFilesystemConfigFileContains($content)
    {
        $config = config_path('filesystems.php');

        $beginning = <<<EOT
<?php

return [
EOT;

        $end = '];';

        $irrelevantConfig = "'default' => env('FILESYSTEM_DRIVER', 'local'),";

        // Assert valid PHP array.
        $this->assertEquals('array', gettype(include $config));

        // Assert begining and end of config is untouched.
        $this->assertContains($beginning, $this->files->get($config));
        $this->assertContains($end, $this->files->get($config));

        // Assert irrelevant config is untouched.
        $this->assertContains($irrelevantConfig, $this->files->get($config));

        // Assert config file contains specific content.
        return $this->assertContains($content, $this->files->get($config));
    }

    /**
     * Assert filesystem disk array key exists.
     *
     * @param string $disk
     */
    protected function assertFilesystemDiskExists($disk)
    {
        return $this->assertTrue(Arr::has(include config_path('filesystems.php'), "disks.{$disk}"));
    }

    /**
     * Assert filesystem disk array key does not exist.
     *
     * @param string $disk
     */
    protected function assertFilesystemDiskNotExists($disk)
    {
        return $this->assertFalse(Arr::has(include config_path('filesystems.php'), "disks.{$disk}"));
    }
}
