<?php

declare(strict_types=1);

final class Logger
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'token',
        'token_hash',
        'api_key',
        'secret',
        'authorization',
        'cookie',
    ];

    private static bool $initialized = false;

    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }

        self::$initialized = true;

        ini_set('log_errors', '1');
        ini_set('error_log', self::getFallbackPhpErrorLogPath());

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function logException(Throwable $throwable, array $context = []): void
    {
        self::error($throwable->getMessage(), array_merge($context, [
            'exception' => get_class($throwable),
            'code' => $throwable->getCode(),
            'file' => $throwable->getFile(),
            'line' => $throwable->getLine(),
            'trace' => self::formatTrace($throwable),
        ]));
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        self::write(self::severityToLevel($severity), $message, [
            'severity' => $severity,
            'file' => $file,
            'line' => $line,
        ]);

        return false;
    }

    public static function handleException(Throwable $throwable): void
    {
        self::logException($throwable, ['uncaught' => true]);

        if (!headers_sent()) {
            $isJsonRequest = str_contains((string)($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json')
                || str_contains((string)($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')
                || str_contains((string)($_SERVER['SCRIPT_NAME'] ?? ''), '/src/actions/');

            if ($isJsonRequest) {
                http_response_code(500);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'Doslo je do neocekivane greske. Molimo pokusajte ponovo.',
                ]);
                return;
            }

            http_response_code(500);
        }

        if (Helpers::isDev()) {
            echo '<pre>' . htmlspecialchars((string)$throwable, ENT_QUOTES, 'UTF-8') . '</pre>';
            return;
        }

        echo 'Doslo je do neocekivane greske. Molimo pokusajte ponovo.';
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();

        if ($error === null || !self::isFatal($error['type'])) {
            return;
        }

        self::write('CRITICAL', $error['message'], [
            'severity' => $error['type'],
            'file' => $error['file'],
            'line' => $error['line'],
        ]);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => date(DATE_ATOM),
            'level' => $level,
            'message' => $message,
            'request' => self::requestContext(),
            'context' => self::sanitize($context),
        ];

        $encoded = json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            $encoded = json_encode([
                'timestamp' => date(DATE_ATOM),
                'level' => 'ERROR',
                'message' => 'Failed to encode log entry',
            ]);
        }

        $logDir = self::getLogDirectory();

        if (!is_dir($logDir) && !mkdir($logDir, 0775, true) && !is_dir($logDir)) {
            error_log((string)$encoded);
            return;
        }

        $path = $logDir . DIRECTORY_SEPARATOR . 'app-' . date('Y-m-d') . '.log';
        file_put_contents($path, $encoded . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    private static function requestContext(): array
    {
        return self::sanitize([
            'method' => $_SERVER['REQUEST_METHOD'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null,
            'script' => $_SERVER['SCRIPT_NAME'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
            'get' => $_GET ?? [],
            'post' => $_POST ?? [],
        ]);
    }

    private static function sanitize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];

        foreach ($value as $key => $item) {
            $keyString = strtolower((string)$key);

            if (self::isSensitiveKey($keyString)) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = self::sanitize($item);
        }

        return $sanitized;
    }

    private static function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private static function severityToLevel(int $severity): string
    {
        return match ($severity) {
            E_WARNING, E_USER_WARNING => 'WARNING',
            E_NOTICE, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT => 'NOTICE',
            default => 'ERROR',
        };
    }

    private static function isFatal(int $severity): bool
    {
        return in_array($severity, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_USER_ERROR,
            E_RECOVERABLE_ERROR,
        ], true);
    }

    private static function formatTrace(Throwable $throwable): array
    {
        return array_map(
            static fn(array $frame): array => [
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'function' => $frame['function'] ?? null,
                'class' => $frame['class'] ?? null,
            ],
            array_slice($throwable->getTrace(), 0, 20)
        );
    }

    private static function getLogDirectory(): string
    {
        return Helpers::getFullPath('logs');
    }

    private static function getFallbackPhpErrorLogPath(): string
    {
        return self::getLogDirectory() . DIRECTORY_SEPARATOR . 'php-errors.log';
    }
}
