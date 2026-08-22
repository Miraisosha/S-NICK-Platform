<?php
declare(strict_types=1);

use Migrations\BaseMigration;
use Migrations\Db\Adapter\MysqlAdapter;

/**
 * SCR-OPR-2405 カテゴリ管理 (docs/specifications/200_Operator.md): categories
 * belong to a single event and carry both entry-management fields
 * (capacity, fee, registration window) and match-format/scoring/timing
 * fields used to compute the scheduling slot for each match.
 *
 * `max_games` is stored as a plain positive integer, not an enum of
 * {1,3,5}, per the spec's "内部データではゲーム数を1、3、5だけの列挙値にせず、
 * 正の整数として保持できる構造とする" - the {1,3,5} choices are a
 * screen-level (frontend) restriction only.
 */
final class CreateCategories extends BaseMigration
{
    public function change(): void
    {
        $this->table('categories', ['signed' => false, 'limit' => MysqlAdapter::INT_BIG])
            ->addColumn('event_id', 'biginteger', ['signed' => false])
            ->addColumn('name', 'string', ['limit' => 255])
            ->addColumn('gender', 'string', ['limit' => 16])
            ->addColumn('age_min', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('age_max', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('level', 'string', ['limit' => 100, 'null' => true])
            ->addColumn('squash_association_registration', 'boolean')
            ->addColumn('eligibility', 'text', ['null' => true])
            ->addColumn('entry_fee', 'integer', ['signed' => false])
            ->addColumn('capacity', 'integer', ['signed' => false])
            ->addColumn('registration_start_at', 'datetime')
            ->addColumn('registration_end_at', 'datetime')
            ->addColumn('waitlist_allowed', 'boolean')
            ->addColumn('match_format', 'string', ['limit' => 32])
            ->addColumn('max_games', 'integer', ['signed' => false])
            ->addColumn('game_end_score', 'integer', ['signed' => false])
            ->addColumn('required_point_diff', 'integer', ['null' => true, 'signed' => false])
            ->addColumn('estimated_game_minutes', 'integer', ['signed' => false])
            ->addColumn('warmup_seconds', 'integer', ['default' => 120, 'signed' => false])
            ->addColumn('pre_match_interval_seconds', 'integer', ['default' => 60, 'signed' => false])
            ->addColumn('between_game_interval_seconds', 'integer', ['default' => 120, 'signed' => false])
            ->addColumn('min_rest_seconds', 'integer', ['signed' => false])
            ->addColumn('display_order', 'integer', ['signed' => false])
            ->addColumn('publication_status', 'string', ['default' => 'unpublished', 'limit' => 32])
            ->addColumn('notes', 'text', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addColumn('created', 'datetime')
            ->addColumn('modified', 'datetime')
            ->addIndex(['event_id', 'name'], ['unique' => true])
            ->addIndex(['event_id', 'display_order'])
            ->addForeignKey('event_id', 'events', 'id', ['delete' => 'RESTRICT'])
            ->create();
    }
}
