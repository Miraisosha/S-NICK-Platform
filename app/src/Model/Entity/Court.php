<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Court Entity (SCR-ADM-522). An individual court within a facility.
 *
 * @property int $id
 * @property int $facility_id
 * @property string $name
 * @property string|null $equipment
 * @property string|null $available_hours_note
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Facility $facility
 */
class Court extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        // Deliberately guarded: FacilitiesController::syncCourts() patches
        // an existing court from raw request data (to let the client say
        // "update court #4"), and CakePHP does not exclude the primary key
        // from mass assignment by default - an unguarded `id` would let
        // that patch retarget the UPDATE at an arbitrary row via
        // Entity::save()'s primary-key-based WHERE clause.
        'id' => false,
        'facility_id' => true,
        'name' => true,
        'equipment' => true,
        'available_hours_note' => true,
        'notes' => true,
        'deleted_at' => false,
        'facility' => true,
    ];
}
