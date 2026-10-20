<?php

declare(strict_types=1);

$root = dirname(__DIR__);

// WHY: Reaction_03cd10 walks TraitNames with mb_substr. This PHP build has no mbstring.
// Trait names are ASCII, so substr is the same function. Unqualified calls fall back to global.
if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        unset($encoding);
        return $length === null ? substr($string, $start) : substr($string, $start, $length);
    }
}

require_once __DIR__ . '/stubs/bga.php';
require_once __DIR__ . '/harness/FakeGlobals.php';
require_once __DIR__ . '/harness/FakeNotify.php';
require_once __DIR__ . '/harness/FakeGamestate.php';
require_once __DIR__ . '/harness/Assert.php';
require_once __DIR__ . '/harness/TestCase.php';
require_once __DIR__ . '/harness/TestRunner.php';

spl_autoload_register(function (string $class) use ($root): void {
    // Prefer test FakeGame over real Game.php (which needs BGA Table).
    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Game') {
        require_once __DIR__ . '/harness/FakeGame.php';
        return;
    }

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\TestTheah') {
        require_once __DIR__ . '/harness/TestTheah.php';
        return;
    }

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\TestWorld') {
        require_once __DIR__ . '/harness/TestWorld.php';
        return;
    }

    // WHY: tests/regression/framework suites run the real StatesTrait on a FakeGame subclass.
    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\StatesTraitGame') {
        require_once __DIR__ . '/harness/StatesTraitGame.php';
        return;
    }

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\GenericScheme') {
        require_once __DIR__ . '/harness/GenericScheme.php';
        return;
    }

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\GenericCharacter') {
        require_once __DIR__ . '/harness/GenericCharacter.php';
        return;
    }

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\GenericLeader') {
        require_once __DIR__ . '/harness/GenericLeader.php';
        return;
    }

    // WHY: StatesTrait suites add more Generic* fixtures; any other Tests\Harness class
    // resolves to its same-named file in tests/harness without a new entry here.
    $harnessPrefix = 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\';
    if (str_starts_with($class, $harnessPrefix)) {
        $harnessFile = __DIR__ . '/harness/' . substr($class, strlen($harnessPrefix)) . '.php';
        if (is_file($harnessFile)) {
            require_once $harnessFile;
        }
        return;
    }

    $prefix = 'Bga\\Games\\SeventhSeaCityOfFiveSails\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = $root . '/modules/php/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
