<?php
declare(strict_types=1);

namespace StorvixLabs\AdminActionLog\Model;

class ValueNormalizer
{
    private const MAX_LENGTH = 10000;
    public const MASK = '******';
    private const SENSITIVE_PATTERN =
        '/(password|passwd|secret|token|api_?key|private_?key|access_?key|hash|salt|credential)/i';

    /**
     * @return string|null String form, or null if the value can't be compared (objects, nested arrays)
     */
    public function normalize(mixed $value): ?string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        if (is_array($value)) {
            foreach ($value as $item) {
                if (!is_scalar($item) && $item !== null) {
                    return null;
                }
            }
            $items = array_map('strval', array_values($value));
            sort($items);

            return implode(', ', $items);
        }

        return null;
    }

    public function equals(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float)$a - (float)$b) < 0.00001;
        }

        return false;
    }

    public function isSensitive(string $field): bool
    {
        return (bool)preg_match(self::SENSITIVE_PATTERN, $field);
    }

    public function truncate(string $value): string
    {
        return mb_strlen($value) > self::MAX_LENGTH
            ? mb_substr($value, 0, self::MAX_LENGTH) . ' [truncated]'
            : $value;
    }
}
