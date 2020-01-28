<?php

namespace Tests;

use Tests\TestCase;
use Facades\Statamic\Console\Processes\Process;

class MigrateRoutesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Process::swap(new \Statamic\Console\Processes\Process(__DIR__ . '/../'));
    }

    /** @test */
    function it_migrates_routes()
    {
        @mkdir(base_path('routes'));
        file_put_contents($path = base_path('routes/web.php'), <<<EOT
<?php

// Route::get('/', function () {
//     return view('welcome');
// });
EOT);

        $this->artisan('statamic:migrate:routes');

        $expected = <<<EOT
<?php

// Route::get('/', function () {
//     return view('welcome');
// });

Route::statamic('search', 'search');
Route::statamic('blog/feed', 'feeds.blog', [
    'layout' => 'feed',
    'content_type' => 'atom',
]);

Route::redirect('products', 'products-old');

Route::permanentRedirect('articles', '/');
Route::permanentRedirect('blog/posts', 'blog');

EOT;

        $this->assertFileHasContent($expected, $path);
        // TODO: Assert about warning when a route has no template.
    }
}
