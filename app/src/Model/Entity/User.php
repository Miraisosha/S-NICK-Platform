<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\I18n\DateTime;
use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string $account_number
 * @property string $email
 * @property string $password_hash
 * @property string $status
 * @property \Cake\I18n\DateTime|null $email_verified_at
 * @property \Cake\I18n\DateTime|null $terms_agreed_at
 * @property string|null $terms_version
 * @property int $failed_login_count
 * @property \Cake\I18n\DateTime|null $locked_until
 * @property \Cake\I18n\DateTime|null $sessions_invalidated_at
 * @property \Cake\I18n\DateTime|null $deleted_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class User extends Entity
{
    /**
     * Plain passwords are never assigned to this entity; only the
     * already-hashed value is ever set, and only by the auth services
     * under `App\Service\Auth`.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'account_number' => false,
        'email' => true,
        'password_hash' => false,
        'status' => false,
        'email_verified_at' => false,
        'terms_agreed_at' => false,
        'terms_version' => false,
        'failed_login_count' => false,
        'locked_until' => false,
        'sessions_invalidated_at' => false,
        'deleted_at' => false,
    ];

    /**
     * Never expose the password hash (debug output, JSON serialization, etc.).
     *
     * @var list<string>
     */
    protected array $_hidden = [
        'password_hash',
    ];

    /**
     * @return bool
     */
    public function isLocked(): bool
    {
        return $this->locked_until instanceof DateTime && $this->locked_until->isFuture();
    }
}
