<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * EventCourtUsageTime Entity: one day's start/end usage time for one court
 * within one event (SCR-OPR-2404 "開催日ごと・使用コートごとに利用開始時刻と
 * 利用終了時刻を登録する").
 *
 * @property int $id
 * @property int $event_id
 * @property int $court_id
 * @property \Cake\I18n\Date $usage_date
 */
class EventCourtUsageTime extends Entity
{
    /**
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'event_id' => false,
        'court_id' => true,
        'usage_date' => true,
        'start_time' => true,
        'end_time' => true,
    ];
}
