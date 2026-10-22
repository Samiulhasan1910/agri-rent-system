<?php
declare(strict_types=1);

/**
 * Smart equipment matching. Predefined crop + farming stage rules map to equipment
 * categories (e.g. Rice + Harvest -> Harvester). Matches are ranked by rule priority,
 * farm-size fit, rating and current availability.
 */
class CropRule extends Model
{
    public const SEASONS = [
        'land_preparation' => 'Land Preparation',
        'planting' => 'Planting / Sowing',
        'growing' => 'Growing / Irrigation',
        'harvest' => 'Harvest',
    ];

    public function crops(): array
    {
        return $this->run('SELECT DISTINCT crop FROM crop_rules ORDER BY crop')->fetchAll(PDO::FETCH_COLUMN);
    }

    public function cropExists(string $crop): bool
    {
        return (bool)$this->value('SELECT COUNT(*) FROM crop_rules WHERE LOWER(crop)=LOWER(?)', [trim($crop)]);
    }

    /** Ranked suggestions (top $limit). */
    public function suggest(float $farmSize, string $crop, string $season, int $limit = 6): array
    {
        $rules = $this->all('SELECT r.*, c.name category_name FROM crop_rules r JOIN categories c ON c.id=r.category_id
            WHERE LOWER(r.crop)=LOWER(?) AND r.season=? AND c.status=\'active\'', [trim($crop), $season]);
        if (!$rules) {
            return [];
        }
        $byCategory = [];
        foreach ($rules as $rule) {
            $byCategory[(int)$rule['category_id']] = $rule;
        }
        $equipment = (new Equipment())->inCategories(array_keys($byCategory));
        if (!$equipment) {
            return [];
        }
        $today = today();
        $free = Availability::freeUnits($equipment, $today, $today);

        $results = [];
        foreach ($equipment as $item) {
            $rule = $byCategory[(int)$item['category_id']];
            $fits = $farmSize >= (float)$rule['min_farm_size'] && ($rule['max_farm_size'] === null || $farmSize <= (float)$rule['max_farm_size']);
            $available = ($free[(int)$item['id']] ?? 0) > 0;
            $score = (int)$rule['priority'] + ($fits ? 10 : -25) + (float)$item['rating'] * 2 + ($available ? 5 : -40);
            $results[] = $item + [
                'score' => round($score, 1),
                'match_reason' => $rule['note'],
                'size_fit' => $fits,
                'available_now' => $available,
                'free_units' => $free[(int)$item['id']] ?? 0,
            ];
        }
        usort($results, static fn($a, $b) => $b['score'] <=> $a['score']);
        return array_slice($results, 0, $limit);
    }

    public function listAll(): array
    {
        return $this->all('SELECT r.*, c.name category_name FROM crop_rules r JOIN categories c ON c.id=r.category_id
            ORDER BY r.crop, FIELD(r.season,\'land_preparation\',\'planting\',\'growing\',\'harvest\'), r.priority DESC');
    }

    public function create(array $data): int
    {
        return $this->insert('crop_rules', $data);
    }

    public function remove(int $id): int
    {
        return $this->run('DELETE FROM crop_rules WHERE id=?', [$id])->rowCount();
    }
}
