<?php

declare(strict_types=1);

require __DIR__ . "/db_config.php";

final class Db
{
	private PDO $pdo;

	/**
	 * @var array<string, list<string>>
	 */
	private array $schema = [
		'roles' => ['id', 'name'],
		'users' => [
			'id',
			'email',
			'password_hash',
			'first_name',
			'last_name',
			'phone',
			'role_id',
			'is_active',
			'created_at',
		],
		'password_resets' => [
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

	public function __construct()
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
	}

	public function pdo(): PDO
	{
		return $this->pdo;
	}

	public function table(string $table): DbTable
	{
		$this->assertTableExists($table);

		return new DbTable($this->pdo, $table, $this->schema[$table]);
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

	private function assertTableExists(string $table): void
	{
		if (!array_key_exists($table, $this->schema)) {
			throw new InvalidArgumentException("Unknown table: {$table}");
		}
	}
}

final class DbTable
{
	private PDO $pdo;

	private string $table;

	/**
	 * @var list<string>
	 */
	private array $columns;

	/**
	 * @param list<string> $columns
	 */
	public function __construct(PDO $pdo, string $table, array $columns)
	{
		$this->pdo = $pdo;
		$this->table = $table;
		$this->columns = $columns;
	}

	/**
	 * Prisma-like:
	 * $db->table('users')->findUnique(['id' => 1]);
	 *
	 * @param array<string, scalar|null> $where
	 * @param list<string>|null $select
	 * @return array<string, mixed>|null
	 */
	public function findUnique(array $where, ?array $select = null): ?array
	{
		if ($where === []) {
			throw new InvalidArgumentException('findUnique requires at least one where condition.');
		}

		$rows = $this->findMany($where, $select, 1);

		return $rows[0] ?? null;
	}

	/**
	 * Prisma-like:
	 * $db->table('users')->findFirst(['role_id' => 2], ['id', 'email']);
	 *
	 * @param array<string, scalar|null> $where
	 * @param list<string>|null $select
	 * @return array<string, mixed>|null
	 */
	public function findFirst(array $where = [], ?array $select = null): ?array
	{
		$rows = $this->findMany($where, $select, 1);

		return $rows[0] ?? null;
	}

	/**
	 * Prisma-like:
	 * $db->table('users')->findMany(['role_id' => 2], ['id', 'email'], 10, 0, ['id' => 'DESC']);
	 *
	 * @param array<string, scalar|null> $where
	 * @param list<string>|null $select
	 * @param array<string, 'ASC'|'DESC'> $orderBy
	 * @return list<array<string, mixed>>
	 */
	public function findMany(
		array  $where = [],
		?array $select = null,
		?int   $limit = null,
		int    $offset = 0,
		array  $orderBy = []
	): array
	{
		$selectedColumns = $this->buildSelect($select);
		$params = [];
		$whereSql = $this->buildWhere($where, $params);
		$orderSql = $this->buildOrderBy($orderBy);
		$limitSql = $this->buildLimitOffset($limit, $offset);

		$sql = "SELECT {$selectedColumns} FROM `{$this->table}`{$whereSql}{$orderSql}{$limitSql}";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		/** @var list<array<string, mixed>> $rows */
		$rows = $statement->fetchAll();

		return $rows;
	}

	/**
	 * @param array<string, scalar|null> $data
	 * @return array<string, mixed>
	 */
	public function create(array $data): array
	{
		if ($data === []) {
			throw new InvalidArgumentException('create requires data.');
		}

		$this->assertValidColumns(array_keys($data));

		$columns = array_keys($data);
		$columnSql = implode(', ', array_map(fn(string $column): string => "`{$column}`", $columns));
		$placeholderSql = implode(', ', array_map(fn(string $column): string => ":{$column}", $columns));

		$sql = "INSERT INTO `{$this->table}` ({$columnSql}) VALUES ({$placeholderSql})";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($data);

		$lastInsertId = $this->pdo->lastInsertId();

		if ($lastInsertId !== '0' && $lastInsertId !== '') {
			$created = $this->findUnique(['id' => (int)$lastInsertId]);

			if ($created !== null) {
				return $created;
			}
		}

		return $data;
	}

	/**
	 * @param array<string, scalar|null> $where
	 * @param array<string, scalar|null> $data
	 * @return array<string, mixed>|null
	 */
	public function update(array $where, array $data): ?array
	{
		if ($where === []) {
			throw new InvalidArgumentException('update requires where conditions.');
		}

		if ($data === []) {
			throw new InvalidArgumentException('update requires data.');
		}

		$this->assertValidColumns(array_keys($where));
		$this->assertValidColumns(array_keys($data));

		$setParts = [];
		$params = [];

		foreach ($data as $column => $value) {
			$setParts[] = "`{$column}` = :set_{$column}";
			$params["set_{$column}"] = $value;
		}

		$whereSql = $this->buildWhere($where, $params, 'where_');
		$setSql = implode(', ', $setParts);

		$sql = "UPDATE `{$this->table}` SET {$setSql}{$whereSql}";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		return $this->findFirst($where);
	}

	/**
	 * @param array<string, scalar|null> $where
	 * @return int
	 */
	public function updateMany(array $where, array $data): int
	{
		if ($where === []) {
			throw new InvalidArgumentException('updateMany requires where conditions.');
		}

		if ($data === []) {
			throw new InvalidArgumentException('updateMany requires data.');
		}

		$this->assertValidColumns(array_keys($where));
		$this->assertValidColumns(array_keys($data));

		$setParts = [];
		$params = [];

		foreach ($data as $column => $value) {
			$setParts[] = "`{$column}` = :set_{$column}";
			$params["set_{$column}"] = $value;
		}

		$whereSql = $this->buildWhere($where, $params, 'where_');
		$setSql = implode(', ', $setParts);

		$sql = "UPDATE `{$this->table}` SET {$setSql}{$whereSql}";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		return $statement->rowCount();
	}

	/**
	 * @param array<string, scalar|null> $where
	 * @return array<string, mixed>|null
	 */
	public function delete(array $where): ?array
	{
		if ($where === []) {
			throw new InvalidArgumentException('delete requires where conditions.');
		}

		$existing = $this->findFirst($where);

		if ($existing === null) {
			return null;
		}

		$params = [];
		$whereSql = $this->buildWhere($where, $params);

		$sql = "DELETE FROM `{$this->table}`{$whereSql} LIMIT 1";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		return $existing;
	}

	/**
	 * @param array<string, scalar|null> $where
	 * @return int
	 */
	public function deleteMany(array $where): int
	{
		if ($where === []) {
			throw new InvalidArgumentException('deleteMany requires where conditions.');
		}

		$params = [];
		$whereSql = $this->buildWhere($where, $params);

		$sql = "DELETE FROM `{$this->table}`{$whereSql}";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		return $statement->rowCount();
	}

	/**
	 * @param array<string, scalar|null> $where
	 */
	public function count(array $where = []): int
	{
		$params = [];
		$whereSql = $this->buildWhere($where, $params);

		$sql = "SELECT COUNT(*) AS total FROM `{$this->table}`{$whereSql}";

		$statement = $this->pdo->prepare($sql);
		$statement->execute($params);

		$result = $statement->fetch();

		return (int)($result['total'] ?? 0);
	}

	/**
	 * @param list<string>|null $select
	 */
	private function buildSelect(?array $select): string
	{
		if ($select === null || $select === []) {
			return '*';
		}

		$this->assertValidColumns($select);

		return implode(', ', array_map(fn(string $column): string => "`{$column}`", $select));
	}

	/**
	 * @param array<string, scalar|null> $where
	 * @param array<string, mixed> $params
	 */
	private function buildWhere(array $where, array &$params, string $prefix = ''): string
	{
		if ($where === []) {
			return '';
		}

		$this->assertValidColumns(array_keys($where));

		$parts = [];

		foreach ($where as $column => $value) {
			$paramName = $prefix . $column;

			if ($value === null) {
				$parts[] = "`{$column}` IS NULL";
				continue;
			}

			$parts[] = "`{$column}` = :{$paramName}";
			$params[$paramName] = $value;
		}

		return ' WHERE ' . implode(' AND ', $parts);
	}

	/**
	 * @param array<string, 'ASC'|'DESC'> $orderBy
	 */
	private function buildOrderBy(array $orderBy): string
	{
		if ($orderBy === []) {
			return '';
		}

		$this->assertValidColumns(array_keys($orderBy));

		$parts = [];

		foreach ($orderBy as $column => $direction) {
			$normalizedDirection = strtoupper($direction);

			if ($normalizedDirection !== 'ASC' && $normalizedDirection !== 'DESC') {
				throw new InvalidArgumentException("Invalid order direction for {$column}");
			}

			$parts[] = "`{$column}` {$normalizedDirection}";
		}

		return ' ORDER BY ' . implode(', ', $parts);
	}

	private function buildLimitOffset(?int $limit, int $offset): string
	{
		if ($limit === null) {
			return '';
		}

		if ($limit < 1) {
			throw new InvalidArgumentException('limit must be greater than 0.');
		}

		if ($offset < 0) {
			throw new InvalidArgumentException('offset must be 0 or greater.');
		}

		return " LIMIT {$limit} OFFSET {$offset}";
	}

	/**
	 * @param list<string> $columns
	 */
	private function assertValidColumns(array $columns): void
	{
		foreach ($columns as $column) {
			if (!in_array($column, $this->columns, true)) {
				throw new InvalidArgumentException("Unknown column '{$column}' for table '{$this->table}'.");
			}
		}
	}
}