<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02019 extends RiskReaction
{
    private string $Location = '';
    private ?int $PerformerId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Pressure: Each Player +1 to total for Wounds");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to wound a Performer for each Player to add +1 to total for Wounds: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        if ($this->Location != '')
        {
            $owner = $this->getOwningCard($theah);
            $characters = $theah->getCharactersAtLocation($this->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId);
            foreach ($characters as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, $character->Name, "add1ToTotalForWounds-$character->Id");
            }
        }
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($this->PerformerId);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($this->PerformerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::TRIAL_OF_FAITH_PRESSURE_TYPE);

            $game->notify->all('message', clienttranslate('${owner_inject_code}: ${player_name} used Reaction to add 1 Wound to ${character_inject_code}.'), [
                'i18n' => ['character_inject_code'],
                'owner_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $character->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
            $this->Location = '';
        }

        if ($event instanceof EventPressureOccuring && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND && in_array(Game::STAT_INFLUENCE, $event->pressureTypes))
            {
                $characters = $event->theah->getCharactersAtLocation($event->location);
                $characters = array_filter($characters, fn($character) => $character->ControllerId == $owner->ControllerId);
                if (count($characters) > 0)
                {
                    $this->Location = $event->location;
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }

        if ($event instanceof EventLocationPressureResult && $this->PerformerId)
        {
            $owner = $this->getOwningCard($event->theah);
            $performer = $event->theah->getCharacterById($this->PerformerId);
            if ($performer->hasTrait("Zealot") && $performer->Wounds > 0)
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent($performer->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $event->theah->queueEvent($healEvent);
            }
            $this->PerformerId = null;
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass')
        {
            $owner = $this->getOwningCard($game->theah);
            $characterId = str_replace("add1ToTotalForWounds-", "", $reactionId);
            $character = $game->theah->getCharacterById($characterId);
            $this->PerformerId = $character->Id;
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        $game->gamestate->nextState("done");
    }
}