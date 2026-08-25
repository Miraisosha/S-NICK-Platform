<?php
declare(strict_types=1);

namespace App\Service\Auth\Exception;

use Cake\I18n\DateTime;
use RuntimeException;

/**
 * Thrown when a verification/reset email resend is rejected by the 60-second
 * cooldown or the 5-per-24h cap (SCR-OPR-211/213). Carries the timestamp the
 * caller may retry at so the dedicated resend screen can display it
 * ("次に送信可能となる時刻を表示する").
 */
class ResendThrottledException extends RuntimeException
{
    /**
     * @param \Cake\I18n\DateTime $retryAfter The time after which a resend may be retried.
     * @param string $message Exception message.
     */
    public function __construct(private readonly DateTime $retryAfter, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'Resend throttled.');
    }

    /**
     * @return \Cake\I18n\DateTime
     */
    public function getRetryAfter(): DateTime
    {
        return $this->retryAfter;
    }
}
