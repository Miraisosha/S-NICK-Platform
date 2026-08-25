<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Category;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Categories Model (SCR-OPR-2405).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\Category>
 */
class CategoriesTable extends Table
{
    use DateTimeAfterValidationTrait;

    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('categories');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Events');
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
            ->scalar('gender')
            ->requirePresence('gender', 'create')
            ->inList('gender', [Category::GENDER_MALE, Category::GENDER_FEMALE, Category::GENDER_NONE]);

        $validator
            ->integer('age_min')
            ->range('age_min', [0, 150])
            ->allowEmptyString('age_min');

        $validator
            ->integer('age_max')
            ->range('age_max', [0, 150])
            ->allowEmptyString('age_max')
            ->add('age_max', 'ageRange', [
                'rule' => function ($value, $context) {
                    $min = $context['data']['age_min'] ?? null;
                    if ($min === null || $min === '' || $value === null || $value === '') {
                        return true;
                    }

                    return (int)$value >= (int)$min;
                },
                'message' => __('年齢・至は年齢・自以上にしてください。'),
            ]);

        $validator
            ->scalar('level')
            ->maxLength('level', 100)
            ->allowEmptyString('level');

        $validator
            ->boolean('squash_association_registration')
            ->requirePresence('squash_association_registration', 'create');

        $validator
            ->scalar('eligibility')
            ->allowEmptyString('eligibility');

        $validator
            ->integer('entry_fee')
            ->greaterThanOrEqual('entry_fee', 0)
            ->requirePresence('entry_fee', 'create');

        $validator
            ->integer('capacity')
            ->greaterThanOrEqual('capacity', 1)
            ->requirePresence('capacity', 'create');

        $validator
            ->dateTime('registration_start_at')
            ->requirePresence('registration_start_at', 'create')
            ->notEmptyDateTime('registration_start_at');

        $validator
            ->dateTime('registration_end_at')
            ->requirePresence('registration_end_at', 'create')
            ->notEmptyDateTime('registration_end_at')
            ->add('registration_end_at', 'afterRegistrationStart', [
                'rule' => fn($value, $context) => self::isAfter($value, $context, 'registration_start_at'),
                'message' => __('申込締切日時は申込開始日時より後にしてください。'),
            ]);

        $validator
            ->boolean('waitlist_allowed')
            ->requirePresence('waitlist_allowed', 'create');

        $validator
            ->scalar('match_format')
            ->requirePresence('match_format', 'create')
            ->inList('match_format', [Category::MATCH_FORMAT_TOURNAMENT, Category::MATCH_FORMAT_ROUND_ROBIN]);

        $validator
            ->integer('max_games')
            ->greaterThanOrEqual('max_games', 1)
            ->requirePresence('max_games', 'create');

        $validator
            ->integer('game_end_score')
            ->range('game_end_score', [0, 100])
            ->requirePresence('game_end_score', 'create');

        $validator
            ->integer('required_point_diff')
            ->inList('required_point_diff', [1, 2])
            ->allowEmptyString('required_point_diff')
            ->add('required_point_diff', 'notApplicableWhenManual', [
                'rule' => function ($value, $context) {
                    $gameEndScore = $context['data']['game_end_score'] ?? null;
                    if ((int)$gameEndScore === 0) {
                        return $value === null || $value === '';
                    }

                    return true;
                },
                'message' => __('ゲーム終了点が0（手動終了）の場合、必要点差は指定できません。'),
            ]);

        $validator
            ->integer('estimated_game_minutes')
            ->greaterThanOrEqual('estimated_game_minutes', 1)
            ->requirePresence('estimated_game_minutes', 'create');

        $validator
            ->integer('warmup_seconds')
            ->greaterThanOrEqual('warmup_seconds', 0)
            ->allowEmptyString('warmup_seconds');

        $validator
            ->integer('pre_match_interval_seconds')
            ->greaterThanOrEqual('pre_match_interval_seconds', 0)
            ->allowEmptyString('pre_match_interval_seconds');

        $validator
            ->integer('between_game_interval_seconds')
            ->greaterThanOrEqual('between_game_interval_seconds', 0)
            ->allowEmptyString('between_game_interval_seconds');

        $validator
            ->integer('min_rest_seconds')
            ->greaterThanOrEqual('min_rest_seconds', 0)
            ->requirePresence('min_rest_seconds', 'create');

        $validator
            ->integer('display_order')
            ->greaterThanOrEqual('display_order', 0)
            ->allowEmptyString('display_order');

        $validator
            ->scalar('publication_status')
            ->inList('publication_status', [
                Category::PUBLICATION_STATUS_PUBLISHED,
                Category::PUBLICATION_STATUS_UNPUBLISHED,
            ]);

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
        $rules->add($rules->existsIn('event_id', 'Events'), 'eventExists');
        $rules->add($rules->isUnique(['event_id', 'name']), 'nameUniqueWithinEvent', [
            'message' => __('このカテゴリ名は既に使用されています。'),
        ]);

        return $rules;
    }

    /**
     * Rows that are not soft-deleted.
     *
     * @param \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Category> $query The query.
     * @return \Cake\ORM\Query\SelectQuery<\App\Model\Entity\Category>
     */
    public function findActive(SelectQuery $query): SelectQuery
    {
        return $query->where([$this->aliasField('deleted_at') . ' IS' => null]);
    }

    /**
     * Next display_order for a new category in the given event: max+1, or 0
     * if none exist yet (SCR-OPR-2405 "未指定時は一覧の最後を自動設定").
     *
     * @param int $eventId Event id.
     * @return int
     */
    public function nextDisplayOrder(int $eventId): int
    {
        $max = $this->find('active')
            ->where(['event_id' => $eventId])
            ->select(['maxOrder' => $this->find()->func()->max('display_order')])
            ->enableHydration(false)
            ->first();

        return $max === null || $max['maxOrder'] === null ? 0 : (int)$max['maxOrder'] + 1;
    }
}
