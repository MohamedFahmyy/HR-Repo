<?php

declare(strict_types=1);

namespace App\Core;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected bool $timestamps = true;
    protected bool $softDeletes = false;

    public function getTable(): string
    {
        return $this->table;
    }

    public function getPrimaryKey(): string
    {
        return $this->primaryKey;
    }

    public function find(int|string $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        if ($this->softDeletes) {
            $sql .= " AND `deleted_at` IS NULL";
        }
        $sql .= " LIMIT 1";

        return Database::fetchOne($sql, ['id' => $id]);
    }

    public function findOrFail(int|string $id): array
    {
        $record = $this->find($id);
        if (!$record) {
            throw new \RuntimeException("Record with ID {$id} not found in {$this->table}.");
        }
        return $record;
    }

    public function all(string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($this->softDeletes) {
            $sql .= " WHERE `deleted_at` IS NULL";
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY `{$orderBy}` {$direction}";

        return Database::fetchAll($sql);
    }

    public function where(string|array $column, mixed $value = null, string $operator = '='): array
    {
        if (is_array($column)) {
            $conditions = [];
            $params = [];
            $i = 0;
            foreach ($column as $col => $val) {
                $paramKey = "p_{$i}";
                $conditions[] = "`{$col}` = :{$paramKey}";
                $params[$paramKey] = $val;
                $i++;
            }
            $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $conditions);
            if ($this->softDeletes) {
                $sql .= " AND `deleted_at` IS NULL";
            }
            return Database::fetchAll($sql, $params);
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` {$operator} :val";
        if ($this->softDeletes) {
            $sql .= " AND `deleted_at` IS NULL";
        }

        return Database::fetchAll($sql, ['val' => $value]);
    }

    public function firstWhere(string|array $column, mixed $value = null, string $operator = '='): ?array
    {
        if (is_array($column)) {
            $conditions = [];
            $params = [];
            $i = 0;
            foreach ($column as $col => $val) {
                $paramKey = "p_{$i}";
                $conditions[] = "`{$col}` = :{$paramKey}";
                $params[$paramKey] = $val;
                $i++;
            }
            $sql = "SELECT * FROM `{$this->table}` WHERE " . implode(' AND ', $conditions);
            if ($this->softDeletes) {
                $sql .= " AND `deleted_at` IS NULL";
            }
            $sql .= " LIMIT 1";
            return Database::fetchOne($sql, $params);
        }

        $sql = "SELECT * FROM `{$this->table}` WHERE `{$column}` {$operator} :val";
        if ($this->softDeletes) {
            $sql .= " AND `deleted_at` IS NULL";
        }
        $sql .= " LIMIT 1";

        return Database::fetchOne($sql, ['val' => $value]);
    }

    public function create(array $data): int|string
    {
        $filtered = [];
        foreach ($this->fillable as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $filtered['created_at'] = $now;
            $filtered['updated_at'] = $now;
        }

        $fields = array_keys($filtered);
        $columns = implode('`, `', $fields);
        $placeholders = ':' . implode(', :', $fields);

        $sql = "INSERT INTO `{$this->table}` (`{$columns}`) VALUES ({$placeholders})";
        Database::execute($sql, $filtered);

        return Database::lastInsertId();
    }

    public function update(int|string $id, array $data): bool
    {
        $filtered = [];
        foreach ($this->fillable as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }

        if ($this->timestamps) {
            $filtered['updated_at'] = date('Y-m-d H:i:s');
        }

        $setPieces = [];
        $params = ['id' => $id];
        foreach ($filtered as $col => $val) {
            $setPieces[] = "`{$col}` = :set_{$col}";
            $params["set_{$col}"] = $val;
        }

        $setString = implode(', ', $setPieces);
        $sql = "UPDATE `{$this->table}` SET {$setString} WHERE `{$this->primaryKey}` = :id";

        return Database::execute($sql, $params);
    }

    public function delete(int|string $id): bool
    {
        if ($this->softDeletes) {
            $sql = "UPDATE `{$this->table}` SET `deleted_at` = :now WHERE `{$this->primaryKey}` = :id";
            return Database::execute($sql, ['now' => date('Y-m-d H:i:s'), 'id' => $id]);
        }

        $sql = "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = :id";
        return Database::execute($sql, ['id' => $id]);
    }

    public function count(string $whereClause = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}`";
        $conditions = [];

        if ($this->softDeletes) {
            $conditions[] = "`deleted_at` IS NULL";
        }
        if (!empty($whereClause)) {
            $conditions[] = $whereClause;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        return (int)Database::fetchColumn($sql, $params);
    }

    public function paginate(int $page = 1, int $perPage = 15, string $whereClause = '', array $params = [], string $orderBy = 'id', string $direction = 'DESC'): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        $total = $this->count($whereClause, $params);
        $lastPage = (int)ceil($total / $perPage);

        $sql = "SELECT * FROM `{$this->table}`";
        $conditions = [];

        if ($this->softDeletes) {
            $conditions[] = "`deleted_at` IS NULL";
        }
        if (!empty($whereClause)) {
            $conditions[] = $whereClause;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $sql .= " ORDER BY `{$orderBy}` {$direction} LIMIT {$perPage} OFFSET {$offset}";

        $items = Database::fetchAll($sql, $params);

        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => $lastPage,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $perPage, $total),
        ];
    }
}
