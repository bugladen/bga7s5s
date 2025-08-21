<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01016 extends Scheme implements IHasReactions
{
    use ReactionTrait;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Plans Within Plans";
        $this->Image = "img/cards/7s5s/016.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 16;

        $this->Faction = "Vodacce";
        $this->Initiative = 73;
        $this->PanacheModifier = -1;

        $this->Traits = [
            "Cunning", 
            "Gang",
        ];

        $this->Reactions = [
            new Reaction_01016(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        //Two locations will each get one Reknown.
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose two city locations to place reknown onto. 
            Then they must search their deck for a Red Hand Thug, reaveal it, and put it in their hand.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01016");
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2)
        {
            $playerId = $game->getActivePlayerId();
            $location = $game->getPlayerFactionDeckName($playerId);
            $deckObject = $game->getGameDeckObject();
            $deck = $deckObject->getCardsInLocation($location);
            $thugs = [];
            foreach ($deck as $deckCard) {
                $card = $game->getCardObjectFromDb($deckCard['id']);
                if ($card->HasTrait("Red Hand") && $card->HasTrait("Thug")) 
                {
                    $thugs[] = $card->getPropertyArray($game);
                }
            }            

            $args["thugs"] = $thugs;
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2)
        {
            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Invalid card ID."));
            }

            $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
            if ($card->Location != $deckName)
            {
                throw new \BgaUserException($game->translate("Card is not in your Faction Deck."));
            }
    
            $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($this->ControllerId, $card->Id);
            $game->theah->eventCheck($removeEvent);
    
            $addEvent = EventFactory::createCardAddedToHandEvent($this->ControllerId, $card->Id);
            $game->theah->eventCheck($addEvent);
    
            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);
    
            $game->globals->set(GAME::CHOSEN_CARD, $card->Id);
    
            $game->gamestate->nextState("cardChosen");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2)
        {
            $deck = $game->getGameDeckObject();
            $deckName = $game->getPlayerFactionDeckName($this->ControllerId);
            $cards = $deck->getCardsInLocation($deckName);

            $count = 0;
            foreach ($cards as $cardObject)
            {
                $card = $game->theah->getCardById($cardObject['id']);
                if ($card->HasTrait("Red Hand") && $card->HasTrait("Thug"))
                {
                    $count++;
                }
            }

            if ($count > 0)
            {
                throw new \BgaUserException($game->translate("There are Red Hand Thugs in your Faction Deck."));
            }

            $game->gamestate->nextState("pass");
        }
    }
}