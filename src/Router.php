<?php

namespace Statamic\Migrator;

use Statamic\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\VarExporter\VarExporter;

class Router
{
    protected $routesFile;

    /**
     * Instantiate router.
     *
     * @param string $routesFile
     */
    public function __construct($routesFile)
    {
        $this->routesFile = $routesFile;

        $this->files = app(Filesystem::class);
    }

    /**
     * Instantiate configurator.
     *
     * @param string $configFile
     * @return static
     */
    public static function file($configFile)
    {
        return new static($configFile);
    }

    /**
     * Append routes to routes file.
     *
     * @param array $routes
     */
    public function appendRoutes($routes)
    {
        if (! $routes) {
            return;
        }

        $this->appendBlankLine();

        collect($routes)->each(function ($to, $from) {
            if (is_string($to) && Str::contains($to, '@')) {
                return $this->appendControllerRoute($from, $to);
            } elseif (is_array($to)) {
                return $this->appendTemplateRouteWithData($from, $to);
            } else {
                return $this->appendTemplateRoute($from, $to);
            }
        });
    }

    /**
     * Append controller route.
     *
     * @param string $route
     * @param string $controllerAction
     */
    public function appendControllerRoute($route, $controllerAction)
    {
        $route = $this->normalizeRoute($route);

        $this->append("Route::statamic('{$route}', '{$controllerAction}');");
    }

    /**
     * Append template route with data.
     *
     * @param string $route
     * @param array $routeConfig
     */
    public function appendTemplateRouteWithData($route, $routeConfig)
    {
        $config = collect($routeConfig);

        $route = $this->normalizeRoute($route);
        $template = $this->normalizeTemplate($config->pull('template'));
        $data = VarExporter::export($config->all());

        $this->append("Route::statamic('{$route}', '{$template}', {$data});");
    }

    /**
     * Append template route.
     *
     * @param string $route
     * @param string $controllerAction
     */
    public function appendTemplateRoute($route, $template)
    {
        $route = $this->normalizeRoute($route);
        $template = $this->normalizeTemplate($template);

        $this->append("Route::statamic('{$route}', '{$template}');");
    }

    /**
     * Append redirects to routes file.
     *
     * @param array $redirects
     */
    public function appendRedirects($redirects, $routeMethod = 'redirect')
    {
        if (! $redirects) {
            return;
        }

        $this->appendBlankLine();

        collect($redirects)
            ->mapWithKeys(function ($to, $from) {
                return [$this->normalizeRoute($from) => $this->normalizeRoute($to)];
            })
            ->each(function ($to, $from) use ($routeMethod) {
                $this->append("Route::{$routeMethod}('{$from}', '{$to}');");
            });
    }

    /**
     * Append permanent redirects to routes file.
     *
     * @param array $redirects
     */
    public function appendPermanentRedirects($redirects)
    {
        $this->appendRedirects($redirects, 'permanentRedirect');
    }

    /**
     * Has if routes file has any of the given route definitions from v2 routes array.
     *
     * @param mixed $routes
     */
    public function has($routes)
    {
        return $this->hasAnyRouteDefinitions('statamic', $routes['routes'] ?? [])
            || $this->hasAnyRouteDefinitions('redirect', $routes['vanity'] ?? [])
            || $this->hasAnyRouteDefinitions('permanentRedirect', $routes['redirect'] ?? []);
    }

    /**
     * Check if routes file has any of the given route definitions.
     *
     * @param string $routeMethod
     * @param array $routes
     */
    protected function hasAnyRouteDefinitions($routeMethod, $routes)
    {
        return collect($routes)
            ->keys()
            ->map(function ($route) {
                return preg_quote($this->normalizeRoute($route), '/');
            })
            ->filter(function ($route) use ($routeMethod) {
                return preg_match("/^Route::{$routeMethod}\(['\"]{$route}['\"]/m", $this->getRoutesFileContents());
            })
            ->isNotEmpty();
    }

    /**
     * Get routes file path.
     *
     * @return string
     */
    protected function path()
    {
        return base_path("routes/{$this->routesFile}");
    }

    /**
     * Append content to routes file.
     *
     * @param string $content
     */
    protected function append($content)
    {
        if ($this->routeContentAlreadyExists($content)) {
            return;
        }

        $this->files->append($this->path(), "\n{$content}");
    }

    /**
     * Append blank line to routes file.
     */
    protected function appendBlankLine()
    {
        $this->files->append($this->path(), "\n");
    }

    /**
     * Get routes file contents.
     *
     * @return string
     */
    protected function getRoutesFileContents()
    {
        return $this->files->get($this->path());
    }

    /**
     * Check if routes file already contains the given content.
     *
     * @param string $content
     * @return bool
     */
    protected function routeContentAlreadyExists($content)
    {
        $pattern = '/' . preg_quote($content, '/') . '/m';

        return preg_match($pattern, $this->getRoutesFileContents());
    }

    /**
     * Normalize route.
     *
     * @param string $route
     * @return string
     */
    protected function normalizeRoute($route)
    {
        $route = Str::removeLeft($route, '/');

        return $route === '' ? '/' : $route;
    }

    /**
     * Normalize template.
     *
     * @param string $template
     * @return string
     */
    protected function normalizeTemplate($template)
    {
        return str_replace('/', '.', $template);
    }
}
