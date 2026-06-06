<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01070 extends CardReaction
{
    private string $location = "";

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Add Another Renown");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . sprintf($theah->game->translate('${you} may add another Renown to %s by discarding a card from your hand: '), $this->location);
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $owner = $this->getOwningCard($theah);
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
        foreach ($hand as $card)
        {
            $array[] = $this->createButtonProperty($theah->game, $theah->game->translate($card->Name), "addAnotherRenown-$card->Id");
        }
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventRenownAddedToLocation && $this->isAvailable() && ! $event->isMove)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($event->playerId == $owner->ControllerId)
            {
                $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId);
                if (count($hand) > 0)
                {
                    $this->location = $event->location;
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $owner = $this->getOwningCard($game->theah);
            $cardId = str_replace("addAnotherRenown-", "", $reactionId);
            $card = $game->theah->getCardById($cardId);
            $event = EventFactory::createCardDiscardedFromHandEvent($owner->ControllerId, $card->Id, $owner->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createRenownAddedToLocationEvent($owner->ControllerId, $this->location, 1, $owner->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction.'), [
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'reaction_inject_code' => $owner->getInjectCode(),
            ]);

            $this->location = "";
            $owner->IsUpdated = true;
            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}