<?php
declare(strict_types=1);

namespace App\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\I18n\DateTime;

/**
 * Housekeeping for the SCR-OPR-211 registration flow:
 *
 * - Soft-deletes accounts that never completed email verification within
 *   24 hours (the row itself is kept, per spec, as the minimal record
 *   needed to prevent silent re-registration abuse of the same address).
 * - Purges `account_action_attempts` rows older than the 30-day retention
 *   window.
 * - Removes long-settled `account_action_tokens` rows (expired, used, or
 *   invalidated) as routine table-growth hygiene; not mandated by the spec.
 *
 * Not scheduled by this change — see docs/08_deployment.md for a future
 * production cron/task-scheduler increment. Run manually via:
 * `bin/cake cleanup_unverified_accounts`.
 */
class CleanupUnverifiedAccountsCommand extends Command
{
    /**
     * @return string
     */
    public static function defaultName(): string
    {
        return 'cleanup_unverified_accounts';
    }

    /**
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io.
     * @return int
     */
    public function execute(Arguments $args, ConsoleIo $io): int
    {
        /** @var \App\Model\Table\UsersTable $usersTable */
        $usersTable = $this->fetchTable('Users');
        /** @var \App\Model\Table\AccountActionAttemptsTable $attemptsTable */
        $attemptsTable = $this->fetchTable('AccountActionAttempts');
        /** @var \App\Model\Table\AccountActionTokensTable $tokensTable */
        $tokensTable = $this->fetchTable('AccountActionTokens');

        $now = DateTime::now();

        $unverifiedCutoff = $now->subHours(24);
        $softDeleted = $usersTable->updateAll(
            ['deleted_at' => $now],
            [
                'email_verified_at IS' => null,
                'deleted_at IS' => null,
                'created <=' => $unverifiedCutoff,
            ],
        );
        $io->out(sprintf('未確認アカウントを論理削除しました: %d件', $softDeleted));

        $attemptsCutoff = $now->subDays(30);
        $deletedAttempts = $attemptsTable->deleteOlderThan($attemptsCutoff);
        $io->out(sprintf('登録試行履歴を削除しました（30日超過分）: %d件', $deletedAttempts));

        $deletedTokens = $tokensTable->deleteAll([
            'OR' => [
                'expires_at <' => $now,
                'used_at IS NOT' => null,
                'invalidated_at IS NOT' => null,
            ],
        ]);
        $io->out(sprintf('期限切れ・使用済み・無効化済みトークンを削除しました: %d件', $deletedTokens));

        return static::CODE_SUCCESS;
    }
}
