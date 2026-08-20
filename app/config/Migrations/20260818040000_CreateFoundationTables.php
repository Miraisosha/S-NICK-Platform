<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

final class CreateFoundationTables extends BaseMigration
{
    public function change(): void
    {
        $this->table('users', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('account_number', 'string', ['limit' => 32])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('status', 'string', ['default' => 'active', 'limit' => 32])
            ->addColumn('email_verified_at', 'datetime', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['account_number'], ['unique' => true])
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['status'])
            ->create();

        $this->table('admins', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('email', 'string', ['limit' => 255])
            ->addColumn('password_hash', 'string', ['limit' => 255])
            ->addColumn('status', 'string', ['default' => 'active', 'limit' => 32])
            ->addColumn('last_login_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['email'], ['unique' => true])
            ->addIndex(['status'])
            ->create();

        $this->table('players', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('user_id', 'biginteger', ['null' => true, 'signed' => false])
            ->addColumn('display_name', 'string', ['limit' => 255])
            ->addColumn('affiliation', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('status', 'string', ['default' => 'active', 'limit' => 32])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['user_id'], ['unique' => true])
            ->addIndex(['status'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'SET_NULL'])
            ->create();

        $this->table('events', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('owner_user_id', 'biginteger', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('start_at', 'datetime')
            ->addColumn('end_at', 'datetime')
            ->addColumn('timezone', 'string', ['default' => 'Asia/Tokyo', 'limit' => 64])
            ->addColumn('publication_status', 'string', ['default' => 'published', 'limit' => 32])
            ->addColumn('default_locale', 'string', ['default' => 'ja', 'limit' => 16])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['owner_user_id'])
            ->addIndex(['start_at', 'end_at'])
            ->addIndex(['publication_status'])
            ->addForeignKey('owner_user_id', 'users', 'id', ['delete' => 'RESTRICT'])
            ->create();

        $this->table('roles', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('code', 'string', ['limit' => 64])
            ->addColumn('name', 'string', ['limit' => 100])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['code'], ['unique' => true])
            ->create();

        $this->table('event_staff', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('event_id', 'biginteger', ['signed' => false])
            ->addColumn('user_id', 'biginteger', ['signed' => false])
            ->addColumn('membership_status', 'string', ['default' => 'active', 'limit' => 32])
            ->addColumn('joined_at', 'datetime')
            ->addColumn('released_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['event_id', 'user_id'], ['unique' => true])
            ->addIndex(['membership_status'])
            ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'RESTRICT'])
            ->create();

        $this->table('event_staff_roles', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('event_staff_id', 'biginteger', ['signed' => false])
            ->addColumn('role_id', 'biginteger', ['signed' => false])
            ->addColumn('created', 'datetime')
            ->addIndex(['event_staff_id', 'role_id'], ['unique' => true])
            ->addForeignKey('event_staff_id', 'event_staff', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('role_id', 'roles', 'id', ['delete' => 'RESTRICT'])
            ->create();
    }
}
