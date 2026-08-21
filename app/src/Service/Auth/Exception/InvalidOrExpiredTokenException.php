<?php
declare(strict_types=1);

namespace App\Service\Auth\Exception;

use RuntimeException;

/**
 * Thrown when an email-verification or password-reset token is missing,
 * expired, already used, or invalidated by a later resend.
 *
 * SCR-OPR-212/213 require that the reason not be over-disclosed to the
 * caller ("期限切れ・使用済み・無効なURLの場合は理由を必要以上に公開せず"),
 * so callers should render a single generic message regardless of cause.
 */
class InvalidOrExpiredTokenException extends RuntimeException
{
}
