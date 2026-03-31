<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

const APP_DIR = 'web-restaurant-app';

const BASE_PATH = ROOT . DIRECTORY_SEPARATOR . APP_DIR;

final class Helpers
{
    /**
     * Joins an unlimited number of path segments using the system directory separator.
     * * @param string ...$segments Unlimited path segments
     * @return string The combined path
     */
    public static function joinPaths(string ...$segments): string
    {
        $filteredSegments = array_filter($segments, fn($segment) => $segment !== '');

        return implode(DIRECTORY_SEPARATOR, $filteredSegments);
    }

    /**
     * Returns the full path starting from the BASE_PATH.
     *
     * @param string ...$segments Additional path segments to append to BASE_PATH
     * @return string
     */
    public static function getFullPath(string ...$segments): string
    {
        return self::joinPaths(BASE_PATH, ...$segments);
    }

    // TODO: ovo valja zavrsiti kad dodjes kuci
    public static function getRelativePageLocation(string ...$segments): string
    {

    }
}