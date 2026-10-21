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

    /**
     * Run $fn and require it to throw an exception whose message contains $needle.
     *
     * WHY: FrameworkActionsTrait act* handlers signal every illegal move with
     * UserException / BgaUserException (two unrelated classes), so the helper accepts any
     * Throwable and only pins the message. Returns the exception for extra assertions.
     */
    public static function throws(callable $fn, string $needle, string $message): \Throwable
    {
        try {
            $fn();
        } catch (AssertionFailedException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), $needle)) {
                throw new AssertionFailedException(
                    $message . sprintf(" (expected message containing '%s', got '%s' [%s])", $needle, $e->getMessage(), $e::class)
                );
            }
            return $e;
        }

        throw new AssertionFailedException($message . " (expected an exception containing '$needle', none thrown)");
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
