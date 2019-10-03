<?php

namespace Statamic\Migrator\Concerns;

trait NormalizesPath
{
    /**
     * Normalize path, resolving `../` segments.
     *
     * Source: https://edmondscommerce.github.io/php/php-realpath-for-none-existant-paths.html
     *
     * @param string $path
     * @return string
     */
    protected function normalizePath($path)
    {
        return collect(explode('/', $path))->reduce(function ($a, $b) {
            if ($a === 0) $a = "/";
            if ($b === "" || $b === ".") return $a;
            if ($b === "..") return dirname($a);
            return preg_replace("/\/+/", "/", "$a/$b");
        });
    }
}
