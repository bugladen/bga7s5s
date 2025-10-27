<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_01108a extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Adversary Discards a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if (! $actor->hasTrait("Scoundrel"))
        {
            return false;
        }

        $adversaryId = $theah->getDuelOpponentId($playerId);
        $adversary = $theah->getCharacterById($adversaryId);
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
        return count($hand) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversaryId = $event->theah->getDuelOpponentId($owner->ControllerId);
            $adversary = $event->theah->getCharacterById($adversaryId);
            $transitionEvent = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "01108", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_01108)
        {
            $card = $game->getCardObjectFromDb($id);

            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $playerId = $game->getActivePlayerId();

            if ($card->ControllerId != $playerId)
            {
                throw new \BgaUserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new \BgaUserException($game->translate("Card not in your hand"));
            }

            $card = $game->getCardObjectFromDb($id);
            $owner = $this->getOwningCard($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $owner->Id, $asPayment = false, $asPlayed = false, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();                
        }  
    }
}