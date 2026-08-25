<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\User;
use Authentication\PasswordHasher\DefaultPasswordHasher;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Users Model
 *
 * Common account shared by operators, staff, markers and players
 * (docs/specifications/020_UserRoles.md). Platform admins use the
 * separate `admins` table and are not represented here.
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\User>
 */
class UsersTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setDisplayField('email');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('AccountActionTokens', [
            'foreignKey' => 'user_id',
        ]);
        $this->hasMany('AccountActionAttempts', [
            'foreignKey' => 'user_id',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('email')
            ->maxLength('email', 255)
            ->requirePresence('email', 'create')
            ->notEmptyString('email')
            ->email('email');

        $validator
            ->scalar('password_hash')
            ->maxLength('password_hash', 255)
            ->requirePresence('password_hash', 'create')
            ->notEmptyString('password_hash');

        $validator
            ->scalar('account_number')
            ->maxLength('account_number', 32)
            ->requirePresence('account_number', 'create')
            ->notEmptyString('account_number');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['email']), 'emailUnique', [
            'message' => __('このメールアドレスは既に使用されています。'),
        ]);
        $rules->add($rules->isUnique(['account_number']), 'accountNumberUnique');

        return $rules;
    }

    /**
     * Normalizes an email address for storage and lookup:
     * trims surrounding whitespace and case-folds it, per
     * SCR-OPR-211 "メールアドレスは前後の空白や大文字小文字等を正規化して照合する".
     */
    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * Rows that are not soft-deleted and are in normal standing.
     * Does not require email verification (used e.g. to find a pending
     * registration to resend a verification email to).
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('deleted_at') . ' IS' => null,
            $this->aliasField('status') => 'active',
        ]);
    }

    /**
     * Rows eligible to authenticate in general: active, not soft-deleted,
     * and with a confirmed email address. Deliberately does NOT exclude
     * currently-locked accounts, since e.g. password-reset eligibility
     * (SCR-OPR-213) must still work for a locked-out user — resetting the
     * password is how they recover, and it also clears the lock.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User>
     */
    public function findAuthable(SelectQuery $query): SelectQuery
    {
        return $this->findActive($query)->where([
            $this->aliasField('email_verified_at') . ' IS NOT' => null,
        ]);
    }

    /**
     * `findAuthable` further restricted to accounts that are not currently
     * locked out (SCR-OPR-214). This is the finder wired into the
     * Authentication plugin's ORM resolver: excluding locked accounts at
     * the identifier level (rather than after the fact in the controller)
     * is what stops `AuthenticationMiddleware` from persisting a session
     * for a locked account even when the submitted password is correct.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\User>
     */
    public function findLoginable(SelectQuery $query): SelectQuery
    {
        return $this->findAuthable($query)->where([
            'OR' => [
                $this->aliasField('locked_until') . ' IS' => null,
                $this->aliasField('locked_until') . ' <=' => DateTime::now(),
            ],
        ]);
    }

    /**
     * Recomputes and persists `password_hash` if it was hashed with
     * different Argon2id parameters than the currently configured ones
     * (SCR-OPR-214: "設定変更後は...再ハッシュ要否を判定し...更新する").
     */
    public function rehashPasswordIfNeeded(User $user, string $plainPassword): void
    {
        $hasher = new DefaultPasswordHasher([
            'hashType' => PASSWORD_ARGON2ID,
            'hashOptions' => (array)Configure::read('PasswordHasher.argon2id'),
        ]);

        if (!$hasher->needsRehash($user->password_hash)) {
            return;
        }

        $user->password_hash = $hasher->hash($plainPassword);
        $this->saveOrFail($user);
    }
}
