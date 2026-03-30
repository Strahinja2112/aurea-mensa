<?php

declare(strict_types=1);

class Helpers
{
    public static function getPathForRedirect(string $fileName): string|null
    {
        return BASE_URL . "\\" . $fileName;
    }
}