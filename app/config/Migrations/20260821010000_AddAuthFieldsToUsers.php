<?php
declare(strict_types=1);

use Migrations\BaseMigration;

final class AddAuthFieldsToUsers extends BaseMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('terms_agreed_at', 'datetime', ['null' => true])
            ->addColumn('terms_version', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('failed_login_count', 'integer', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('locked_until', 'datetime', ['null' => true])
            ->addColumn('sessions_invalidated_at', 'datetime', ['null' => true])
            ->addIndex(['locked_until'])
            ->update();
    }
}
