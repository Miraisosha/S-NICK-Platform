<?php
declare(strict_types=1);

use Migrations\BaseMigration;

/**
 * Adds the remaining columns from the admin table spec
 * (docs/specifications/500_Admin.md §501 "管理者テーブルの主要項目") that
 * `CreateFoundationTables` didn't include: `admin_code` (a stable,
 * non-changeable admin number shown in screens/audits, mirroring
 * `users.account_number`), `name`, `role` (`admin` or `super_admin`), and
 * `mfa_enabled` (field only for now - no real 2FA flow yet, per the spec's
 * "導入フェーズは...詳細設計で確定する").
 */
final class ExtendAdminsTable extends BaseMigration
{
    public function change(): void
    {
        $this->table('admins')
            ->addColumn('admin_code', 'string', ['limit' => 32, 'after' => 'id'])
            ->addColumn('name', 'string', ['limit' => 255, 'after' => 'admin_code'])
            ->addColumn('role', 'string', ['default' => 'admin', 'limit' => 32, 'after' => 'password_hash'])
            ->addColumn('mfa_enabled', 'boolean', ['default' => false, 'after' => 'role'])
            ->addIndex(['admin_code'], ['unique' => true])
            ->update();
    }
}
