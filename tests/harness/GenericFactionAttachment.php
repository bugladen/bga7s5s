<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

/**
 * Faction Attachment fixture for equip tests: hand-card with a Wealth cost, optional
 * "can equip to opponents", and an optional redirect for getRequiredAttachTargetId.
 */
class GenericFactionAttachment extends FactionAttachment
{
    /** When set, getRequiredAttachTargetId returns this instead of the chosen performer. */
    public ?int $attachTargetOverride = null;

    public function __construct(string $name = 'Test Attachment', int $wealthCost = 0, bool $canEquipToOpponents = false)
    {
        parent::__construct();
        $this->Name = $name;
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->WealthCost = $wealthCost;
        $this->CanEquipToOpponents = $canEquipToOpponents;
    }

    public function getRequiredAttachTargetId(Theah $theah, int $originalTargetId): int
    {
        return $this->attachTargetOverride ?? $originalTargetId;
    }
}
