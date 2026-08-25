<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AccountActionAttempt Entity
 *
 * Audit/rate-limit trail for registration and password-reset requests
 * (SCR-OPR-211: "登録試行履歴は30日間保持し...").
 *
 * @property int $id
 * @property string $normalized_email
 * @property int|null $user_id
 * @property string $purpose
 * @property string $action
 * @property string $outcome
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property \Cake\I18n\DateTime $created
 */
class AccountActionAttempt extends Entity
{
    protected array $_accessible = [
        '*' => false,
    ];
}
