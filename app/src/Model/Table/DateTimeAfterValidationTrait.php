<?php
declare(strict_types=1);

namespace App\Model\Table;

/**
 * Shared custom validation rule for "this datetime field must be later than
 * that one" checks (e.g. end_at after start_at, registration_end_at after
 * registration_start_at).
 *
 * Deliberately NOT CakePHP's built-in `greaterThanField()`: reading
 * `Validation::comparison()`'s source (after it rejected even
 * correctly-ordered dates during EventsTable manual testing) showed it only
 * actually compares when both sides are numeric, or the operator is an
 * equality check - for `>`/`<` it silently returns false for any
 * non-numeric value, which includes every ISO datetime string.
 */
trait DateTimeAfterValidationTrait
{
    /**
     * @param mixed $value The field being validated (already datetime-format-checked by `dateTime()`).
     * @param array<string, mixed> $context Validation context, including sibling field data.
     * @param string $compareField The field `$value` must be later than.
     * @return bool
     */
    private static function isAfter(mixed $value, array $context, string $compareField): bool
    {
        $compareValue = $context['data'][$compareField] ?? null;
        if (!is_string($compareValue) || $compareValue === '' || !is_string($value)) {
            // Nothing to compare against (e.g. an optional sibling field
            // wasn't supplied) - not this rule's job to flag as missing.
            return true;
        }

        $valueTimestamp = strtotime($value);
        $compareTimestamp = strtotime($compareValue);

        if ($valueTimestamp === false || $compareTimestamp === false) {
            return true;
        }

        return $valueTimestamp > $compareTimestamp;
    }
}
