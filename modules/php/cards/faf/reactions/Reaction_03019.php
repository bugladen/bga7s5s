<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03019 extends AttachmentReaction
{
    private int $OpposingCharacterId = 0;
    private string $NewLocation = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Hunt Opposing Character to Adjacent City Location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may engage this card to move the equipped character to the opposing character\'s new location and engage that character: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Hunt'), 'hunt');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved))
        {
            return;
        }

        if (! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner == null || $owner->Engaged)
        {
            return;
        }

        $owningCharacter = $this->getOwningCharacter($event->theah);
        if ($owningCharacter == null || ! $event->theah->cardInCity($owningCharacter))
        {
            return;
        }

        $character = $event->theah->getCardById($event->cardId);
        if (! ($character instanceof Character))
        {
            return;
        }

        if ($character->ControllerId == $owningCharacter->ControllerId)
        {
            return;
        }

        if ($event->fromLocation != $owningCharacter->Location)
        {
            return;
        }

        if (! $event->theah->locationInCity($event->toLocation))
        {
            return;
        }

        $adjacent = $event->theah->getAdjacentCityLocations($owningCharacter->Location, false);
        if (! in_array($event->toLocation, $adjacent))
        {
            return;
        }

        $this->OpposingCharacterId = $character->Id;
        $this->NewLocation = $event->toLocation;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'hunt')
        {
            $owner = $this->getOwningCard($game->theah);
            $owningCharacter = $this->getOwningCharacter($game->theah);
            $opposing = $game->theah->getCharacterById($this->OpposingCharacterId);

            $engageOwnerEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageOwnerEvent);

            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $owningCharacter->Id, $owningCharacter->Location, $this->NewLocation, false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $engageOpposingEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $opposing->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageOpposingEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} engages ${owner_inject_code}, moves ${character_inject_code} to ${location_name}, and engages ${opposing_inject_code}.'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $owningCharacter->getInjectCode(),
                "opposing_inject_code" => $opposing->getInjectCode(),
                "location_name" => $game->translate($this->NewLocation),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
