<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02032 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Put your Risk from The Locker into your hand.');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may put a Risk from The Locker into your hand: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $lockerName = $theah->game->getPlayerLockerName($owner->ControllerId);
        $lockerCards = $theah->getCardObjectsAtLocation($lockerName);
        foreach ($lockerCards as $card)
        {
            if ($card instanceof Risk)
            {
                $array[] = $this->createButtonProperty($theah->game, $card->Name, "putRisk-$card->Id");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterMustered && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner && $event->characterId == $owner->Id)
            {
                $lockerName = $event->theah->game->getPlayerLockerName($owner->ControllerId);
                $lockerCards = $event->theah->getCardObjectsAtLocation($lockerName);
                $hasRisk = false;
                foreach ($lockerCards as $card)
                {
                    if ($card instanceof Risk)
                    {
                        $hasRisk = true;
                        break;
                    }
                }

                if ($hasRisk)
                {
                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass')
        {
            $cardId = (int) str_replace("putRisk-", "", $reactionId);
            $owner = $this->getOwningCard($game->theah);
            $risk = $game->getCardObjectFromDb($cardId);

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} puts ${risk_inject_code} from The Locker into their hand.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "risk_inject_code" => $risk->getInjectCode(),
            ]);

            $removedFromLockerEvent = EventFactory::createCardRemovedFromLockerEvent($owner->ControllerId, $cardId);
            $game->theah->queueEvent($removedFromLockerEvent);

            $addToHandEvent = EventFactory::createCardAddedToHandEvent($owner->ControllerId, $cardId);
            $game->theah->queueEvent($addToHandEvent);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
