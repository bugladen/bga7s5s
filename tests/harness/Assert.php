<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

class Assert
{
    public static function true(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new AssertionFailedException($message);
        }
    }

    public static function false(bool $condition, string $message): void
    {
        self::true(!$condition, $message);
    }

    public static function same(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected !== $actual) {
            throw new AssertionFailedException(
                $message . sprintf(' (expected %s, got %s)', self::export($expected), self::export($actual))
            );
        }
    }

    public static function equals(mixed $expected, mixed $actual, string $message): void
    {
        if ($expected != $actual) {
            throw new AssertionFailedException(
                $message . sprintf(' (expected %s, got %s)', self::export($expected), self::export($actual))
            );
        }
    }

    public static function instanceOf(string $class, mixed $actual, string $message): void
    {
        if (!($actual instanceof $class)) {
            throw new AssertionFailedException(
                $message . sprintf(' (expected instanceof %s, got %s)', $class, self::export($actual))
            );
        }
    }

    public static function count(int $expected, iterable $actual, string $message): void
    {
        $count = is_countable($actual) ? count($actual) : iterator_count($actual);
        self::same($expected, $count, $message);
    }

    public static function contains(string $needle, string $haystack, string $message): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionFailedException($message . " (missing '$needle')");
        }
    }

    public static function notContains(string $needle, string $haystack, string $message): void
    {
        if (str_contains($haystack, $needle)) {
            throw new AssertionFailedException($message . " (unexpected '$needle')");
        }
    }

    private static function export(mixed $value): string
    {
        if (is_object($value)) {
            return $value::class;
        }
        return var_export($value, true);
    }
}

class AssertionFailedException extends \RuntimeException
{
}
