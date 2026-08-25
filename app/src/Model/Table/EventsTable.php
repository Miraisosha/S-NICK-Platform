<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Events Model (SCR-OPR-2401〜2404).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Event>
 */
class EventsTable extends Table
{
    use DateTimeAfterValidationTrait;

    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('events');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Owners', [
            'className' => 'Users',
            'foreignKey' => 'owner_user_id',
        ]);
        $this->hasMany('EventStaff', [
            'foreignKey' => 'event_id',
        ]);
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('name')
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('name_en')
            ->maxLength('name_en', 255)
            ->allowEmptyString('name_en');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 64)
            ->add('slug', 'format', [
                'rule' => ['custom', '/^[A-Za-z0-9-]+$/'],
                'message' => __('大会スラッグは半角英数字とハイフンのみ使用できます。'),
            ])
            ->allowEmptyString('slug');

        $validator
            ->scalar('organizer')
            ->maxLength('organizer', 255)
            ->allowEmptyString('organizer');

        $validator
            ->scalar('subtitle')
            ->maxLength('subtitle', 255)
            ->allowEmptyString('subtitle');

        $validator
            ->dateTime('start_at')
            ->requirePresence('start_at', 'create')
            ->notEmptyDateTime('start_at');

        $validator
            ->dateTime('end_at')
            ->requirePresence('end_at', 'create')
            ->notEmptyDateTime('end_at')
            ->add('end_at', 'afterStart', [
                'rule' => fn($value, $context) => self::isAfter($value, $context, 'start_at'),
                'message' => __('終了日時は開始日時より後にしてください。'),
            ]);

        $validator
            ->dateTime('registration_start_at')
            ->allowEmptyDateTime('registration_start_at');

        $validator
            ->dateTime('registration_end_at')
            ->allowEmptyDateTime('registration_end_at')
            ->add('registration_end_at', 'afterRegistrationStart', [
                'rule' => fn($value, $context) => self::isAfter($value, $context, 'registration_start_at'),
                'message' => __('申込締切日時は申込開始日時より後にしてください。'),
            ]);

        $validator
            ->email('contact_email')
            ->allowEmptyString('contact_email');

        $validator
            ->scalar('contact_info')
            ->allowEmptyString('contact_info');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('notes')
            ->allowEmptyString('notes');

        return $validator;
    }

    /**
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('owner_user_id', 'Owners'), 'ownerExists');
        $rules->add($rules->isUnique(['slug'], __('この大会スラッグは既に使用されています。')), 'slugUnique', [
            'allowNullableNulls' => true,
        ]);

        return $rules;
    }

    /**
     * Rows that are not soft-deleted.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Event> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Event>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('deleted_at') . ' IS' => null]);
    }

    /**
     * Events the given user either owns or has been given the
     * `event_manager` role for as staff (docs/specifications/020_UserRoles.md
     * "ロールと担当範囲の両方を満たす必要があり" - this phase-1 slice checks
     * role only, not the finer-grained scope, per the implementation plan's
     * judgment call #4: event/category CRUD is owner-or-event_manager only).
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Event> $query The query.
     * @param array<string, mixed> $options Must include `userId`.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Event>
     */
    public function findForUser(SelectQuery $query, array $options): SelectQuery
    {
        $userId = $options['userId'];

        /** @var \App\Model\Table\EventStaffTable $eventStaffTable */
        $eventStaffTable = $this->getAssociation('EventStaff')->getTarget();
        $managedEventIds = $eventStaffTable->find()
            ->select(['EventStaff.event_id'])
            ->innerJoinWith('EventStaffRoles.Roles', function ($q) {
                return $q->where(['Roles.code' => 'event_manager']);
            })
            ->where(['EventStaff.user_id' => $userId, 'EventStaff.membership_status' => 'active']);

        return $query->where([
            'OR' => [
                $this->aliasField('owner_user_id') => $userId,
                $this->aliasField('id') . ' IN' => $managedEventIds,
            ],
        ]);
    }

    /**
     * Whether the given user may create/edit this event: its owner, or an
     * active staff member with the `event_manager` role.
     *
     * @param int $eventId Event id.
     * @param int $userId User id.
     * @return bool
     */
    public function userCanManage(int $eventId, int $userId): bool
    {
        return $this->find()
            ->where(['id' => $eventId])
            ->find('forUser', userId: $userId)
            ->count() > 0;
    }
}
