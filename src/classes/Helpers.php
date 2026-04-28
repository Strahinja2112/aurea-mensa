<?php

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(__DIR__)));
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', ROOT);
}

if (!class_exists('Helpers')) {
    final class Helpers
    {
        public const WEBSITE_NAME = "Aurea Mensa";
        public const WEBSITE_DESCRIPTION = "Ekskluzivna kulinarska avantura u srcu Subotice.";
        public const WEBSITE_ADDRESS = "Vatroslava Lisinskog 26, Subotica";
        public const WEBSITE_PHONE = "+381 11 123 4567";
        public const WEBSITE_EMAIL = "rezervacije@aureamensa.rs";
        public const WEBSITE_WORKING_DAYS = "08:00 - 23:00";
        public const WEBSITE_WORKING_WEEKEND = "09:00 - 00:00";

        public static function getFullPath(string ...$segments): string
        {
            return self::joinPaths(BASE_PATH, ...$segments);
        }

        public static function joinPaths(string ...$segments): string
        {
            $filteredSegments = array_filter($segments, fn($segment) => $segment !== '');
            return implode(DIRECTORY_SEPARATOR, $filteredSegments);
        }

        public static function renderHead(string $pageTitle, array $extraGlobalCss = []): void
        {
            $siteName = self::WEBSITE_NAME;

            $defaultGlobalCss = ["globals.css", "landing.css", "responsive.css"];
            $allGlobalCss = array_merge($defaultGlobalCss, $extraGlobalCss);

            $cssLinks = "";

            foreach ($allGlobalCss as $file) {
                $url = self::getRelativePath('src/styles', $file);
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

        public static function getRelativePath(string ...$segments): string
        {
            $currentDir = rtrim(str_replace('\\', '/', realpath(dirname($_SERVER['SCRIPT_FILENAME']))), '/');
            $rootDir = rtrim(str_replace('\\', '/', is_dir(BASE_PATH) ? realpath(BASE_PATH) : realpath(ROOT)), '/');

            if ($currentDir === $rootDir) {
                $base = './';
            } else {
                $relativePart = str_replace($rootDir, '', $currentDir);
                $relativePart = trim($relativePart, '/');

                if ($relativePart === '') {
                    $base = './';
                } else {
                    $depth = count(explode('/', $relativePart));
                    $base = str_repeat('../', $depth);
                }
            }

            if (empty($segments)) {
                return $base;
            }

            return $base . implode('/', $segments);
        }

        public static function isValidPhone(string $phone): bool
        {
            $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
            return (bool)preg_match('/^(\+381|0)6[0-9]{7,8}$/', $cleanPhone);
        }

        public static function isStrongPassword(string $password): bool
        {
            $hasUppercase = preg_match('@[A-Z]@', $password);
            $hasNumber = preg_match('@[0-9]@', $password);
            $isLongEnough = strlen($password) >= 8;

            return $hasUppercase && $hasNumber && $isLongEnough;
        }
    }
}

if (!function_exists('db')) {
    function db(): Database
    {
        return Database::getNewOrExistingInstance();
    }
}