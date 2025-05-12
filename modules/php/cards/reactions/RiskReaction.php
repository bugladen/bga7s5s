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
        $announcement .= $game->translate("Played") . "<strong>" . $game->translate($risk->Name) . "</strong>.";

        return $announcement;
    }

    public function getReactionDescription(Theah $theah): string
    {
        $owner = $this->getOwningCard($theah);
        return $theah->game->translate("Faction Hand") . " > " . $theah->game->translate($owner->Name) . " > " . $theah->game->translate("Reaction: ");
    }
}