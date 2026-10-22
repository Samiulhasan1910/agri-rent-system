<?php
declare(strict_types=1);

/** Audit log of every price/pricing-rule change. */
class PriceLog extends Model
{
    public function log(?int $equipmentId, string $field, $old, $new, ?string $note, ?int $userId): void
    {
        $this->insert('price_change_logs', [
            'equipment_id' => $equipmentId,
            'field' => $field,
            'old_value' => $old === null ? null : mb_substr((string)$old, 0, 100),
            'new_value' => $new === null ? null : mb_substr((string)$new, 0, 100),
            'note' => $note ? mb_substr($note, 0, 255) : null,
            'changed_by' => $userId,
        ]);
    }

    public function recent(int $limit = 20): array
    {
        return $this->all('SELECT l.*, e.name equipment_name, u.name changed_by_name FROM price_change_logs l
            LEFT JOIN equipment e ON e.id=l.equipment_id LEFT JOIN users u ON u.id=l.changed_by ORDER BY l.id DESC LIMIT ' . (int)$limit);
    }
}
