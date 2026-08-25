<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * EventCourtUsageTimes Model (SCR-OPR-2404).
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\EventCourtUsageTime>
 */
class EventCourtUsageTimesTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('event_court_usage_times');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Events');
        $this->belongsTo('Courts');
    }

    /**
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('court_id')
            ->notEmptyString('court_id');

        $validator
            ->date('usage_date')
            ->requirePresence('usage_date')
            ->notEmptyDate('usage_date');

        $validator
            ->time('start_time')
            ->requirePresence('start_time')
            ->notEmptyTime('start_time');

        $validator
            ->time('end_time')
            ->requirePresence('end_time')
            ->notEmptyTime('end_time')
            ->add('end_time', 'afterStartTime', [
                'rule' => function ($value, $context) {
                    $start = $context['data']['start_time'] ?? null;
                    if (!is_string($start) || !is_string($value)) {
                        return true;
                    }

                    return strtotime($value) > strtotime($start);
                },
                'message' => __('利用終了時刻は利用開始時刻より後にしてください。'),
            ]);

        return $validator;
    }
}
