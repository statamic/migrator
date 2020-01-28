<?php

namespace Statamic\Migrator;

use Statamic\Support\Arr;
use Statamic\Support\Str;
use Symfony\Component\VarExporter\VarExporter;

class RoutesMigrator extends Migrator
{
    use Concerns\MigratesFile,
        Concerns\ThrowsFinalWarnings;

    /**
     * Perform migration.
     */
    public function migrate()
    {
        return $this->migrateRoutes()->throwFinalWarnings();
    }

    /**
     * Perform migration on routes.
     *
     * @return $this
     */
    protected function migrateRoutes()
    {
        $routes = $this->getSourceYaml($this->sitePath('settings/routes.yaml'));

        $php = $this->files->exists($path = base_path('routes/web.php'))
            ? $this->files->get($path)
            : '<?php';

        $str = $php . "\n\n"
            . $this->getRegularRoutes($routes['routes'] ?? []) . "\n\n"
            . $this->getVanityRedirects($routes['vanity'] ?? []) . "\n\n"
            . $this->getPermanentRedirects($routes['redirect'] ?? []);

        $str = rtrim($str) . "\n";

        $this->files->put($path, $str);

        return $this;
    }

    protected function getRegularRoutes($routes)
    {
        return collect($routes)
            ->map(function ($route) {
                return is_array($route) ? $route : ['template' => $route];
            })
            ->map(function ($route, $uri) {
                if (! $view = Arr::pull($route, 'template')) {
                    $this->addWarning("Route [$uri] was not migrated because it has no template.");
                    return null;
                }

                $str = vsprintf("Route::statamic('%s', '%s'", [
                    $this->removeLeadingSlash($uri),
                    str_replace('/', '.', $view)
                ]);

                if (! empty($route)) {
                    $str .= ', ' . VarExporter::export($route);
                }

                $str .= ');';

                return $str;
            })
            ->filter()
            ->join("\n");
    }

    protected function getVanityRedirects($redirects)
    {
        return collect($redirects)->map(function ($to, $from) {
            return vsprintf("Route::redirect('%s', '%s');", [
                $this->removeLeadingSlash($from),
                $this->removeLeadingSlash($to),
            ]);
        })->join("\n");
    }

    protected function getPermanentRedirects($redirects)
    {
        return collect($redirects)->map(function ($to, $from) {
            return vsprintf("Route::permanentRedirect('%s', '%s');", [
                $this->removeLeadingSlash($from),
                $this->removeLeadingSlash($to),
            ]);
        })->join("\n");
    }

    protected function removeLeadingSlash($str)
    {
        $str = Str::removeLeft($str, '/');
        return $str === '' ? '/' : $str;
    }
}
