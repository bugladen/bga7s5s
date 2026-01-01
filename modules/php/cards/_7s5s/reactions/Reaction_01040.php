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
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate("Engage: ") . $theah->game->translate($attachment->Name), "engageWeapon-$attachment->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate("Decline"), "decline");

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterIntervened)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->playerId == $owner->ControllerId && $event->newTargetId == $owner->Id && count($owner->Attachments) > 0)
            {
                $transition = EventFactory::createReactionTransitionEvent($event->playerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "decline")
        {
            $owner = $this->getOwningCharacter($game->theah);
            $attachmentId = str_replace("engageWeapon-", "", $reactionId);

            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $owner->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $attachmentId, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            //This reaction always available, do not set it as used
        }

        $game->gamestate->nextState("done");        
    }
}