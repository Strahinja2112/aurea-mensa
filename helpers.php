<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

const APP_DIR = 'web-restaurant-app';
const BASE_PATH = ROOT . DIRECTORY_SEPARATOR . APP_DIR;

final class Helpers
{
    public const WEBSITE_NAME = "Aurea Mensa";

    /**
     * Joins path segments using system directory separator.
     */
    public static function joinPaths(string ...$segments): string
    {
        $filteredSegments = array_filter($segments, fn($segment) => $segment !== '');
        return implode(DIRECTORY_SEPARATOR, $filteredSegments);
    }

    /**
     * Returns the full server path.
     */
    public static function getFullPath(string ...$segments): string
    {
        return self::joinPaths(BASE_PATH, ...$segments);
    }

    /**
     * Calculates the relative path from the current page to the project root.
     * Optionally appends additional segments to the path.
     */
    public static function getRelativePath(string ...$segments): string
    {
        $currentDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        $rootDir = realpath(BASE_PATH);

        $relativePart = str_replace($rootDir, '', $currentDir);
        $relativePart = trim($relativePart, DIRECTORY_SEPARATOR);

        $base = '';

        if ($relativePart !== '') {
            $depth = count(explode(DIRECTORY_SEPARATOR, $relativePart));
            $base = str_repeat('../', $depth);
        } else {
            $base = './';
        }

        if (empty($segments)) {
            return $base;
        }

        return $base . implode('/', $segments);
    }

    public static function loadLocalCssFile(string $file): string
    {
        $localPath = dirname($_SERVER['SCRIPT_FILENAME']) . DIRECTORY_SEPARATOR . $file;

        if (file_exists($localPath)) {
            return $file;
        }

        return self::getRelativePath('styles', $file);
    }

    public static function renderHead(string $pageTitle, array $localCss = [], array $extraGlobalCss = []): void
    {
        $siteName = self::WEBSITE_NAME;

        $defaultGlobalCss = ["globals.css", "landing.css", "responsive.css"];
        $allGlobalCss = array_merge($defaultGlobalCss, $extraGlobalCss);

        $cssLinks = "";

        foreach ($allGlobalCss as $file) {
            $url = self::getRelativePath('styles', $file);
            $cssLinks .= "<link rel=\"stylesheet\" href=\"{$url}\">\n    ";
        }

        foreach ($localCss as $file) {
            $url = self::loadLocalCssFile($file);
            $cssLinks .= "<link rel=\"stylesheet\" href=\"{$url}\">\n    ";
        }

        echo <<<HTML
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{$pageTitle} | {$siteName}</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        {$cssLinks}
    </head>
    HTML;
    }
    
    public static function isValidPhone(string $phone): bool
    {
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
        return (bool)preg_match('/^(\+381|0)6[0-9]{7,8}$/', $cleanPhone);
    }

    /**
     * Provera jačine lozinke
     * Uslovi: Min 8 karaktera, bar jedno veliko slovo, bar jedan broj
     */
    public static function isStrongPassword(string $password): bool
    {
        $hasUppercase = preg_match('@[A-Z]@', $password);
        $hasNumber = preg_match('@[0-9]@', $password);
        $isLongEnough = strlen($password) >= 8;

        return $hasUppercase && $hasNumber && $isLongEnough;
    }
}

function db(): Database
{
    return Database::getNewOrExistingInstance();
}