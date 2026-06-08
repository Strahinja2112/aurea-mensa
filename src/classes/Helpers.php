<?php

declare(strict_types=1);

if (!defined('ROOT')) {
    define('ROOT', dirname(dirname(__DIR__)));
}

if (!defined("APP_DIR")) {
    define('APP_DIR', '/aurea-mensa');
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

            $defaultGlobalCss = ["globals.css", "landing.css"];
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
                    <style>html,body{background:rgb(5,5,5);margin:0;padding:0}</style>
                    <link rel="preconnect" href="https://fonts.googleapis.com">
                    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&family=Libre+Baskerville:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">
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

        public static function generateReservationCode(): string
        {
            return strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
        }

        public static function getErrorUrl(ErrorType $type, ?string $message = null, ?string $redirectTo = null): string
        {
            $params = ['type' => $type->value];

            if ($message !== null) {
                $params['message'] = $message;
            }

            if ($redirectTo !== null) {
                $params['redirect'] = $redirectTo;
            }

            return self::getRelativePath('pages/error') . '?' . http_build_query($params);
        }

        public static function redirectToError(ErrorType $type, ?string $message = null, ?string $redirectTo = null): never
        {
            header('Location: ' . self::getErrorUrl($type, $message, $redirectTo));
            exit;
        }
    }
}

if (!function_exists('db')) {
    function db(): Database
    {
        return Database::getNewOrExistingInstance();
    }
}
