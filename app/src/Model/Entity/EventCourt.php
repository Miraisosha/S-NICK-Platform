<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EventCourt Entity: one court selected for use by one event
 * (SCR-OPR-2402/2404).
 *
 * @property int $id
 * @property int $event_id
 * @property int $court_id
 * @property \Cake\I18n\DateTime $created
 * @property \App\Model\Entity\Court $court
 */
class EventCourt extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'event_id' => false,
        'court_id' => true,
        'court' => true,
    ];
}
