<?php
declare(strict_types=1);

class Wishlist extends Model
{
    public function ids(int $farmerId): array
    {
        return array_map('intval', $this->run('SELECT equipment_id FROM wishlist WHERE farmer_id=?', [$farmerId])->fetchAll(PDO::FETCH_COLUMN));
    }

    public function has(int $farmerId, int $equipmentId): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM wishlist WHERE farmer_id=? AND equipment_id=?', [$farmerId, $equipmentId]);
    }

    public function add(int $farmerId, int $equipmentId): void
    {
        $this->run('INSERT INTO wishlist(farmer_id,equipment_id) VALUES(?,?)', [$farmerId, $equipmentId]);
    }

    public function remove(int $farmerId, int $equipmentId): int
    {
        return $this->run('DELETE FROM wishlist WHERE farmer_id=? AND equipment_id=?', [$farmerId, $equipmentId])->rowCount();
    }

    public function count(int $farmerId): int
    {
        return (int)$this->value('SELECT COUNT(*) FROM wishlist WHERE farmer_id=?', [$farmerId]);
    }

    /** Saved equipment with current details (always live data from the equipment table). */
    public function items(int $farmerId, string $q = '', string $availability = ''): array
    {
        $params = [$farmerId];
        $sql = "SELECT e.*, c.name category_name, c.icon category_icon, w.created_at saved_at,
                COALESCE(r.avg_rating,0) rating, COALESCE(r.review_count,0) review_count
            FROM wishlist w JOIN equipment e ON e.id=w.equipment_id JOIN categories c ON c.id=e.category_id
            LEFT JOIN (SELECT equipment_id, AVG(rating) avg_rating, COUNT(*) review_count FROM reviews GROUP BY equipment_id) r ON r.equipment_id=e.id
            WHERE w.farmer_id=?";
        if ($q !== '') {
            $sql .= ' AND (e.name LIKE ? OR e.location LIKE ?)';
            array_push($params, "%{$q}%", "%{$q}%");
        }
        if ($availability === 'available') {
            $sql .= " AND e.status='available' AND e.health_status IN ('good','unknown')";
        } elseif ($availability === 'unavailable') {
            $sql .= " AND (e.status<>'available' OR e.health_status IN ('needs_maintenance','under_maintenance'))";
        }
        return $this->all($sql . ' ORDER BY w.created_at DESC', $params);
    }
}
