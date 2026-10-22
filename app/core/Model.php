<?php
declare(strict_types=1);

/** Base model with small query helpers around PDO prepared statements. */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    protected function run(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    protected function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row ?: null;
    }

    protected function value(string $sql, array $params = [])
    {
        $value = $this->run($sql, $params)->fetchColumn();
        return $value === false ? null : $value;
    }

    protected function insert(string $table, array $data): int
    {
        $columns = array_keys($data);
        $sql = sprintf(
            'INSERT INTO `%s` (`%s`) VALUES (%s)',
            $table,
            implode('`,`', $columns),
            implode(',', array_fill(0, count($columns), '?'))
        );
        $this->run($sql, array_values($data));
        return (int)$this->db->lastInsertId();
    }

    protected function update(string $table, int $id, array $data): int
    {
        $sets = implode(',', array_map(static fn($c) => "`{$c}`=?", array_keys($data)));
        return $this->run("UPDATE `{$table}` SET {$sets} WHERE id=?", [...array_values($data), $id])->rowCount();
    }

    /** Run a callback inside a transaction, rolling back on any exception. */
    public function transaction(callable $callback)
    {
        $outer = !$this->db->inTransaction();
        if ($outer) {
            $this->db->beginTransaction();
        }
        try {
            $result = $callback();
            if ($outer) {
                $this->db->commit();
            }
            return $result;
        } catch (Throwable $e) {
            if ($outer && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** Builds "IN (?,?,?)" placeholders. */
    protected function in(array $values): string
    {
        return implode(',', array_fill(0, max(1, count($values)), '?'));
    }
}
