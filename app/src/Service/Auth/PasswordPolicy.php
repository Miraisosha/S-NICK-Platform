<?php
declare(strict_types=1);

namespace App\Service\Auth;

use App\Service\Auth\Exception\WeakPasswordException;
use Cake\Core\Configure;

/**
 * Password rules from SCR-OPR-211 "パスワード文字と保存方式":
 *
 * - length 6-64 (configurable via PasswordPolicy.minLength/maxLength)
 * - allowed characters: half-width upper/lower case letters, digits,
 *   and the symbols `!#$%&|_-` (no character-class mix is required)
 * - must not be trivially derivable from the account's email address
 * - must not be a simple sequential or single-repeated-character value
 *
 * A general breached/common-password blocklist is explicitly left
 * undecided by the spec (source, refresh cadence and matching method are
 * "実装時に選定する") and is intentionally NOT implemented here.
 *
 * @see docs/specifications/200_Operator.md
 */
class PasswordPolicy
{
    private const ALLOWED_CHARSET_PATTERN = '/^[A-Za-z0-9!#$%&|_\-]+$/';

    /**
     * @return int
     */
    public function minLength(): int
    {
        return (int)Configure::read('PasswordPolicy.minLength');
    }

    /**
     * @return int
     */
    public function maxLength(): int
    {
        return (int)Configure::read('PasswordPolicy.maxLength');
    }

    /**
     * @param string $password The password to check.
     * @return bool
     */
    public function isLengthValid(string $password): bool
    {
        $length = mb_strlen($password);

        return $length >= $this->minLength() && $length <= $this->maxLength();
    }

    /**
     * @param string $password The password to check.
     * @return bool
     */
    public function isCharsetValid(string $password): bool
    {
        return $password !== '' && preg_match(self::ALLOWED_CHARSET_PATTERN, $password) === 1;
    }

    /**
     * Rejects passwords equal to (or trivially built from) the account's
     * email address or its local-part, case-insensitively.
     */
    public function isDerivedFromEmail(string $password, string $email): bool
    {
        $normalizedPassword = mb_strtolower($password);
        $normalizedEmail = mb_strtolower($email);
        $localPart = mb_strtolower((string)strstr($email, '@', true));

        if ($normalizedPassword === '' || $localPart === '') {
            return $normalizedPassword !== '' && $normalizedPassword === $normalizedEmail;
        }

        return $normalizedPassword === $normalizedEmail
            || $normalizedPassword === $localPart
            || str_contains($localPart, $normalizedPassword)
            || str_contains($normalizedPassword, $localPart);
    }

    /**
     * Rejects an all-identical-character password, or one whose character
     * codes form a strictly ascending or descending run (e.g. "123456",
     * "abcdef", "654321").
     */
    public function isSequentialOrRepeated(string $password): bool
    {
        $chars = mb_str_split($password);
        if (count($chars) < 2) {
            return true;
        }

        if (count(array_unique($chars)) === 1) {
            return true;
        }

        $codes = array_map(static fn(string $char): int => mb_ord($char), $chars);

        $ascending = true;
        $descending = true;
        $codeCount = count($codes);
        for ($i = 1; $i < $codeCount; $i++) {
            if ($codes[$i] !== $codes[$i - 1] + 1) {
                $ascending = false;
            }
            if ($codes[$i] !== $codes[$i - 1] - 1) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }

    /**
     * @throws \App\Service\Auth\Exception\WeakPasswordException
     */
    public function assertAcceptable(string $password, string $email): void
    {
        if (mb_strlen($password) < $this->minLength()) {
            throw new WeakPasswordException(WeakPasswordException::TOO_SHORT);
        }

        if (mb_strlen($password) > $this->maxLength()) {
            throw new WeakPasswordException(WeakPasswordException::TOO_LONG);
        }

        if (!$this->isCharsetValid($password)) {
            throw new WeakPasswordException(WeakPasswordException::INVALID_CHARSET);
        }

        if ($this->isDerivedFromEmail($password, $email)) {
            throw new WeakPasswordException(WeakPasswordException::DERIVED_FROM_EMAIL);
        }

        if ($this->isSequentialOrRepeated($password)) {
            throw new WeakPasswordException(WeakPasswordException::SEQUENTIAL_OR_REPEATED);
        }

        // TODO(検討中): 広く使用されているパスワード・既知の漏えいパスワードの禁止リスト照合。
        // 仕様上、入手元・更新頻度・照合方式が未決定のため実装しない (SCR-OPR-211)。
    }
}
