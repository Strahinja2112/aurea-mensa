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
     * Essential for linking assets like CSS/JS in different subdirectories.
     */
    public static function getRelativePath(): string
    {
        // Get the directory of the file that is currently being executed
        $currentDir = realpath(dirname($_SERVER['SCRIPT_FILENAME']));
        $rootDir = realpath(BASE_PATH);
        
        // Remove the root path from the current directory path
        $relativePart = str_replace($rootDir, '', $currentDir);
        $relativePart = trim($relativePart, DIRECTORY_SEPARATOR);
        
        if ($relativePart === '') {
            return './';
        }
        
        // Count levels and add '../' for each level deep
        $depth = count(explode(DIRECTORY_SEPARATOR, $relativePart));
        return str_repeat('../', $depth);
    }
    
    /**
     * Includes the standard head elements, dynamically resolving asset paths.
     * Usage: Helpers::renderHead("Home Page");
     */
    public static function renderHead(string $pageTitle, array $extraCss = []): void
    {
        $relativePath = self::getRelativePath();
        $siteName = self::WEBSITE_NAME;
        
        // Osnovni CSS fajlovi koji idu na svaku stranicu
        $defaultCss = ["globals.css", "landing.css", "responsive.css"];
        
        // Spajamo podrazumevane sa dodatnim CSS fajlovima ako ih proslediš
        $allCss = array_merge($defaultCss, $extraCss);
        
        $cssLinks = "";
        foreach ($allCss as $fileName) {
            $fullPath = $relativePath . "styles/" . $fileName;
            $cssLinks .= "<link rel=\"stylesheet\" href=\"{$fullPath}\">\n    ";
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
}