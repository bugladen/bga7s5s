<?php

declare(strict_types=1);

/**
 * Run all PHP regression tests.
 *
 * Usage (from repo root):
 *   php tests/run.php
 *
 * Or a single suite file:
 *   php tests/run.php tests/regression/Card_01006_Test.php
 */

require_once __DIR__ . '/bootstrap.php';

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestRunner;

$runner = new TestRunner();
$files = [];

if ($argc > 1) {
    for ($i = 1; $i < $argc; $i++) {
        $files[] = $argv[$i];
    }
} else {
    $files = glob(__DIR__ . '/regression/*_Test.php') ?: [];
    sort($files);
}

if ($files === []) {
    fwrite(STDERR, "No test files found.\n");
    exit(1);
}

echo "Running " . count($files) . " regression suite(s)...\n\n";

foreach ($files as $file) {
    $absolute = str_contains($file, DIRECTORY_SEPARATOR) || str_starts_with($file, '/') || preg_match('#^[A-Za-z]:#', $file)
        ? $file
        : __DIR__ . '/' . $file;

    if (!is_file($absolute)) {
        fwrite(STDERR, "Missing test file: {$file}\n");
        exit(1);
    }

    $declaredBefore = get_declared_classes();
    require_once $absolute;
    $declaredAfter = get_declared_classes();
    $newClasses = array_diff($declaredAfter, $declaredBefore);

    $found = false;
    foreach ($newClasses as $class) {
        if (!is_subclass_of($class, TestCase::class)) {
            continue;
        }
        /** @var TestCase $suite */
        $suite = new $class();
        $runner->runSuite($suite);
        $found = true;
    }

    if (!$found) {
        fwrite(STDERR, "No TestCase subclass found in {$absolute}\n");
        exit(1);
    }
}

exit($runner->summary());
