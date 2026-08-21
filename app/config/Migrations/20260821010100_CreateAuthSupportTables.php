<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

final class CreateAuthSupportTables extends BaseMigration
{
    public function change(): void
    {
        $this->table('account_action_tokens', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('purpose', 'string', ['limit' => 32])
            ->addColumn('token_hash', 'string', ['limit' => 64])
            ->addColumn('expires_at', 'datetime')
            ->addColumn('used_at', 'datetime', ['null' => true])
            ->addColumn('invalidated_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['token_hash'], ['unique' => true])
            ->addIndex(['user_id', 'purpose'])
            ->addIndex(['expires_at'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE'])
            ->create();

        $this->table('account_action_attempts', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('normalized_email', 'string', ['limit' => 255])
            ->addColumn('user_id', 'biginteger', ['null' => true, 'signed' => false])
            ->addColumn('purpose', 'string', ['limit' => 32])
            ->addColumn('action', 'string', ['limit' => 32])
            ->addColumn('outcome', 'string', ['limit' => 32])
            ->addColumn('ip_address', 'string', ['limit' => 45, 'null' => true])
            ->addColumn('user_agent', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('created', 'datetime')
            ->addIndex(['normalized_email', 'purpose', 'created'])
            ->addIndex(['user_id'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL'])
            ->create();
    }
}
