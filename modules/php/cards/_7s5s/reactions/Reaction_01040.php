<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01040 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("(Continuous) Engage Weapon Instead when Intervening");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate("Engage Weapon Instead: ");
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        foreach ($owner->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment->hasTrait("Weapon") && !$attachment->Engaged)
            {
                $array[] = $this->createButtonProperty($theah->game, $theah->game->translate("Engage: ") . $theah->game->translate($attachment->Name), "engageWeapon-$attachment->Id");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate("Decline"), "decline");

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // $this->isAvailable() is not checked because this reaction is always available
        if ($event instanceof EventCharacterIntervened)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->playerId == $owner->ControllerId && $event->newTargetId == $owner->Id && $owner->hasEngardeWeaponEquipped($event->theah))
            {
                $transition = EventFactory::createReactionTransitionEvent($event->playerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId != "decline")
        {
            $attachmentId = str_replace("engageWeapon-", "", $reactionId);

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $attachmentId, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            //This reaction always available, do not call $this->setUsed()
        }
        else
        {
            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $owner->Id);
            $game->theah->queueEvent($engageEvent);
        }

        $game->gamestate->nextState("done");        
    }
}