<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01093 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("-1 Riposte and the Adversary discards a Card");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
            return false;

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (!$inDuel)
        {
            return false;
        }

        $riposte = $theah->getCurrentRoundRiposte();
        return $riposte > 0;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            $hand = $event->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            if (count($hand) > 0)
            {
                $transition = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "01093", $this->Id);
                $event->theah->queueEvent($transition);
            }

            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $event->explanations[] = sprintf($event->theah->game->translate("Technique [%s] adds -1 Riposte."), $this->Name);
            $event->riposte -= 1;
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01093)
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

            $owner = $this->getOwningCharacter($game->theah);
            $discardEvent = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $owner->Id, $asPayment = false, $asPlayed = false, $asEffect = true);
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();                
        }
    }
}