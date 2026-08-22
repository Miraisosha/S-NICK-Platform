<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * EventStaff Model: links a user to an event with one or more fixed roles
 * via EventStaffRoles (docs/specifications/020_UserRoles.md). No direct API
 * yet - written internally by EventService when an event is created (the
 * creator becomes its `event_owner`).
 *
 * @extends \Cake\ORM\Table<array{}, \Cake\ORM\Entity>
 */
class EventStaffTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('event_staff');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Events');
        $this->belongsTo('Users');
        $this->hasMany('EventStaffRoles', [
            'foreignKey' => 'event_staff_id',
        ]);
    }
}
