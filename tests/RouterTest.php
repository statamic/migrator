<?php

namespace Tests;

use Statamic\Facades\Path;
use Statamic\Migrator\Router;
use Statamic\Migrator\YAML;

class RouterTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->files->copy(__DIR__.'/Fixtures/routes/web.php', $this->path());

        $this->router = Router::file('test.php');

        $this->oldRoutes = YAML::parse($this->files->get(__DIR__.'/Fixtures/site/settings/routes.yaml'));
    }

    protected function path()
    {
        return Path::tidy(base_path('routes/test.php'));
    }

    /** @test */
    public function it_appends_routes()
    {
        $router = $this->router->appendRoutes($this->oldRoutes['routes']);

        $this->assertInstanceOf(Router::class, $router);

        $this->assertRoutesFileContains(<<<'EOT'
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
    public function it_appends_redirects()
    {
        $router = $this->router->appendRedirects($this->oldRoutes['vanity']);

        $this->assertInstanceOf(Router::class, $router);

        $this->assertRoutesFileContains(<<<'EOT'
Route::redirect('products', 'products-old');
EOT
        );
    }

    /** @test */
    public function it_appends_permanent_redirects()
    {
        $router = $this->router->appendPermanentRedirects($this->oldRoutes['redirect']);

        $this->assertInstanceOf(Router::class, $router);

        $this->assertRoutesFileContains(<<<'EOT'
Route::permanentRedirect('articles', '/');
Route::permanentRedirect('blog/posts', 'blog');
EOT
        );
    }

    /** @test */
    public function it_detects_if_routes_file_already_has_any_of_the_given_routes()
    {
        $this->assertFalse($this->router->has($this->oldRoutes));

        $router = $this->router->appendRoutes($this->oldRoutes['routes']);

        $this->assertInstanceOf(Router::class, $router);

        $this->assertTrue($this->router->has($this->oldRoutes));
    }

    /** @test */
    public function it_detects_if_routes_file_already_has_any_of_the_given_redirects()
    {
        $this->assertFalse($this->router->has($this->oldRoutes));

        $router = $this->router->appendRedirects($this->oldRoutes['vanity']);

        $this->assertInstanceOf(Router::class, $router);

        $this->assertTrue($this->router->has($this->oldRoutes));
    }

    /** @test */
    public function it_detects_if_routes_file_already_has_any_of_the_given_permanent_redirects()
    {
        $this->assertFalse($this->router->has($this->oldRoutes));

        $router = $this->router->appendPermanentRedirects($this->oldRoutes['redirect']);

        $this->assertInstanceOf(Router::class, $router);

        $this->assertTrue($this->router->has($this->oldRoutes));
    }

    /** @test */
    public function it_wont_append_the_same_route_twice()
    {
        $this->router->appendRoutes($this->oldRoutes['routes']);
        $this->router->appendRoutes($this->oldRoutes['routes']);
        $this->router->appendRoutes($this->oldRoutes['routes']);

        $this->router->appendRedirects($this->oldRoutes['vanity']);
        $this->router->appendRedirects($this->oldRoutes['vanity']);
        $this->router->appendRedirects($this->oldRoutes['vanity']);

        $this->router->appendPermanentRedirects($this->oldRoutes['redirect']);
        $this->router->appendPermanentRedirects($this->oldRoutes['redirect']);
        $this->router->appendPermanentRedirects($this->oldRoutes['redirect']);

        $this->assertRoutesFileContainsOnlyOnce("Route::statamic('search', 'search');");
        $this->assertRoutesFileContainsOnlyOnce("Route::redirect('products', 'products-old');");
        $this->assertRoutesFileContainsOnlyOnce("Route::permanentRedirect('blog/posts', 'blog');");
    }

    /**
     * Assert routes file contains specific content.
     *
     * @param  string  $content
     */
    protected function assertRoutesFileContains($content)
    {
        $contents = $this->files->get($this->path());

        $beginning = <<<'EOT'
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
        $this->assertStringContainsStringWithNormalizedLineEndings($beginning, $contents);

        // Assert routes file contains specific content.
        return $this->assertStringContainsStringWithNormalizedLineEndings($content, $contents);
    }

    /**
     * Assert routes file contains specific content.
     *
     * @param  string  $content
     */
    protected function assertRoutesFileContainsOnlyOnce($content)
    {
        $contents = $this->files->get($this->path());

        $pattern = preg_quote($content, '/');

        preg_match_all("/{$pattern}/", $contents, $matches);

        return $this->assertCount(1, $matches[0]);
    }
}
