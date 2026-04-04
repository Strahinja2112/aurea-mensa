<?php

final class ORMTable
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
     * @param array{
     *     where?: array<string, mixed>,
     *     select?: list<string>,
     *     orderBy?: array<string, 'ASC'|'DESC'>,
     *     take?: int,
     *     skip?: int
     * } $args
     * @return array<string, mixed>|null
     */
    public function findFirst(array $args = []): ?array
    {
        $rows = $this->findMany([
            'where' => $args['where'] ?? [],
            'select' => $args['select'] ?? null,
            'orderBy' => $args['orderBy'] ?? [],
            'take' => 1,
            'skip' => $args['skip'] ?? 0,
        ]);

        return $rows[0] ?? null;
    }

    /**
     * @param array{
     *     where?: array<string, mixed>,
     *     select?: list<string>,
     *     orderBy?: array<string, 'ASC'|'DESC'>,
     *     take?: int,
     *     skip?: int
     * } $args
     * @return list<array<string, mixed>>
     */
    public function findMany(array $args = []): array
    {
        $select = $this->buildSelect($args['select'] ?? null);
        $params = [];

        $whereSql = $this->buildWhere($args['where'] ?? [], $params);
        $orderSql = $this->buildOrderBy($args['orderBy'] ?? []);
        $limitSql = $this->buildLimitOffset($args['take'] ?? null, $args['skip'] ?? 0);

        $sql = "SELECT {$select} FROM `{$this->table}`{$whereSql}{$orderSql}{$limitSql}";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll();

        return $rows;
    }

    /**
     * @param array{
     *     where?: array<string, mixed>
     * } $args
     */
    public function count(array $args = []): int
    {
        $params = [];
        $whereSql = $this->buildWhere($args['where'] ?? [], $params);

        $sql = "SELECT COUNT(*) AS total FROM `{$this->table}`{$whereSql}";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        $result = $statement->fetch();

        return (int)($result['total'] ?? 0);
    }

    /**
     * @param array{
     *     data: array<string, mixed>
     * } $args
     * @return array<string, mixed>
     */
    public function create(array $args): array
    {
        $data = $args['data'] ?? [];

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

        if ($lastInsertId !== '' && $lastInsertId !== '0' && in_array('id', $this->columns, true)) {
            $created = $this->findFirst([
                'where' => ['id' => (int)$lastInsertId],
            ]);

            if ($created !== null) {
                return $created;
            }
        }

        return $data;
    }

    /**
     * @param array{
     *     where: array<string, mixed>,
     *     data: array<string, mixed>
     * } $args
     * @return array<string, mixed>|null
     */
    public function update(array $args): ?array
    {
        $where = $args['where'] ?? [];
        $data = $args['data'] ?? [];

        if ($where === []) {
            throw new InvalidArgumentException('update requires where.');
        }

        if ($data === []) {
            throw new InvalidArgumentException('update requires data.');
        }

        $this->assertValidColumns(array_keys($data));

        $params = [];
        $setParts = [];

        foreach ($data as $column => $value) {
            $paramName = 'set_' . $column;
            $setParts[] = "`{$column}` = :{$paramName}";
            $params[$paramName] = $value;
        }

        $whereSql = $this->buildWhere($where, $params);
        $setSql = implode(', ', $setParts);

        $existing = $this->findFirst(['where' => $where]);

        if ($existing === null) {
            return null;
        }

        $sql = "UPDATE `{$this->table}` SET {$setSql}{$whereSql} LIMIT 1";

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $this->findFirst(['where' => $where]);
    }

    /**
     * @param array{
     *     where: array<string, mixed>
     * } $args
     */
    public function deleteMany(array $args): int
    {
        $where = $args['where'] ?? [];

        if ($where === []) {
            throw new InvalidArgumentException('deleteMany requires where.');
        }

        $params = [];
        $whereSql = $this->buildWhere($where, $params);

        $sql = "DELETE FROM `{$this->table}`{$whereSql}";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * @param array{
     *     where: array<string, mixed>
     * } $args
     * @return array<string, mixed>|null
     */
    public function delete(array $args): ?array
    {
        $where = $args['where'] ?? [];

        if ($where === []) {
            throw new InvalidArgumentException('delete requires where.');
        }

        $existing = $this->findFirst(['where' => $where]);

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
     * @param list<string>|null $select
     */
    private function buildSelect(?array $select): string
    {
        if ($select === null || $select === []) {
            return '*';
        }

        $this->assertValidColumns($select);

        return implode(', ', array_map(
            fn(string $column): string => "`{$column}`",
            $select
        ));
    }

    /**
     * @param array<string, mixed> $where
     * @param array<string, mixed> $params
     */
    private function buildWhere(array $where, array &$params): string
    {
        if ($where === []) {
            return '';
        }

        $sql = $this->parseConditionGroup($where, $params);

        return $sql === '' ? '' : ' WHERE ' . $sql;
    }

    /**
     * @param array<string, mixed> $conditions
     * @param array<string, mixed> $params
     */
    private function parseConditionGroup(array $conditions, array &$params): string
    {
        $parts = [];

        foreach ($conditions as $key => $value) {
            if ($key === 'AND') {
                if (!is_array($value)) {
                    throw new InvalidArgumentException('AND must be an array.');
                }

                $andParts = [];

                foreach ($value as $item) {
                    if (!is_array($item)) {
                        throw new InvalidArgumentException('Each AND item must be an array.');
                    }

                    $parsed = $this->parseConditionGroup($item, $params);

                    if ($parsed !== '') {
                        $andParts[] = '(' . $parsed . ')';
                    }
                }

                if ($andParts !== []) {
                    $parts[] = implode(' AND ', $andParts);
                }

                continue;
            }

            if ($key === 'OR') {
                if (!is_array($value)) {
                    throw new InvalidArgumentException('OR must be an array.');
                }

                $orParts = [];

                foreach ($value as $item) {
                    if (!is_array($item)) {
                        throw new InvalidArgumentException('Each OR item must be an array.');
                    }

                    $parsed = $this->parseConditionGroup($item, $params);

                    if ($parsed !== '') {
                        $orParts[] = '(' . $parsed . ')';
                    }
                }

                if ($orParts !== []) {
                    $parts[] = '(' . implode(' OR ', $orParts) . ')';
                }

                continue;
            }

            $this->assertValidColumns([$key]);
            $parts[] = $this->parseColumnCondition($key, $value, $params);
        }

        return implode(' AND ', $parts);
    }

    /**
     * @param string $column
     * @param mixed $value
     * @param array<string, mixed> $params
     * @return string
     */
    private function parseColumnCondition(string $column, mixed $value, array &$params): string
    {
        if (!is_array($value)) {
            $paramName = $this->nextParamName($column, $params);

            if ($value === null) {
                return "`{$column}` IS NULL";
            }

            $params[$paramName] = $value;

            return "`{$column}` = :{$paramName}";
        }

        $parts = [];

        foreach ($value as $operator => $operatorValue) {
            $paramName = $this->nextParamName($column . '_' . $operator, $params);

            switch ($operator) {
                case 'equals':
                    if ($operatorValue === null) {
                        $parts[] = "`{$column}` IS NULL";
                        break;
                    }

                    $params[$paramName] = $operatorValue;
                    $parts[] = "`{$column}` = :{$paramName}";
                    break;

                case 'not':
                    if ($operatorValue === null) {
                        $parts[] = "`{$column}` IS NOT NULL";
                        break;
                    }

                    $params[$paramName] = $operatorValue;
                    $parts[] = "`{$column}` != :{$paramName}";
                    break;

                case 'gt':
                    $params[$paramName] = $operatorValue;
                    $parts[] = "`{$column}` > :{$paramName}";
                    break;

                case 'gte':
                    $params[$paramName] = $operatorValue;
                    $parts[] = "`{$column}` >= :{$paramName}";
                    break;

                case 'lt':
                    $params[$paramName] = $operatorValue;
                    $parts[] = "`{$column}` < :{$paramName}";
                    break;

                case 'lte':
                    $params[$paramName] = $operatorValue;
                    $parts[] = "`{$column}` <= :{$paramName}";
                    break;

                case 'contains':
                    $params[$paramName] = '%' . (string)$operatorValue . '%';
                    $parts[] = "`{$column}` LIKE :{$paramName}";
                    break;

                case 'startsWith':
                    $params[$paramName] = (string)$operatorValue . '%';
                    $parts[] = "`{$column}` LIKE :{$paramName}";
                    break;

                case 'endsWith':
                    $params[$paramName] = '%' . (string)$operatorValue;
                    $parts[] = "`{$column}` LIKE :{$paramName}";
                    break;

                case 'in':
                    if (!is_array($operatorValue) || $operatorValue === []) {
                        throw new InvalidArgumentException("Operator 'in' for {$column} must be a non-empty array.");
                    }

                    $inPlaceholders = [];

                    foreach ($operatorValue as $index => $item) {
                        $inParam = $this->nextParamName($column . '_in_' . $index, $params);
                        $params[$inParam] = $item;
                        $inPlaceholders[] = ':' . $inParam;
                    }

                    $parts[] = "`{$column}` IN (" . implode(', ', $inPlaceholders) . ")";
                    break;

                default:
                    throw new InvalidArgumentException("Unsupported operator '{$operator}' for column '{$column}'.");
            }
        }

        if ($parts === []) {
            throw new InvalidArgumentException("Invalid condition for column '{$column}'.");
        }

        return '(' . implode(' AND ', $parts) . ')';
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

    private function buildLimitOffset(?int $take, int $skip): string
    {
        if ($take === null) {
            return '';
        }

        if ($take < 1) {
            throw new InvalidArgumentException('take must be greater than 0.');
        }

        if ($skip < 0) {
            throw new InvalidArgumentException('skip must be 0 or greater.');
        }

        return " LIMIT {$take} OFFSET {$skip}";
    }

    /**
     * @param list<string> $columns
     */
    private function assertValidColumns(array $columns): void
    {
        foreach ($columns as $column) {
            if (!in_array($column, $this->columns, true)) {
                throw new InvalidArgumentException(
                    "Unknown column '{$column}' for table '{$this->table}'."
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $params
     */
    private function nextParamName(string $base, array $params): string
    {
        $base = preg_replace('/[^a-zA-Z0-9_]/', '_', $base) ?? 'param';
        $name = $base;
        $index = 1;

        while (array_key_exists($name, $params)) {
            $name = $base . '_' . $index;
            $index++;
        }

        return $name;
    }

    /**
     * Pokušava da ažurira postojeći red, a ako ga nema, kreira novi.
     * Korisno za sesije, brojače ili korisnička podešavanja.
     * * @param array{
     * where: array<string, mixed>,
     * update: array<string, mixed>,
     * create: array<string, mixed>
     * } $args
     * @return array<string, mixed>
     */
    public function upsert(array $args): array
    {
        $where = $args['where'] ?? [];
        $updateData = $args['update'] ?? [];
        $createData = $args['create'] ?? [];

        if ($where === []) {
            throw new InvalidArgumentException('upsert zahteva "where" uslov.');
        }

        $existing = $this->findFirst(['where' => $where]);

        if ($existing) {
            return $this->update([
                'where' => $where,
                'data' => $updateData
            ]) ?? $existing;
        }

        return $this->create([
            'data' => $createData
        ]);
    }
}