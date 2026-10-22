<?php
declare(strict_types=1);

class Category extends Model
{
    public const ICONS = ['tractor', 'tiller', 'harvester', 'pump', 'sprayer', 'rotavator', 'seeder', 'thresher'];

    public function active(): array
    {
        return $this->all("SELECT * FROM categories WHERE status='active' ORDER BY name");
    }

    public function withCounts(string $q = ''): array
    {
        $params = [];
        $sql = "SELECT c.*, COUNT(e.id) equipment_count, COALESCE(SUM(e.quantity),0) unit_count
                FROM categories c LEFT JOIN equipment e ON e.category_id=c.id AND e.status<>'inactive'";
        if ($q !== '') {
            $sql .= ' WHERE c.name LIKE ?';
            $params[] = "%{$q}%";
        }
        return $this->all($sql . ' GROUP BY c.id ORDER BY c.id', $params);
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT * FROM categories WHERE id=?', [$id]);
    }

    public function nameExists(string $name, int $exceptId = 0): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM categories WHERE LOWER(name)=LOWER(?) AND id<>?', [trim($name), $exceptId]);
    }

    public function create(array $data): int
    {
        return $this->insert('categories', $data);
    }

    public function edit(int $id, array $data): int
    {
        return $this->update('categories', $id, $data);
    }

    public function equipmentCount(int $id): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM equipment WHERE category_id=? AND status<>'inactive'", [$id]);
    }

    public function delete(int $id): int
    {
        // archived (inactive) equipment keeps its history, so it blocks hard deletion: deactivate instead
        if ((int)$this->value('SELECT COUNT(*) FROM equipment WHERE category_id=?', [$id]) > 0) {
            return $this->run("UPDATE categories SET status='inactive' WHERE id=?", [$id])->rowCount() ?: 1;
        }
        return $this->run('DELETE FROM categories WHERE id=?', [$id])->rowCount();
    }
}
