<?php
declare(strict_types=1);

/** Idempotency records for actions queued offline and replayed after reconnecting. */
class SyncLog extends Model
{
    public function find(string $uuid): ?array
    {
        return $this->one('SELECT * FROM sync_log WHERE client_uuid=?', [$uuid]);
    }

    public function record(string $uuid, int $userId, string $type, string $result): void
    {
        $this->run('INSERT IGNORE INTO sync_log(client_uuid,user_id,type,result) VALUES(?,?,?,?)', [$uuid, $userId, $type, mb_substr($result, 0, 255)]);
    }
}
