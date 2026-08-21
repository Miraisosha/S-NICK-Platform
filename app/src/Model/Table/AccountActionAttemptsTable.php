<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AccountActionAttempt;
use Cake\I18n\DateTime;
use Cake\ORM\Table;

/**
 * AccountActionAttempts Model
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\AccountActionAttempt>
 */
class AccountActionAttemptsTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('account_action_attempts');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
        ]);
    }

    /**
     * @param string $normalizedEmail Normalized email address.
     * @param int|null $userId The user id, if a matching account exists.
     * @param string $purpose One of AccountActionToken::PURPOSE_*.
     * @param string $action The action that triggered this attempt (register, resend, reactivate, request).
     * @param string $outcome The result (sent, throttled_cooldown, throttled_daily_limit, already_verified, not_found).
     * @param string|null $ip The client IP address.
     * @param string|null $userAgent The client user agent string.
     * @return \App\Model\Entity\AccountActionAttempt
     */
    public function record(
        string $normalizedEmail,
        ?int $userId,
        string $purpose,
        string $action,
        string $outcome,
        ?string $ip = null,
        ?string $userAgent = null,
    ): AccountActionAttempt {
        /** @var \App\Model\Entity\AccountActionAttempt $entity */
        $entity = $this->newEntity([
            'normalized_email' => $normalizedEmail,
            'user_id' => $userId,
            'purpose' => $purpose,
            'action' => $action,
            'outcome' => $outcome,
            'ip_address' => $ip,
            'user_agent' => $userAgent !== null ? mb_substr($userAgent, 0, 512) : null,
            'created' => DateTime::now(),
        ], ['accessibleFields' => ['*' => true]]);

        $this->saveOrFail($entity);

        return $entity;
    }

    /**
     * Most recent successfully-sent notification timestamp for the given
     * email/purpose, used for the 60-second resend cooldown.
     */
    public function lastSentAt(string $normalizedEmail, string $purpose): ?DateTime
    {
        $row = $this->find()
            ->select(['created'])
            ->where([
                'normalized_email' => $normalizedEmail,
                'purpose' => $purpose,
                'outcome' => 'sent',
            ])
            ->orderBy(['created' => 'DESC'])
            ->first();

        return $row?->created;
    }

    /**
     * Number of successfully-sent notifications since a given time, used for
     * the "5 per 24h" resend cap.
     */
    public function countSentSince(string $normalizedEmail, string $purpose, DateTime $since): int
    {
        return $this->find()
            ->where([
                'normalized_email' => $normalizedEmail,
                'purpose' => $purpose,
                'outcome' => 'sent',
                'created >=' => $since,
            ])
            ->count();
    }

    /**
     * Deletes attempt rows older than the given retention window
     * (SCR-OPR-211: 30-day retention). Used by the account cleanup command.
     */
    public function deleteOlderThan(DateTime $threshold): int
    {
        return $this->deleteAll(['created <' => $threshold]);
    }
}
