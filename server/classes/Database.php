<?php

declare(strict_types=1);

require_once BASE_PATH . '/server/db_config.php';

final class Database
{
    private static ?Database $instance = null;

    private PDO $pdo;

    public ORMTable $roles;
    public ORMTable $authSessions;

    public ORMTable $users;
    public ORMTable $verificationTokens;
    public ORMTable $restaurantTables;
    public ORMTable $reservations;
    public ORMTable $reservationTables;
    public ORMTable $reservationLogs;

    /**
     * @var array<string, list<string>>
     */
    private array $schema = [
        'roles' => ['id', 'name'],
        'auth_sessions' => [
            'id',
            'user_id',
            'token_hash',
            'expires_at',
            'created_at',
        ],
        'users' => [
            'id',
            'email',
            'password_hash',
            'first_name',
            'last_name',
            'phone',
            'role_id',
            'email_verified',
            'created_at',
        ],
        'verification_tokens' => [
            'id',
            'user_id',
            'token',
            'expires_at',
        ],
        'restaurant_tables' => [
            'id',
            'name',
            'seats',
            'location_description',
            'is_smoking',
        ],
        'reservations' => [
            'id',
            'user_id',
            'reservation_code',
            'reservation_date',
            'start_time',
            'end_time',
            'status',
            'created_at',
        ],
        'reservation_tables' => [
            'reservation_id',
            'table_id',
        ],
        'reservation_logs' => [
            'id',
            'reservation_id',
            'staff_id',
            'arrived',
            'comment',
            'created_at',
        ],
    ];

    private function __construct()
    {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=utf8mb4',
            Config::DB_HOST,
            Config::DB_NAME
        );

        $this->pdo = new PDO(
            $dsn,
            Config::DB_USER,
            Config::DB_PASSWORD,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );

        $this->roles = new ORMTable($this->pdo, 'roles', $this->schema['roles']);
        $this->authSessions = new ORMTable($this->pdo, 'auth_sessions', $this->schema['auth_sessions']);
        $this->users = new ORMTable($this->pdo, 'users', $this->schema['users']);
        $this->verificationTokens = new ORMTable(
            $this->pdo, 'verification_tokens', $this->schema['verification_tokens']
        );
        $this->restaurantTables = new ORMTable($this->pdo, 'restaurant_tables', $this->schema['restaurant_tables']);
        $this->reservations = new ORMTable($this->pdo, 'reservations', $this->schema['reservations']);
        $this->reservationTables = new ORMTable($this->pdo, 'reservation_tables', $this->schema['reservation_tables']);
        $this->reservationLogs = new ORMTable($this->pdo, 'reservation_logs', $this->schema['reservation_logs']);
    }

    private function __clone()
    {
        throw new Exception("Cannot clone singleton");
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public static function getNewOrExistingInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    public function beginTransaction(): bool
    {
        return $this->pdo->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    public function rollBack(): bool
    {
        return $this->pdo->rollBack();
    }
}
