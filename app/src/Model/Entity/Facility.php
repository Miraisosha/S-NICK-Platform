<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Facility Entity (SCR-ADM-522). Managed only by platform admins;
 * operators select from these but cannot create/edit/delete them.
 *
 * @property int $id
 * @property string $name
 * @property string|null $address
 * @property string|null $prefecture
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string|null $map_url
 * @property string|null $website_url
 * @property string|null $contact_info
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\Court[] $courts
 */
class Facility extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        // See App\Model\Entity\Court's `id => false` for why: this entity
        // is also patched from raw request data (FacilitiesController).
        'id' => false,
        'name' => true,
        'address' => true,
        'prefecture' => true,
        'latitude' => true,
        'longitude' => true,
        'map_url' => true,
        'website_url' => true,
        'contact_info' => true,
        'notes' => true,
        'deleted_at' => false,
        'courts' => true,
    ];
}
