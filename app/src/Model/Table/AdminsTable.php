<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Admin;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Admins Model
 *
 * Platform administrators, authenticated separately from `users`
 * (docs/specifications/500_Admin.md §501).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Admin>
 */
class AdminsTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('admins');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
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
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('password_hash')
            ->maxLength('password_hash', 255)
            ->requirePresence('password_hash', 'create')
            ->notEmptyString('password_hash');

        $validator
            ->scalar('admin_code')
            ->maxLength('admin_code', 32)
            ->requirePresence('admin_code', 'create')
            ->notEmptyString('admin_code');

        $validator
            ->scalar('role')
            ->inList('role', [Admin::ROLE_ADMIN, Admin::ROLE_SUPER_ADMIN]);

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
        $rules->add($rules->isUnique(['admin_code']), 'adminCodeUnique');

        return $rules;
    }

    /**
     * Rows eligible to authenticate: not suspended. Admins have no soft-delete
     * or email-verification concept (docs/specifications/500_Admin.md §501
     * accounts are created directly by a super admin, never self-registered).
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Admin> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Admin>
     */
    public function findLoginable(SelectQuery $query): SelectQuery
    {
        return $query->where([
            $this->aliasField('status') => 'active',
        ]);
    }
}
