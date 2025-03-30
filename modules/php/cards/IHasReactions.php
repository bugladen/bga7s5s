<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\Reaction;

interface IHasReactions
{
    public function getReactions(): Array;

    public function addReactionProperties(&$properties);

    public function anyReactionsAvailable(): bool;

    public function getReactionById($id): ?Reaction;

    public function getReactionNames($includeAvailable = false): Array;

    public function getReactionsArray(): Array;

    public function updateReactionOwnerIds($id);

}