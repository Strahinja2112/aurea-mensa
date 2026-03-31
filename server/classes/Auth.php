<?php

declare(strict_types=1);

final class Auth
{
    public const COOKIE_NAME = 'auth_token';
    private const COOKIE_LIFETIME = 7 * 24 * 60 * 60;

    public static function registerNewUser(array $data): array
    {
        // 1. Provera da li korisnik već postoji
        $existing = db()->users->findFirst([
            'where' => ['email' => $data['email']]
        ]);

        if ($existing) {
            throw new Exception("Korisnik sa ovom email adresom već postoji.");
        }

        // 2. Pronalaženje ID-a za 'USER' rolu (podrazumevana za registraciju)
        $role = db()->roles->findFirst([
            'where' => ['name' => 'USER']
        ]);

        if (!$role) {
            throw new Exception("Sistemska greška: Rola nije pronađena.");
        }

        // 3. Početak transakcije (bitno jer radimo u dve tabele)
        db()->beginTransaction();

        try {
            // Unos korisnika (is_active je DEFAULT FALSE po tvom DDL-u)
            $user = db()->users->create([
                'data' => [
                    'email' => $data['email'],
                    'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'role_id' => $role['id'],
                ]
            ]);

            // 4. Generisanje aktivacionog tokena
            $token = bin2hex(random_bytes(32));
            db()->passwordResets->create([
                'data' => [
                    'user_id' => $user['id'],
                    'token' => $token,
                    'expires_at' => date('Y-m-d H:i:s', strtotime('+24 hours'))
                ]
            ]);

            // 5. Slanje emaila
            $mailSent = Mailer::sendActivationEmail($user['email'], $token);

            if (!$mailSent) {
                throw new Exception("Greška prilikom slanja aktivacionog emaila.");
            }

            db()->commit();
            return $user;

        } catch (Exception $e) {
            db()->rollBack();
            throw $e;
        }
    }

    public static function signIn(string $email, string $password): bool
    {
        $user = db()->users->findFirst([
            'where' => ['email' => $email],
        ]);

        if (!$user) {
            return false;
        }
        if (!(int)$user['is_active']) {
            return false;
        }
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        self::createSession((int)$user['id']);

        return true;
    }

    public static function getUser(): ?array
    {
        $token = self::getToken();
        if (!$token) return null;

        $session = db()->authSessions->findFirst([
            'where' => [
                'token_hash' => hash('sha256', $token),
                'expires_at' => ['gt' => date('Y-m-d H:i:s')],
            ],
        ]);

        if (!$session) {
            self::clearCookie();
            return null;
        }

        return db()->users->findFirst([
            'where' => [
                'id' => $session['user_id'],
                'is_active' => 1,
            ],
        ]);
    }

    public static function signOut(): void
    {
        $token = self::getToken();

        if ($token) {
            db()->authSessions->delete([
                'where' => [
                    'token_hash' => hash('sha256', $token),
                ],
            ]);
        }

        self::clearCookie();
    }

    public static function isSignedIn(): bool
    {
        return self::getUser() !== null;
    }

    public static function requireAuth(): void
    {
        if (!self::isSignedIn()) {
            http_response_code(401);
            exit;
        }
    }

    private static function createSession(int $userId): void
    {
        db()->authSessions->deleteMany([
            'where' => ['user_id' => $userId],
        ]);

        $token = bin2hex(random_bytes(32));

        db()->authSessions->create([
            'data' => [
                'user_id' => $userId,
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + self::COOKIE_LIFETIME),
            ],
        ]);

        setcookie(self::COOKIE_NAME, $token, [
            'expires' => time() + self::COOKIE_LIFETIME,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function getToken(): ?string
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? null;

        if (!is_string($token) || $token === '') {
            return null;
        }

        return $token;
    }

    private static function clearCookie(): void
    {
        setcookie(self::COOKIE_NAME, '', time() - 3600, '/');
    }
}