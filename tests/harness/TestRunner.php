<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

class TestRunner
{
    /** @var list<array{suite:string,test:string,status:string,message?:string}> */
    private array $results = [];

    public function runSuite(TestCase $suite): void
    {
        foreach ($suite->tests() as $label => $test) {
            if (is_int($label) && is_array($test) && count($test) === 2) {
                [$label, $test] = $test;
            }

            $suiteName = $suite->name();
            try {
                $suite->setUp();
                $test();
                $suite->tearDown();
                $this->results[] = [
                    'suite' => $suiteName,
                    'test' => (string)$label,
                    'status' => 'pass',
                ];
                echo "  PASS  {$suiteName} :: {$label}\n";
            } catch (AssertionFailedException $e) {
                $suite->tearDown();
                $this->results[] = [
                    'suite' => $suiteName,
                    'test' => (string)$label,
                    'status' => 'fail',
                    'message' => $e->getMessage(),
                ];
                echo "  FAIL  {$suiteName} :: {$label}\n";
                echo "        {$e->getMessage()}\n";
            } catch (\Throwable $e) {
                $suite->tearDown();
                $this->results[] = [
                    'suite' => $suiteName,
                    'test' => (string)$label,
                    'status' => 'error',
                    'message' => $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
                ];
                echo "  ERROR {$suiteName} :: {$label}\n";
                echo "        {$e->getMessage()} @ {$e->getFile()}:{$e->getLine()}\n";
            }
        }
    }

    public function summary(): int
    {
        $pass = 0;
        $fail = 0;
        $error = 0;
        foreach ($this->results as $result) {
            match ($result['status']) {
                'pass' => $pass++,
                'fail' => $fail++,
                default => $error++,
            };
        }

        echo "\n{$pass} passed, {$fail} failed, {$error} errors\n";
        return ($fail + $error) > 0 ? 1 : 0;
    }
}
