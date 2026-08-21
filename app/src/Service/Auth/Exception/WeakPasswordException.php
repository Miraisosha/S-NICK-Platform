<?php
declare(strict_types=1);

namespace App\Service\Auth\Exception;

use RuntimeException;

/**
 * Thrown when a submitted password fails the account password policy
 * (SCR-OPR-211: length, allowed characters, derived-from-email, sequential/repeated).
 */
class WeakPasswordException extends RuntimeException
{
    public const TOO_SHORT = 'TOO_SHORT';
    public const TOO_LONG = 'TOO_LONG';
    public const INVALID_CHARSET = 'INVALID_CHARSET';
    public const DERIVED_FROM_EMAIL = 'DERIVED_FROM_EMAIL';
    public const SEQUENTIAL_OR_REPEATED = 'SEQUENTIAL_OR_REPEATED';
    public const CONFIRMATION_MISMATCH = 'CONFIRMATION_MISMATCH';

    private string $reasonCode;

    /**
     * @param string $reasonCode One of the class's reason-code constants.
     * @param string $message Exception message.
     */
    public function __construct(string $reasonCode, string $message = '')
    {
        parent::__construct($message !== '' ? $message : $reasonCode);
        $this->reasonCode = $reasonCode;
    }

    /**
     * @return string
     */
    public function getReasonCode(): string
    {
        return $this->reasonCode;
    }
}
