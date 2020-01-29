<?php

namespace Tests;

use Tests\TestCase;
use Statamic\Migrator\YAML;
use Statamic\Migrator\Router;

class RouterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->files->copy(__DIR__ . '/Fixtures/routes/web.php', $this->path());

        $this->router = Router::file('test.php');

        $this->oldRoutes = YAML::parse($this->files->get(__DIR__ . '/Fixtures/site/settings/routes.yaml'));
    }

    protected function path()
    {
        return base_path('routes/test.php');
    }

    /** @test */
    function it_appends_routes()
    {
        $this->router->appendRoutes($this->oldRoutes['routes']);

        $this->assertRoutesFileContains(<<<EOT
Route::statamic('search', 'search');
Route::statamic('blog/tags', 'blog.taxonomies');
Route::statamic('blog/feed', 'feeds.blog', [
    'layout' => 'feed',
    'content_type' => 'atom',
]);
Route::statamic('complicated/stuff', 'ComplicatedController@stuff');
EOT
        );
    }

    /** @test */
    function it_appends_redirects()
    {
        $this->router->appendRedirects($this->oldRoutes['vanity']);

        $this->assertRoutesFileContains(<<<EOT
Route::redirect('products', 'products-old');
EOT
        );
    }

    /** @test */
    function it_appends_permanent_redirects()
    {
        $this->router->appendPermanentRedirects($this->oldRoutes['redirect']);

        $this->assertRoutesFileContains(<<<EOT
Route::permanentRedirect('articles', '/');
Route::permanentRedirect('blog/posts', 'blog');
EOT
        );
    }

    /**
     * Assert routes file contains specific content.
     *
     * @param string $content
     */
    protected function assertRoutesFileContains($content)
    {
        $contents = $this->files->get($this->path());

        $beginning = <<<EOT
<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('welcome');
// });
EOT;

        // Assert begining of routes file is untouched.
        $this->assertContains($beginning, $contents);

        // Assert routes file contains specific content.
        return $this->assertContains($content, $contents);
    }
}
