<?php
declare(strict_types=1);

use Migrations\BaseMigration;

final class SeedEventRoles extends BaseMigration
{
    private const ROLE_CODES = [
        'event_owner',
        'event_manager',
        'reception_progress',
        'marker',
        'sponsor_display',
        'streaming',
        'viewer',
    ];

    public function up(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->table('roles')
            ->insert([
                ['code' => 'event_owner', 'name' => 'イベント所有者', 'created' => $now, 'modified' => $now],
                ['code' => 'event_manager', 'name' => '大会運営管理者', 'created' => $now, 'modified' => $now],
                ['code' => 'reception_progress', 'name' => '受付・進行担当', 'created' => $now, 'modified' => $now],
                ['code' => 'marker', 'name' => 'マーカー', 'created' => $now, 'modified' => $now],
                ['code' => 'sponsor_display', 'name' => 'スポンサー・表示担当', 'created' => $now, 'modified' => $now],
                ['code' => 'streaming', 'name' => '配信担当', 'created' => $now, 'modified' => $now],
                ['code' => 'viewer', 'name' => '閲覧担当', 'created' => $now, 'modified' => $now],
            ])
            ->saveData();
    }

    public function down(): void
    {
        $codes = implode("', '", self::ROLE_CODES);
        $this->execute(sprintf("DELETE FROM roles WHERE code IN ('%s')", $codes));
    }
}
