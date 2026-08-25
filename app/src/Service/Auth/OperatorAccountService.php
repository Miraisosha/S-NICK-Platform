<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Model\Entity\User;
use App\Model\Table\UsersTable;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;

/**
 * Creates an already-verified operator account directly, skipping
 * RegistrationService's normal email-confirmation flow. Used only by
 * `bin/cake create_operator`, for local development and manual testing -
 * self-registration (SCR-OPR-211) remains the only user-facing way to
 * create an account.
 */
class OperatorAccountService
{
    /**
     * @param \App\Model\Table\UsersTable $usersTable Users table.
     * @param \App\Service\Auth\PasswordPolicy $passwordPolicy Password policy.
     */
    public function __construct(
        private readonly UsersTable $usersTable,
        private readonly PasswordPolicy $passwordPolicy,
    ) {
    }

    /**
     * @param string $email Login email address.
     * @param string $password Plain-text password to validate, hash and store.
     * @return \App\Model\Entity\User
     * @throws \App\Service\Auth\Exception\WeakPasswordException
     * @throws \Cake\ORM\Exception\PersistenceFailedException
     */
    public function create(string $email, string $password): User
    {
        $normalizedEmail = UsersTable::normalizeEmail($email);
        $this->passwordPolicy->assertAcceptable($password, $normalizedEmail);

        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        /** @var \App\Model\Entity\User $user */
        $user = $this->usersTable->newEntity([
            'account_number' => bin2hex(random_bytes(8)),
            'email' => $normalizedEmail,
            'password_hash' => $hasher->hash($password),
            'status' => 'active',
            'email_verified_at' => DateTime::now(),
            'terms_agreed_at' => DateTime::now(),
            'terms_version' => RegistrationService::CURRENT_TERMS_VERSION,
        ], ['accessibleFields' => ['*' => true]]);

        $this->usersTable->saveOrFail($user);

        // Replace the temporary unique placeholder with a stable,
        // human-referenceable account number, mirroring
        // RegistrationService::createUser()'s `account_number` pattern.
        $user->set('account_number', sprintf('U%010d', $user->id), ['guard' => false]);
        $this->usersTable->saveOrFail($user);

        return $user;
    }
}
