<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Event Entity (SCR-OPR-2401〜2404). Any email-verified user can create an
 * event and becomes its owner (docs/specifications/020_UserRoles.md).
 *
 * @property int $id
 * @property int $owner_user_id
 * @property string $name
 * @property string|null $name_en
 * @property string|null $slug
 * @property string|null $subtitle
 * @property \Cake\I18n\DateTime $start_at
 * @property \Cake\I18n\DateTime $end_at
 * @property string $timezone
 * @property \Cake\I18n\DateTime|null $registration_start_at
 * @property \Cake\I18n\DateTime|null $registration_end_at
 * @property string $publication_status
 * @property string $default_locale
 * @property string|null $contact_email
 * @property string|null $organizer
 * @property string|null $logo
 * @property string|null $contact_info
 * @property string|null $description
 * @property string|null $notes
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Event extends Entity
{
    public const PUBLICATION_STATUS_PUBLISHED = 'published';

    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'id' => false,
        'owner_user_id' => false,
        'name' => true,
        'name_en' => true,
        'slug' => true,
        'subtitle' => true,
        'start_at' => true,
        'end_at' => true,
        'timezone' => true,
        'registration_start_at' => true,
        'registration_end_at' => true,
        'publication_status' => false,
        'default_locale' => true,
        'contact_email' => true,
        'organizer' => true,
        'logo' => false,
        'contact_info' => true,
        'description' => true,
        'notes' => true,
        'deleted_at' => false,
    ];
}
