<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\AccountActionToken;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\ORM\Table;

/**
 * AccountActionTokens Model
 *
 * @extends \Cake\ORM\Table<array{}, \App\Model\Entity\AccountActionToken>
 */
class AccountActionTokensTable extends Table
{
    /**
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('account_action_tokens');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }

    /**
     * Issues a new single-use token for the given user/purpose.
     *
     * Only the SHA-256 hash is persisted; the raw token is returned so the
     * caller (a Service\Auth\* class) can email it and then discard it.
     *
     * @return array{token: string, entity: \App\Model\Entity\AccountActionToken}
     */
    public function issue(int $userId, string $purpose): array
    {
        $rawToken = bin2hex(random_bytes(32));
        $expiryMinutes = (int)Configure::read('AccountToken.expiryMinutes');

        /** @var \App\Model\Entity\AccountActionToken $entity */
        $entity = $this->newEntity([
            'user_id' => $userId,
            'purpose' => $purpose,
            'token_hash' => hash('sha256', $rawToken),
            'expires_at' => DateTime::now()->addMinutes($expiryMinutes),
        ], ['accessibleFields' => ['*' => true]]);

        $this->saveOrFail($entity);

        return ['token' => $rawToken, 'entity' => $entity];
    }

    /**
     * Invalidates every currently-usable (not yet used/invalidated/expired)
     * token for a user/purpose. Called before issuing a new one so a resend
     * can never leave more than one valid token alive
     * (SCR-OPR-211: "再送時は、それ以前に発行した未使用URLを無効化する").
     */
    public function invalidateActive(int $userId, string $purpose): int
    {
        return $this->updateAll(
            ['invalidated_at' => DateTime::now()],
            [
                'user_id' => $userId,
                'purpose' => $purpose,
                'used_at IS' => null,
                'invalidated_at IS' => null,
            ],
        );
    }

    /**
     * Looks up a token by its raw (unhashed) value and purpose, returning it
     * only if it is still usable (not used, not invalidated, not expired).
     */
    public function findValidByRawToken(string $rawToken, string $purpose): ?AccountActionToken
    {
        /** @var \App\Model\Entity\AccountActionToken|null */
        return $this->find()
            ->where([
                'token_hash' => hash('sha256', $rawToken),
                'purpose' => $purpose,
                'used_at IS' => null,
                'invalidated_at IS' => null,
                'expires_at >' => DateTime::now(),
            ])
            ->first();
    }

    /**
     * @param \App\Model\Entity\AccountActionToken $token The token entity to mark used.
     * @return bool
     */
    public function markUsed(AccountActionToken $token): bool
    {
        $token->used_at = DateTime::now();

        return (bool)$this->save($token);
    }
}
