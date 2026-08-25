<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Admin Entity
 *
 * Platform administrators (docs/specifications/500_Admin.md §501), stored
 * separately from `users` and never linked to an event as an owner, staff
 * member, marker or player.
 *
 * @property int $id
 * @property string $admin_code
 * @property string $name
 * @property string $email
 * @property string $password_hash
 * @property string $role
 * @property bool $mfa_enabled
 * @property string $status
 * @property \Cake\I18n\DateTime|null $last_login_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Admin extends Entity
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_SUPER_ADMIN = 'super_admin';

    /**
     * Plain passwords are never assigned to this entity; only the
     * already-hashed value is ever set, and only by AdminAccountService.
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'admin_code' => false,
        'name' => true,
        'email' => true,
        'password_hash' => false,
        'role' => false,
        'mfa_enabled' => false,
        'status' => false,
        'last_login_at' => false,
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
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
}
