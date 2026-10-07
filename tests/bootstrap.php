<?php

declare(strict_types=1);

$root = dirname(__DIR__);

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

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\GenericCharacter') {
        require_once __DIR__ . '/harness/GenericCharacter.php';
        return;
    }

    if ($class === 'Bga\\Games\\SeventhSeaCityOfFiveSails\\Tests\\Harness\\GenericLeader') {
        require_once __DIR__ . '/harness/GenericLeader.php';
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
