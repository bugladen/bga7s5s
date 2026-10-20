<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

/** Lightweight Scheme for StatesTrait planning-phase tests (initiative / When Revealed). */
class GenericScheme extends Scheme
{
    public bool $whenRevealed = false;

    public function __construct(string $name = 'Generic Scheme', int $initiative = 1, bool $whenRevealed = false)
    {
        parent::__construct();
        $this->Name = $name;
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->Initiative = $initiative;
        $this->whenRevealed = $whenRevealed;
    }

    public function hasWhenRevealedEffect(): bool
    {
        return $this->whenRevealed;
    }
}
