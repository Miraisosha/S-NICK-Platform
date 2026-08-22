<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

/**
 * SCR-OPR-2402/2404 開催場所・使用コート・利用時間
 * (docs/specifications/200_Operator.md): an event selects individual
 * courts from the admin-managed facility/court master (SCR-ADM-522), and
 * registers a start/end usage time per court per calendar day - "日によって
 * 使用するコートや利用時間が異なる設定を許可する".
 *
 * `event_court_usage_times` intentionally has no foreign key straight to
 * `event_courts`: the spec's per-day granularity means a court can have
 * zero, one, or several usage-time rows independent of how the court
 * selection itself is represented, so both reference `events`/`courts`
 * directly.
 */
final class CreateEventCourtsAndUsageTimes extends BaseMigration
{
    public function change(): void
    {
        $this->table('event_courts', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('event_id', 'biginteger', ['signed' => false])
            ->addColumn('court_id', 'biginteger', ['signed' => false])
            ->addColumn('created', 'datetime')
            ->addIndex(['event_id', 'court_id'], ['unique' => true])
            ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('court_id', 'courts', 'id', ['delete' => 'RESTRICT'])
            ->create();

        $this->table('event_court_usage_times', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('event_id', 'biginteger', ['signed' => false])
            ->addColumn('court_id', 'biginteger', ['signed' => false])
            ->addColumn('usage_date', 'date')
            ->addColumn('start_time', 'time')
            ->addColumn('end_time', 'time')
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['event_id', 'court_id', 'usage_date'])
            ->addForeignKey('event_id', 'events', 'id', ['delete' => 'CASCADE'])
            ->addForeignKey('court_id', 'courts', 'id', ['delete' => 'RESTRICT'])
            ->create();
    }
}
