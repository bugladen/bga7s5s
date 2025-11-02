<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01200 extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gain Renown");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $skull = $this->getOwningAttachment($theah);
        if ($skull instanceof _01200)
        {
            $card = $theah->getCardById($skull->ChosenCard);
        }
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('Chosen card %s was played.${you} may choose to gain Renown: '), $card->Name);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Gain Renown'), 'gainReknown');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventApproachCharacterPlayed || $event instanceof EventCharacterMustered) && $this->ownerIsAttached($event->theah) && $this->isAvailable())
        {
            $attachment = $this->getOwningAttachment($event->theah);
            if ($attachment->isAttached() && $attachment instanceof _01200 && $event->characterId == $attachment->ChosenCard)
            {
                $event->theah->game->notifyAllPlayers("message", clienttranslate('Crystal Eye has triggered because its targeted card was played.'), []);
                $transition = EventFactory::createReactionTransitionEvent($attachment->ControllerId, $attachment->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventSchemeCardRevealed && $this->ownerIsAttached($event->theah) && $this->isAvailable())
        {
            $attachment = $this->getOwningAttachment($event->theah);
            if ($attachment->isAttached() && $attachment instanceof _01200 && $event->scheme->Id == $attachment->ChosenCard)
            {
                $event->theah->game->notifyAllPlayers("message", clienttranslate('Crystal Eye has triggered because its targeted card was played.'), []);
                $transition = EventFactory::createReactionTransitionEvent($attachment->ControllerId, $attachment->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "gainReknown")
        {
            $owner = $this->getOwningCard($game->theah);
            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to gain a Renown.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getActivePlayerName(), 
            ]);

            $reknownEvent = EventFactory::createPlayerGainsReknownEvent($game->getActivePlayerId(), 1);
            $game->theah->eventCheck($reknownEvent);
            $game->theah->queueEvent($reknownEvent);
        }

        $game->gamestate->nextState("done");
    }
    
}
