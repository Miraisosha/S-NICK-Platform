<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Courts Model (SCR-ADM-522).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Court>
 */
class CourtsTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('courts');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Facilities', [
            'foreignKey' => 'facility_id',
            'joinType' => 'INNER',
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
            ->scalar('equipment')
            ->allowEmptyString('equipment');

        $validator
            ->scalar('available_hours_note')
            ->allowEmptyString('available_hours_note');

        $validator
            ->scalar('notes')
            ->allowEmptyString('notes');

        return $validator;
    }

    /**
     * No delete-reference guard yet: `event_courts` (which would reference
     * a court) doesn't exist until the usage-time milestone. That milestone
     * adds the corresponding rule here - see the implementation plan.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('facility_id', 'Facilities'), 'facilityExists');

        return $rules;
    }

    /**
     * Rows that are not soft-deleted.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Court> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Court>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('deleted_at') . ' IS' => null]);
    }
}
