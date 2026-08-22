<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * EventStaffRoles Model: the fixed role(s) assigned to one EventStaff row.
 *
 * @extends \Cake\ORM\Table<array{}, \Cake\ORM\Entity>
 */
class EventStaffRolesTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('event_staff_roles');
        $this->setPrimaryKey('id');

        // This table has only `created` (no `modified`) - restrict the
        // behavior explicitly rather than relying on it silently dropping
        // the unmatched `modified` column from the query.
        $this->addBehavior('Timestamp', [
            'events' => ['Model.beforeSave' => ['created' => 'new']],
        ]);

        $this->belongsTo('EventStaff');
        $this->belongsTo('Roles');
    }
}
