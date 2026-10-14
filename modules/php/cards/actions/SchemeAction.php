<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class SchemeAction extends CardAction
{
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCard($theah);
        if ($owner->Location != Game::LOCATION_PLAYER_HOME)
        {
            return false;
        }

        return true;
    }

    // WHY: Scheme subclasses often build performer lists without calling RiskAction.
    // Filter here when they call parent::getPerformersForAction; subclasses that return
    // their own arrays still need Theah::actionAvailableToPerformer / args filtering.
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_filter(
            $performers,
            fn($performer) => $performer instanceof Character && $performer->canPerformRiskAndSchemeAbilities()
        );
    }
}