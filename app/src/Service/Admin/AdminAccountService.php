<?php
declare(strict_types=1);

namespace App\Service\Admin;

use App\Model\Entity\Admin;
use App\Model\Table\AdminsTable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;

/**
 * Creates admin accounts (docs/specifications/500_Admin.md §501). There is
 * no self-registration or admin-managed "create another admin" screen yet
 * (SCR-ADM-550 is out of scope for now - see the implementation plan's
 * judgment call) - this is used only by `bin/cake create_admin`.
 */
class AdminAccountService
{
    /**
     * @param \App\Model\Table\AdminsTable $adminsTable Admins table.
     */
    public function __construct(
        private readonly AdminsTable $adminsTable,
    ) {
    }

    /**
     * @param string $email Login email address.
     * @param string $name Display name.
     * @param string $password Plain-text password to hash and store.
     * @param string $role `Admin::ROLE_ADMIN` or `Admin::ROLE_SUPER_ADMIN`.
     * @return \App\Model\Entity\Admin
     */
    public function create(string $email, string $name, string $password, string $role): Admin
    {
        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        /** @var \App\Model\Entity\Admin $admin */
        $admin = $this->adminsTable->newEntity([
            'admin_code' => bin2hex(random_bytes(8)),
            'email' => mb_strtolower(trim($email)),
            'name' => $name,
            'password_hash' => $hasher->hash($password),
            'role' => $role,
            'status' => 'active',
        ], ['accessibleFields' => ['*' => true]]);

        $this->adminsTable->saveOrFail($admin);

        // Replace the temporary unique placeholder with a stable,
        // human-referenceable admin number, mirroring
        // RegistrationService::createUser()'s `account_number` pattern.
        $admin->set('admin_code', sprintf('A%010d', $admin->id), ['guard' => false]);
        $this->adminsTable->saveOrFail($admin);

        return $admin;
    }
}
