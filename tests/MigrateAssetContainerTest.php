<?php

namespace Tests;

use Facades\Statamic\Console\Processes\Process;
use Statamic\Facades\Path;
use Statamic\Migrator\Configurator;
use Statamic\Migrator\YAML;
use Statamic\Support\Arr;

class MigrateAssetContainerTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->configurator = Configurator::file('filesystems.php');

        Process::swap(new \Statamic\Console\Processes\Process(__DIR__.'/../'));
    }

    protected function paths()
    {
        return [
            base_path('content/assets'),
            public_path('assets'),
            resource_path('blueprints'),
        ];
    }

    protected function containerPath($append = null)
    {
        return Path::tidy(collect([base_path('content/assets'), $append])->filter()->implode('/'));
    }

    protected function blueprintPath($append = null)
    {
        return Path::tidy(collect([resource_path('blueprints/assets'), $append])->filter()->implode('/'));
    }

    /** @test */
    public function it_migrates_yaml_config()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $expected = [
            'title' => 'Main Assets',
            'disk' => 'assets',
        ];

        $this->assertParsedYamlEquals($expected, $this->containerPath('main.yaml'));

        $this->assertFileNotExists($this->blueprintPath());
    }

    /** @test */
    public function it_migrates_assets_folder()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->assertCount(0, $this->files->allFiles(public_path('assets')));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertCount(3, $this->files->allFiles(public_path('assets')));
    }

    /** @test */
    public function it_migrates_assets_folder_if_assets_are_left_in_original_site_nested_path()
    {
        $this->files->put($this->sitePath('content/assets/main.yaml'), YAML::dump([
            'path' => 'content/somewhere/nested',
        ]));

        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('site/content/somewhere/nested'));

        $this->assertCount(0, $this->files->allFiles(public_path('assets')));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertCount(3, $this->files->allFiles(public_path('assets')));
    }

    /** @test */
    public function it_migrates_multiple_assets_folders()
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
    public function it_can_migrate_meta()
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
        $this->assertArrayHasKey('mime_type', $blankMeta);
        $this->assertEmpty($blankMeta['data']);

        $fullMeta = YAML::parse($this->files->get(public_path('assets/img/.meta/stetson.jpg.yaml')));

        $this->assertArrayHasKey('data', $fullMeta);
        $this->assertArrayHasKey('size', $fullMeta);
        $this->assertArrayHasKey('last_modified', $fullMeta);
        $this->assertArrayHasKey('width', $fullMeta);
        $this->assertArrayHasKey('height', $fullMeta);
        $this->assertArrayHasKey('mime_type', $fullMeta);
        $this->assertCount(2, $fullMeta['data']);
        $this->assertEquals('Fancy hat!', $fullMeta['data']['alt']);
        $this->assertEquals('15-24-1', $fullMeta['data']['focus']);
    }

    /** @test */
    public function it_can_force_migrate_meta()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->assertCount(0, $this->files->allFiles(public_path('assets'), true));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->files->put(public_path('assets/img/.meta/stetson.jpg.yaml'), <<<'EOT'
data:
  title: Test Title
EOT
        );

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main', '--force' => true]);

        $meta = YAML::parse($this->files->get(public_path('assets/img/.meta/stetson.jpg.yaml')));

        $this->assertArrayHasKey('data', $meta);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('last_modified', $meta);
        $this->assertArrayHasKey('width', $meta);
        $this->assertArrayHasKey('height', $meta);
        $this->assertCount(2, $meta['data']);
        $this->assertEquals('Fancy hat!', $meta['data']['alt']);
        $this->assertEquals('15-24-1', $meta['data']['focus']);
    }

    /** @test */
    public function it_can_migrate_with_custom_fieldset_meta()
    {
        $this->files->put($this->sitePath('content/assets/secondary.yaml'), YAML::dump([
            'title' => 'Secondary Assets',
            'path' => 'somewhere/nested/secondary',
            'fieldset' => 'asset_fields',
            'assets' => [
                'img/stetson.jpg' => [
                    'title' => 'A Hat',
                    'alt' => 'fancy hat',
                    'purchase' => 'amazon.texas/stetson',
                ],
            ],
        ]));

        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('secondary'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'secondary']);

        $expected = [
            'title' => 'Secondary Assets',
            'disk' => 'assets_secondary',
        ];

        $this->assertParsedYamlEquals($expected, $this->containerPath('secondary.yaml'));
        $this->assertFileExists($this->blueprintPath('secondary.yaml'));

        $meta = YAML::parse($this->files->get(public_path('assets/secondary/img/.meta/stetson.jpg.yaml')));

        $this->assertCount(3, $meta['data']);
        $this->assertEquals('A Hat', $meta['data']['title']);
        $this->assertEquals('fancy hat', $meta['data']['alt']);
        $this->assertEquals('amazon.texas/stetson', $meta['data']['purchase']);
        $this->assertArrayHasKey('size', $meta);
        $this->assertArrayHasKey('last_modified', $meta);
        $this->assertArrayHasKey('width', $meta);
        $this->assertArrayHasKey('height', $meta);
    }

    /** @test */
    public function it_can_migrate_only_meta()
    {
        $this->files->put($this->sitePath('content/assets/secondary.yaml'), YAML::dump([
            'title' => 'Secondary Assets',
            'path' => 'somewhere/nested/secondary',
            'fieldset' => 'asset_fields',
            'assets' => [
                'img/stetson.jpg' => [
                    'title' => 'A Hat',
                    'alt' => 'fancy hat',
                    'purchase' => 'amazon.texas/stetson',
                ],
            ],
        ]));

        $this->configurator->mergeSpaciously('disks', [
            'assets_secondary' => [
                'driver' => 'local',
                'root' => public_path('assets/secondary'),
                'url' => '/assets/secondary',
                'visibility' => 'public',
            ],
        ]);

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'secondary', '--meta-only' => true]);

        $meta = YAML::parse($this->files->get(public_path('assets/secondary/img/.meta/stetson.jpg.yaml')));

        // Assert no yaml config or assets get copied over.
        $this->assertFileNotExists($this->containerPath('secondary.yaml'));
        $this->assertFileNotExists($this->blueprintPath('secondary.yaml'));
        $this->assertCount(0, $this->files->allFiles(base_path('content/assets')));

        // Assert meta is still generated.
        $this->assertCount(3, $meta['data']);
        $this->assertEquals('A Hat', $meta['data']['title']);
        $this->assertEquals('fancy hat', $meta['data']['alt']);
        $this->assertEquals('amazon.texas/stetson', $meta['data']['purchase']);
    }

    /** @test */
    public function it_can_migrate_meta_into_s3_path()
    {
        $this->files->put($this->sitePath('content/assets/secondary.yaml'), YAML::dump([
            'title' => 'Secondary Assets',
            'driver' => 's3',
            'path' => 'somewhere/nested',
            'fieldset' => 'asset_fields',
            'assets' => [
                'img/stetson.jpg' => [
                    'title' => 'A Hat',
                    'alt' => 'fancy hat',
                    'purchase' => 'amazon.texas/stetson',
                ],
            ],
        ]));

        // Fake container so that we can query it's assets.
        $this->files->put(base_path('content/assets/secondary.yaml'), 'disk: assets_secondary');

        // Fake S3 connection so that we can test `path` on `s3` driver without actually hitting s3.
        $this->configurator->mergeSpaciously('disks', [
            'assets_secondary' => [
                'driver' => 'local',
                'root' => public_path('assets/secondary'),
                'url' => '/assets/secondary',
                'visibility' => 'public',
            ],
        ]);

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'secondary', '--meta-only' => true]);

        $this->assertFileNotExists('assets/secondary/img/.meta/stetson.jpg.yaml');

        $meta = YAML::parse($this->files->get(public_path('assets/secondary/somewhere/nested/img/.meta/stetson.jpg.yaml')));

        $this->assertCount(3, $meta['data']);
        $this->assertEquals('A Hat', $meta['data']['title']);
        $this->assertEquals('fancy hat', $meta['data']['alt']);
        $this->assertEquals('amazon.texas/stetson', $meta['data']['purchase']);
    }

    /** @test */
    public function it_migrates_disk_with_local_driver()
    {
        $this->files->copyDirectory(__DIR__.'/Fixtures/assets', base_path('assets'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<'EOT'
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'assets' => [
            'driver' => 'local',
            'root' => public_path('assets'),
            'url' => '/assets',
            'visibility' => 'public',
            'throw' => false,
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets');

        $this->assertGitConfigPathExists('assets');
    }

    /** @test */
    public function it_migrates_disk_with_s3_driver()
    {
        $this->files->put($this->sitePath('content/assets/main.yaml'), YAML::dump([
            'title' => 'Main Assets',
            'driver' => 's3',
            'key' => 'some-key',
            'secret' => 'some-secret',
            'bucket' => 'some-bucket',
            'region' => 'some-region',
            'url' => '/cloud',
            'path' => 'cloud',
            'cache' => 3600, // TODO: need to handle s3 filesystem caching?
        ]));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<'EOT'
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'assets' => [
            'driver' => 's3',
            'key' => env('ASSETS_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_AWS_BUCKET'),
            'url' => env('ASSETS_AWS_URL'),
            'endpoint' => env('ASSETS_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('ASSETS_AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],
EOT
        );

        $this->assertFilesystemDiskExists('local');
        $this->assertFilesystemDiskExists('public');
        $this->assertFilesystemDiskExists('s3');
        $this->assertFilesystemDiskExists('assets');

        $this->assertGitConfigPathNotExists('assets');
    }

    /** @test */
    public function it_migrates_disk_with_terser_key_when_assets_already_exists()
    {
        $this->configurator->mergeSpaciously('disks', [
            'assets' => [
                'driver' => 'local',
                'root' => "storage_path('app/some-other-user-assets-unrelated-to-statamic')",
                'throw' => false,
            ],
        ]);

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main']);

        $this->assertFilesystemConfigFileContains(<<<'EOT'
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'assets' => [
            'driver' => 'local',
            'root' => storage_path('app/some-other-user-assets-unrelated-to-statamic'),
            'throw' => false,
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
            'throw' => false,
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
    public function it_migrates_multiple_disks_with_terser_keys_only()
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

        $this->assertFilesystemConfigFileContains(<<<'EOT'
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
            'throw' => false,
        ],

        'assets_cloud' => [
            'driver' => 's3',
            'key' => env('ASSETS_CLOUD_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_CLOUD_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_CLOUD_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_CLOUD_AWS_BUCKET'),
            'url' => env('ASSETS_CLOUD_AWS_URL'),
            'endpoint' => env('ASSETS_CLOUD_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('ASSETS_CLOUD_AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
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
    public function it_overwrites_disks_when_forced()
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

        $this->configurator
            ->mergeSpaciously('disks', [
                'assets_main' => [
                    'driver' => 'local',
                    'root' => public_path('assets/main'),
                    'url' => '/assets/main/edited-route',
                    'visibility' => 'public',
                    'throw' => false,
                ],
                'assets_cloud' => [
                    'driver' => 'local',
                    'root' => public_path('assets/cloud'),
                    'url' => '/assets/cloud/edited-route',
                    'visibility' => 'public',
                    'throw' => false,
                ],
            ])
            ->refresh();

        $this->assertEquals('/assets/main/edited-route', config('filesystems.disks.assets_main.url'));
        $this->assertEquals('/assets/cloud/edited-route', config('filesystems.disks.assets_cloud.url'));

        $this->artisan('statamic:migrate:asset-container', ['handle' => 'main', '--force' => true]);
        $this->artisan('statamic:migrate:asset-container', ['handle' => 'cloud', '--force' => true]);

        $this->assertFilesystemConfigFileContains(<<<'EOT'
    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

        'assets_main' => [
            'driver' => 'local',
            'root' => public_path('assets/main'),
            'url' => '/assets/main',
            'visibility' => 'public',
            'throw' => false,
        ],

        'assets_cloud' => [
            'driver' => 's3',
            'key' => env('ASSETS_CLOUD_AWS_ACCESS_KEY_ID'),
            'secret' => env('ASSETS_CLOUD_AWS_SECRET_ACCESS_KEY'),
            'region' => env('ASSETS_CLOUD_AWS_DEFAULT_REGION'),
            'bucket' => env('ASSETS_CLOUD_AWS_BUCKET'),
            'url' => env('ASSETS_CLOUD_AWS_URL'),
            'endpoint' => env('ASSETS_CLOUD_AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('ASSETS_CLOUD_AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
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
        $configPath = config_path('filesystems.php');

        $config = $this->files->get($configPath);

        $config = $this->normalizeLocalConfig($config);
        $config = $this->normalizePublicConfig($config);
        $config = $this->normalizeS3Config($config);

        $beginning = <<<'EOT'
<?php

return [
EOT;

        $end = '];';

        $irrelevantConfig = "'default' => env('FILESYSTEM_DISK', 'local'),";

        // Since we're still supporting Laravel 8 for Statamic 3.3;
        // We can rip this out when we drop Laravel 8 support.
        if (version_compare(app()->version(), '9', '<')) {
            $irrelevantConfig = "'default' => env('FILESYSTEM_DRIVER', 'local'),";
        }

        // Assert valid PHP array.
        $this->assertEquals('array', gettype(include $configPath));

        // Assert begining and end of config is untouched.
        $this->assertStringContainsString($beginning, $config);
        $this->assertStringContainsString($end, $config);

        // Assert irrelevant config is untouched.
        $this->assertStringContainsString($irrelevantConfig, $config);

        // Assert config file contains specific content.
        return $this->assertStringContainsString($content, $config);
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

    /**
     * Assert git config path exists.
     *
     * @param string $publicRelativePath
     */
    protected function assertGitConfigPathExists($publicRelativePath)
    {
        $configPath = config_path('statamic/git.php');

        $this->assertStringContainsString("public_path('{$publicRelativePath}')", $this->files->get($configPath));

        return $this->assertContains(public_path($publicRelativePath), Arr::get(include $configPath, 'paths'));
    }

    /**
     * Assert git config path does not not exist.
     *
     * @param string $publicRelativePath
     */
    protected function assertGitConfigPathNotExists($publicRelativePath)
    {
        $configPath = config_path('statamic/git.php');

        $this->assertStringNotContainsString("public_path('{$publicRelativePath}')", $this->files->get($configPath));

        return $this->assertNotContains(public_path($publicRelativePath), Arr::get(include $configPath, 'paths'));
    }

    /**
     * Normalize `local` config for test assertions, since there are minor variations between laravel versions.
     *
     * @param string $config
     */
    protected function normalizeLocalConfig($config)
    {
        // Laravel 8 and earlier versions of 9
        $variants[] = <<<'EOT'
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],
EOT;

        // Current version
        $current = <<<'EOT'
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
EOT;

        foreach ($variants as $variant) {
            $config = $this->normalizeVariantInConfig($variant, $current, $config);
        }

        return $config;
    }

    /**
     * Normalize `public` config for test assertions, since there are minor variations between laravel versions.
     *
     * @param string $config
     */
    protected function normalizePublicConfig($config)
    {
        // Laravel 8 and earlier versions of 9
        $variants[] = <<<'EOT'
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],
EOT;

        // Current version
        $current = <<<'EOT'
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
EOT;

        foreach ($variants as $variant) {
            $config = $this->normalizeVariantInConfig($variant, $current, $config);
        }

        return $config;
    }

    /**
     * Normalize `s3` config for test assertions, since there are minor variations between laravel versions.
     *
     * @param string $config
     */
    protected function normalizeS3Config($config)
    {
        // Earlier versions of Laravel 8
        $variants[] = <<<'EOT'
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],
EOT;

        // Laravel 8 and earlier versions of 9
        $variants[] = <<<'EOT'
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ],
EOT;

        // Current version
        $current = <<<'EOT'
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
EOT;

        foreach ($variants as $variant) {
            $config = $this->normalizeVariantInConfig($variant, $current, $config);
        }

        return $config;
    }

    protected function normalizeVariantInConfig($variant, $current, $config)
    {
        return str_replace(
            $this->normalizeMultilineString($variant),
            $this->normalizeMultilineString($current),
            $this->normalizeMultilineString($config)
        );
    }
}
