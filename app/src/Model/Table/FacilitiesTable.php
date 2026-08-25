<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Facilities Model (SCR-ADM-522).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Facility>
 */
class FacilitiesTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('facilities');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        // Deliberately NOT using saveStrategy => 'replace': it performs a
        // hard DELETE on any associated row not present in the incoming
        // list, which is wrong for a soft-deletable, later FK-referenced
        // (event_courts, milestone M5) table - and it can only match
        // existing rows by id if the parent was loaded with `contain(['Courts'])`
        // in the first place, silently falling back to delete-and-recreate-
        // everything otherwise. FacilitiesController syncs courts itself
        // (soft-delete removed rows, update matched rows, insert new ones).
        $this->hasMany('Courts', [
            'foreignKey' => 'facility_id',
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
            ->scalar('address')
            ->maxLength('address', 255)
            ->allowEmptyString('address');

        $validator
            ->scalar('prefecture')
            ->maxLength('prefecture', 64)
            ->allowEmptyString('prefecture');

        $validator
            ->decimal('latitude')
            ->allowEmptyString('latitude');

        $validator
            ->decimal('longitude')
            ->allowEmptyString('longitude');

        $validator
            ->scalar('map_url')
            ->maxLength('map_url', 512)
            ->allowEmptyString('map_url');

        $validator
            ->scalar('website_url')
            ->maxLength('website_url', 512)
            ->allowEmptyString('website_url');

        $validator
            ->scalar('contact_info')
            ->allowEmptyString('contact_info');

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
        return $rules;
    }

    /**
     * Rows that are not soft-deleted.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Facility> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Facility>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('deleted_at') . ' IS' => null]);
    }
}
