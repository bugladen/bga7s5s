<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;

/** Lightweight Attachment (engage state / dueling-line cards) for StatesTrait tests. */
class GenericAttachment extends Attachment
{
    public function __construct(string $name = 'Generic Attachment')
    {
        parent::__construct();
        $this->Name = $name;
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
    }
}
