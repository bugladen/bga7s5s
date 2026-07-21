<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03035 extends RiskReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Add +1 to Your Pressure Total");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may play this Risk to add +1 to your total for this Pressure: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Play Loyal'), 'use');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPressureOccuring && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null) return;
            if (! ($owner->Location == Game::LOCATION_HAND)) return;

            if (! $this->controlsMoreNonMercenariesThanEachOpponent($event->theah, $owner->ControllerId, $event->location))
            {
                return;
            }

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null) return;

            // WHY: Solomonia / Trial of Faith pattern — flag is read later in pressureLocation() after reactions resolve.
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::LOYAL_PRESSURE_TYPE);
            $game->globals->set(Game::LOYAL_PLAYER_ID, $owner->ControllerId);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to add +1 to their total for this Pressure.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $this->setUsed($event->theah, true);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId === 'use')
        {
            $payEvent = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($payEvent);

            $payTransition = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($payTransition);
        }

        $game->gamestate->nextState("done");
    }

    private function controlsMoreNonMercenariesThanEachOpponent(Theah $theah, int $playerId, string $location): bool
    {
        $characters = $theah->getCharactersAtLocation($location);
        $counts = [];

        foreach ($characters as $character)
        {
            if (! $character->isControlled()) continue;
            if ($character->hasTrait("Mercenary")) continue;

            $controllerId = $character->ControllerId;
            if (! isset($counts[$controllerId]))
            {
                $counts[$controllerId] = 0;
            }
            $counts[$controllerId]++;
        }

        $myCount = $counts[$playerId] ?? 0;
        if ($myCount === 0)
        {
            return false;
        }

        foreach ($counts as $controllerId => $count)
        {
            if ($controllerId == $playerId) continue;
            if ($myCount <= $count)
            {
                return false;
            }
        }

        // Also beat opponents with zero non-Mercs at the location (not present in $counts).
        // Strict ">" vs 0 is already satisfied by $myCount > 0 above.
        return true;
    }
}
