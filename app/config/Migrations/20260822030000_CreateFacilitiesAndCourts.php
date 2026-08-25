<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

/**
 * SCR-ADM-522 施設・コート管理 (docs/specifications/500_Admin.md §522):
 * facility/court master data, managed only by platform admins. Operators
 * merely select from these when creating/editing an event (SCR-OPR-261).
 *
 * `equipment` and `available_hours_note` are free text rather than
 * structured columns - the spec explicitly marks their detailed scope as
 * "検討中" (undecided).
 */
final class CreateFacilitiesAndCourts extends BaseMigration
{
    public function change(): void
    {
        $this->table('facilities', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('address', 'string', ['limit' => 255, 'null' => true])
            ->addColumn('prefecture', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('latitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('longitude', 'decimal', ['precision' => 10, 'scale' => 7, 'null' => true])
            ->addColumn('map_url', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('website_url', 'string', ['limit' => 512, 'null' => true])
            ->addColumn('contact_info', 'text', ['null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['name'])
            ->create();

        $this->table('courts', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('facility_id', 'biginteger', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('equipment', 'text', ['null' => true])
            ->addColumn('available_hours_note', 'text', ['null' => true])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['facility_id'])
            ->addForeignKey('facility_id', 'facilities', 'id', ['delete' => 'RESTRICT'])
            ->create();
    }
}
