<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
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
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn('facility_id', 'Facilities'), 'facilityExists');

        // SCR-ADM-522 delete-reference guard: only takes effect for an
        // entity-based save()/delete() of a single court. The one deletion
        // path that exists today (FacilitiesController::delete(), cascading
        // to every court under a facility) uses a bulk updateAll() and
        // checks this itself instead, since buildRules() never runs for
        // bulk updates - see that method's comment.
        $rules->addDelete(function ($court) {
            /** @var \App\Model\Table\EventCourtsTable $eventCourtsTable */
            $eventCourtsTable = TableRegistry::getTableLocator()->get('EventCourts');

            return $eventCourtsTable->find()->where(['court_id' => $court->id])->count() === 0;
        }, 'notReferencedByEvent', [
            'errorField' => 'id',
            'message' => __('このコートはイベントで使用されているため削除できません。'),
        ]);

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
