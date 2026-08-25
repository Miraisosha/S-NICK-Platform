<?php
declare(strict_types=1);

namespace App\Service\Auth\Exception;

use RuntimeException;

/**
 * Thrown when a login attempt targets an account currently locked out by
 * SCR-OPR-214 (5 consecutive failures -> 30 minute lock). Callers must show
 * the same generic invalid-credentials message used for a wrong password,
 * to avoid revealing account existence/lock state.
 */
class AccountLockedException extends RuntimeException
{
}
