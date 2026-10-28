<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

/**
 * Named serializable fixture for safeUnserialize protected-property repair tests.
 * Default secret must differ from the value written before serialize so a failed
 * repair cannot look green by keeping the class default.
 */
class SafeUnserializeProbeTarget
{
    protected int $secret = 0;
    public string $name = '';
}
