<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02050 extends RiskReaction
{
    private int $PerformerId = 0;
    private string $TriggeredLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure with Influence to Redirect Renown");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may choose a performer to pressure with Influence and redirect Renown from %s: '), $this->TriggeredLocation);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
        foreach ($characters as $character)
        {
            if ($character->Location != $this->TriggeredLocation && $character->Location != '')
            {
                $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Pressure %s with %s'), $character->Location, $character->Name), "pressure-$character->Id");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventReknownAddedToLocation && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                // Check that the player has at least one character at a different location
                $characters = $event->theah->getCharactersInCityByPlayerId($owner->ControllerId);
                $hasValidPerformer = false;
                foreach ($characters as $character)
                {
                    if ($character->Location != $event->location && $character->Location != '')
                    {
                        $hasValidPerformer = true;
                        break;
                    }
                }

                if ($hasValidPerformer)
                {
                    $this->TriggeredLocation = $event->location;
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($this->PerformerId);

            $game->globals->set(Game::PRESSURE_TYPE, Game::USSURAN_INTRIGUE_PRESSURE_TYPE);
            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_INFLUENCE);
            $pressureEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $game->theah->queueEvent($pressureEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used the Reaction to Pressure ${location_name} with Influence'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'location_name' => $performer->Location,
            ]);

            [$success, $totals, $difference] = $game->pressureLocation($owner->ControllerId, $performer, $performer->Location, Game::STAT_INFLUENCE);

            $pressuredEvent = EventFactory::createLocationPressuredEvent($owner->ControllerId, $performer->Id, $performer->Location, Game::STAT_INFLUENCE, $success, $totals, $difference);
            $pressuredEvent->abilityId = $this->Id;
            $game->theah->queueEvent($pressuredEvent);

            $this->setUsed($game->theah, true);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id && $event->success)
        {
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($this->PerformerId);

            $removeEvent = EventFactory::createRenownRemovedFromLocationEvent($owner->ControllerId, $this->TriggeredLocation, 1, $owner->getInjectCode());
            $event->theah->queueEvent($removeEvent);

            $addEvent = EventFactory::createReknownAddedToLocationEvent($owner->ControllerId, $performer->Location, 1, $owner->getInjectCode(), true);
            $event->theah->queueEvent($addEvent);

            $event->theah->game->notify->all("message", clienttranslate('${reaction_inject_code}: Pressure successful! One Renown moved from ${from_location} to ${to_location}.'), [
                "i18n" => ["from_location", "to_location"],
                "reaction_inject_code" => $owner->getInjectCode(),
                'from_location' => $this->TriggeredLocation,
                'to_location' => $performer->Location,
            ]);
        }

        if ($event instanceof EventPlayerTurnEnd && ($this->PerformerId != 0 || $this->TriggeredLocation != ''))
        {
            $owner = $this->getOwningCard($event->theah);
            $this->PerformerId = 0;
            $this->TriggeredLocation = '';
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'decline')
        {
            $characterId = (int) str_replace("pressure-", "", $reactionId);
            $this->PerformerId = $characterId;

            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }
        else
        {
            $this->TriggeredLocation = '';

            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
