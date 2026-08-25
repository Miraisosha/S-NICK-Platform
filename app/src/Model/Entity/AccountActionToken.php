<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AccountActionToken Entity
 *
 * Single-use tokens backing email verification and password reset
 * (SCR-OPR-211/213). Only the SHA-256 hash of the token is ever stored;
 * the raw token exists only in memory and in the email sent to the user.
 *
 * @property int $id
 * @property int $user_id
 * @property string $purpose
 * @property string $token_hash
 * @property \Cake\I18n\DateTime $expires_at
 * @property \Cake\I18n\DateTime|null $used_at
 * @property \Cake\I18n\DateTime|null $invalidated_at
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 * @property \App\Model\Entity\User $user
 */
class AccountActionToken extends Entity
{
    public const PURPOSE_EMAIL_VERIFICATION = 'email_verification';
    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    protected array $_accessible = [
        '*' => false,
    ];
}
