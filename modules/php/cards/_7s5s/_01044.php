<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action_01044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01044 extends Scheme implements IHasActions
{
    use ActionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Armed and Marshaled");
        $this->Image = "img/cards/7s5s/044.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 44;

        $this->Faction = "Eisen";
        $this->Initiative = 37;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Duress", 
            "Logistics",
        ];

        $this->Actions = [
            new Action_01044(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves.  
            Reknown will be added to The Docks and The Grand Bazaar. 
            ${player_name} will now search their discard pile for an attachment.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "scheme_name" => $this->Name,
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);
            
            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose an item out of their discard pile
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, '01044');
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $actionId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $actionId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01044)
        {
            $playerId = $game->getActivePlayerId();
            $card = $game->getCardObjectFromDb($id);
            if (! $card)
            {
                throw new \BgaUserException($game->translate("Invalid card"));
            }

            //Make sure the card is in the discard pile
            $deck = $game->getGameDeckObject($playerId);
            $discardPileName = $game->getPlayerDiscardDeckName($playerId);
            $cardObjects = $deck->getCardsInLocation($discardPileName);
            if (! in_array($card->Id, array_column($cardObjects, 'id')))
            {
                throw new \BgaUserException($game->translate("Card is not in the discard pile"));
            }
    
            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $card->Id);
            $game->theah->eventCheck($removeEvent);
    
            $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
            $game->theah->eventCheck($addEvent);
    
            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);
    
            $game->gamestate->nextState("");
        }
    }
}