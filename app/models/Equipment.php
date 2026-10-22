<?php
declare(strict_types=1);

class Equipment extends Model
{
    private const SELECT = "SELECT e.*, c.name category_name, c.icon category_icon,
            COALESCE(r.avg_rating,0) rating, COALESCE(r.review_count,0) review_count
        FROM equipment e
        JOIN categories c ON c.id=e.category_id
        LEFT JOIN (SELECT equipment_id, AVG(rating) avg_rating, COUNT(*) review_count FROM reviews GROUP BY equipment_id) r ON r.equipment_id=e.id";

    public const SORTS = [
        'newest' => 'e.id DESC',
        'price_low' => 'e.price_per_day ASC, e.id DESC',
        'price_high' => 'e.price_per_day DESC, e.id DESC',
        'rating' => 'rating DESC, review_count DESC, e.id DESC',
        'name' => 'e.name ASC',
    ];

    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . ' WHERE e.id=?', [$id]);
    }

    public function findDetailed(int $id): ?array
    {
        return $this->one("SELECT x.*, u.name added_by_name FROM (" . self::SELECT . " WHERE e.id=?) x JOIN users u ON u.id=x.added_by", [$id]);
    }

    /**
     * Catalogue search for farmers and staff.
     * Filters: q, category, location, min_price, max_price, availability (available|unavailable), status, health, sort, include_inactive
     */
    public function search(array $f, int $page = 1, int $per = 9): array
    {
        $where = [];
        $params = [];
        if (empty($f['include_inactive'])) {
            $where[] = "e.status<>'inactive'";
        }
        if (!empty($f['q'])) {
            $where[] = '(e.name LIKE ? OR e.description LIKE ? OR e.specs LIKE ? OR c.name LIKE ?)';
            array_push($params, "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%", "%{$f['q']}%");
        }
        if (!empty($f['category'])) {
            $where[] = 'e.category_id=?';
            $params[] = (int)$f['category'];
        }
        if (!empty($f['location'])) {
            $where[] = 'e.location LIKE ?';
            $params[] = "%{$f['location']}%";
        }
        if (isset($f['min_price']) && $f['min_price'] !== '') {
            $where[] = 'e.price_per_day >= ?';
            $params[] = (float)$f['min_price'];
        }
        if (isset($f['max_price']) && $f['max_price'] !== '') {
            $where[] = 'e.price_per_day <= ?';
            $params[] = (float)$f['max_price'];
        }
        if (!empty($f['status'])) {
            $where[] = 'e.status=?';
            $params[] = $f['status'];
        }
        if (!empty($f['health'])) {
            $where[] = 'e.health_status=?';
            $params[] = $f['health'];
        }
        if (($f['availability'] ?? '') === 'available') {
            $where[] = "e.status='available' AND e.health_status IN ('good','unknown')";
        } elseif (($f['availability'] ?? '') === 'unavailable') {
            $where[] = "(e.status<>'available' OR e.health_status IN ('needs_maintenance','under_maintenance'))";
        }
        $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $order = self::SORTS[$f['sort'] ?? 'newest'] ?? self::SORTS['newest'];

        $total = (int)$this->value("SELECT COUNT(*) FROM equipment e JOIN categories c ON c.id=e.category_id{$sqlWhere}", $params);
        $meta = paginate($total, $page, $per);
        $rows = $this->all(self::SELECT . $sqlWhere . " ORDER BY {$order} LIMIT {$meta['per']} OFFSET {$meta['offset']}", $params);
        return [$rows, $meta];
    }

    public function countListed(): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM equipment WHERE status<>'inactive'");
    }

    /** Lightweight catalogue used for the offline cache. */
    public function catalogue(): array
    {
        return $this->all(self::SELECT . " WHERE e.status<>'inactive' ORDER BY e.name");
    }

    public function options(): array
    {
        return $this->all("SELECT id,name,status,health_status FROM equipment WHERE status<>'inactive' ORDER BY name");
    }

    public function create(array $data): int
    {
        return $this->insert('equipment', $data);
    }

    public function edit(int $id, array $data): int
    {
        return $this->update('equipment', $id, $data);
    }

    public function activeRentals(int $id): int
    {
        return (int)$this->value("SELECT COUNT(*) FROM bookings WHERE equipment_id=? AND status IN ('pending','confirmed','delivered','returned')", [$id]);
    }

    /** Hard delete when there is no history, otherwise archive (status inactive) so records stay consistent. */
    public function remove(int $id): int
    {
        $history = (int)$this->value('SELECT COUNT(*) FROM bookings WHERE equipment_id=?', [$id])
            + (int)$this->value('SELECT COUNT(*) FROM emergency_requests WHERE equipment_id=?', [$id]);
        if ($history > 0) {
            return $this->run("UPDATE equipment SET status='inactive' WHERE id=?", [$id])->rowCount();
        }
        return $this->run('DELETE FROM equipment WHERE id=?', [$id])->rowCount();
    }

    public function reviews(int $id, int $limit = 10): array
    {
        return $this->all('SELECT r.*, u.name, u.profile_photo FROM reviews r JOIN users u ON u.id=r.farmer_id WHERE r.equipment_id=? ORDER BY r.id DESC LIMIT ' . (int)$limit, [$id]);
    }

    public function categoryTiles(): array
    {
        return $this->all("SELECT c.id, c.name, c.icon, COUNT(e.id) total FROM categories c
            LEFT JOIN equipment e ON e.category_id=c.id AND e.status<>'inactive' WHERE c.status='active' GROUP BY c.id ORDER BY c.id");
    }

    public function featured(int $limit = 6): array
    {
        return $this->all(self::SELECT . " WHERE e.status='available' ORDER BY rating DESC, e.id LIMIT " . (int)$limit);
    }

    public function similar(array $equipment, int $limit = 3): array
    {
        return $this->all(self::SELECT . " WHERE e.category_id=? AND e.id<>? AND e.status<>'inactive' ORDER BY rating DESC LIMIT " . (int)$limit, [$equipment['category_id'], $equipment['id']]);
    }

    /** Equipment in the given categories (used by smart matching). */
    public function inCategories(array $categoryIds): array
    {
        if (!$categoryIds) {
            return [];
        }
        return $this->all(self::SELECT . " WHERE e.category_id IN ({$this->in($categoryIds)}) AND e.status<>'inactive'", array_values($categoryIds));
    }

    /** Available equipment of a category within $radiusKm, nearest first (Haversine distance). */
    public function nearest(int $categoryId, float $lat, float $lng, float $radiusKm): array
    {
        $sql = "SELECT * FROM (
                    SELECT e.*, c.name category_name, c.icon category_icon,
                    (6371 * ACOS(LEAST(1, COS(RADIANS(?)) * COS(RADIANS(e.latitude)) * COS(RADIANS(e.longitude) - RADIANS(?)) + SIN(RADIANS(?)) * SIN(RADIANS(e.latitude))))) distance_km
                    FROM equipment e JOIN categories c ON c.id=e.category_id
                    WHERE e.category_id=? AND e.latitude IS NOT NULL AND e.longitude IS NOT NULL AND e.status='available'
                ) x WHERE x.distance_km <= ? ORDER BY x.distance_km ASC";
        return $this->all($sql, [$lat, $lng, $lat, $categoryId, $radiusKm]);
    }

    public function topRented(string $from, string $to, int $limit = 5): array
    {
        return $this->all("SELECT e.id, e.name, COUNT(b.id) rentals, COALESCE(SUM(b.days),0) days
            FROM equipment e JOIN bookings b ON b.equipment_id=e.id
            WHERE b.status NOT IN ('cancelled','rejected') AND b.start_date BETWEEN ? AND ?
            GROUP BY e.id ORDER BY rentals DESC, days DESC LIMIT " . (int)$limit, [$from, $to]);
    }

    public function healthList(array $f): array
    {
        $where = ["e.status<>'inactive'"];
        $params = [];
        if (!empty($f['health'])) {
            $where[] = 'e.health_status=?';
            $params[] = $f['health'];
        }
        if (!empty($f['q'])) {
            $where[] = 'e.name LIKE ?';
            $params[] = "%{$f['q']}%";
        }
        return $this->all("SELECT e.*, c.name category_name, c.icon category_icon,
                (SELECT COUNT(*) FROM maintenance_alerts a WHERE a.equipment_id=e.id AND a.status='open') open_alerts,
                (SELECT MAX(created_at) FROM usage_logs ul WHERE ul.equipment_id=e.id) last_usage_log
            FROM equipment e JOIN categories c ON c.id=e.category_id WHERE " . implode(' AND ', $where) . "
            ORDER BY FIELD(e.health_status,'needs_maintenance','under_maintenance','unknown','good'), e.name", $params);
    }

    public function healthCounts(): array
    {
        $counts = ['good' => 0, 'needs_maintenance' => 0, 'under_maintenance' => 0, 'unknown' => 0];
        foreach ($this->all("SELECT health_status, COUNT(*) n FROM equipment WHERE status<>'inactive' GROUP BY health_status") as $row) {
            $counts[$row['health_status']] = (int)$row['n'];
        }
        return $counts;
    }
}
