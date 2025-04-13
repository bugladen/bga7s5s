<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class RiskReaction extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getReactionAnnouncement(Game $game, int $state, string $internalId, string $reactionId): string
    {
        $announcement = parent::getReactionAnnouncement($game, $state, $internalId, $reactionId);

        $risk = $this->getOwningCard($game->theah);
        $announcement .= "Played <strong>$risk->Name</strong>. ";

        return $announcement;
    }

    public function getReactionDescription(Theah $theah): string
    {
        $owner = $this->getOwningCard($theah);
        return parent::getReactionDescription($theah) . "Faction Hand > " . $owner->Name . " > Reaction: ";
    }
}