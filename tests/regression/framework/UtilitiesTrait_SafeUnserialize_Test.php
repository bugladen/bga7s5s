<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\SafeUnserializeProbeTarget;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\UtilitiesTraitHost;

/**
 * UtilitiesTrait::safeUnserialize — normal path plus repair of corrupted bug-report dumps.
 *
 * WHY these repairs exist: when serialized PHP with protected properties is copied from logs,
 * null bytes around `*` are stripped and string length prefixes can drift. gameinfos sets
 * exception_on_warning so unserialize warnings become ErrorExceptions; the try/catch is required.
 */
class UtilitiesTrait_SafeUnserialize_Test extends TestCase
{
    public function name(): string
    {
        return 'UtilitiesTrait: safeUnserialize';
    }

    private function host(): UtilitiesTraitHost
    {
        return new UtilitiesTraitHost();
    }

    public function tests(): array
    {
        return [
            'valid serialized payload unserializes on the first try' => function () {
                $host = $this->host();
                $payload = serialize(['a' => 1, 'b' => 'ok']);
                Assert::same(['a' => 1, 'b' => 'ok'], $host->safeUnserialize($payload), 'array');
            },

            'valid Character object round-trips' => function () {
                $host = $this->host();
                $char = new GenericCharacter('Snap');
                $char->setId(77);
                $char->ControllerId = 1;
                $restored = $host->safeUnserialize(serialize($char));
                Assert::instanceOf(GenericCharacter::class, $restored, 'class');
                Assert::same(77, $restored->Id, 'id');
                Assert::same('Snap', $restored->Name, 'name');
            },

            'mismatched string length prefix is repaired' => function () {
                $host = $this->host();
                // Declared length 9 but content is "hello" (5).
                $broken = 'a:1:{s:9:"hello";s:5:"world";}';
                Assert::same(['hello' => 'world'], $host->safeUnserialize($broken), 'length fixed');
            },

            'stripped protected-property null bytes are reinserted' => function () {
                $host = $this->host();
                $obj = new SafeUnserializeProbeTarget();
                $obj->name = 'ok';
                $ref = new \ReflectionProperty($obj, 'secret');
                $ref->setAccessible(true);
                $ref->setValue($obj, 42);

                $broken = str_replace("\0*\0", '*', serialize($obj));
                Assert::true(str_contains($broken, 's:9:"*secret"'), 'corruption keeps declared length');

                $restored = $host->safeUnserialize($broken);
                Assert::instanceOf(SafeUnserializeProbeTarget::class, $restored, 'class');
                $out = new \ReflectionProperty($restored, 'secret');
                $out->setAccessible(true);
                // WHY: default on the class is 0 — must not pass by keeping the default.
                Assert::same(42, $out->getValue($restored), 'protected value restored');
                Assert::same('ok', $restored->name, 'public value');
            },

            'unrepairable garbage throws with diagnostic context' => function () {
                $host = $this->host();
                Assert::throws(
                    fn () => $host->safeUnserialize('not-serialized-at-all'),
                    'Failed to unserialize data',
                    'throws'
                );
            },
        ];
    }
}
