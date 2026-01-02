<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01037 extends CardReaction
{
    private int $ChallengerId;
    private int $DefenderId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Engaged Participant to Location After Duel");
        $this->ChallengerId = 0;
        $this->DefenderId = 0;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may move Engaged Participant to her location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $challenger = $theah->getCharacterById($this->ChallengerId);
        $defender = $theah->getCharacterById($this->DefenderId);

        $owner = $this->getOwningCharacter($theah);
        $locations = $theah->getAdjacentCityLocations($owner->Location, false);

        if ($challenger->Engaged && in_array($challenger->Location, $locations))
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move %s to %s'), $theah->game->translate($challenger->Name), $theah->game->translate($owner->Location)), "move-{$challenger->Id}");
        }
        if ($defender->Engaged && in_array($defender->Location, $locations))
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move %s to %s'), $theah->game->translate($defender->Name), $theah->game->translate($owner->Location)), "move-{$defender->Id}");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelStarted && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            $this->ChallengerId = $event->challengerId;
            $this->DefenderId = $event->defenderId;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelEnd && $this->isAvailable())
        {
            $owner = $this->getOwningCharacter($event->theah);
            $locations = $event->theah->getAdjacentCityLocations($owner->Location, false);

            $challenger = $event->theah->getCharacterById($this->ChallengerId);
            $defender = $event->theah->getCharacterById($this->DefenderId);
            if (($challenger->Engaged && in_array($challenger->Location, $locations)) || ($defender->Engaged && in_array($defender->Location, $locations)))
            {
                $transitionEvent = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass')
        {
            //Get the character id from the reactionId
            $characterId = str_replace("move-", "", $reactionId);
            $character = $game->theah->getCharacterById($characterId);
            $owner = $this->getOwningCharacter($game->theah);
            $event = EventFactory::createCardMovingEvent($owner->ControllerId, $character->Id, $character->Location, $owner->Location, $engage=false, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and moved ${character_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
                "location_name" => $game->translate($owner->Location),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}