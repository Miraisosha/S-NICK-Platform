<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

/**
 * EventCourts Model: which courts (from the admin-managed facility/court
 * master) an event has selected for use (SCR-OPR-2402/2404).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\EventCourt>
 */
class EventCourtsTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('event_courts');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => ['Model.beforeSave' => ['created' => 'new']],
        ]);

        $this->belongsTo('Events');
        $this->belongsTo('Courts');
    }
}
