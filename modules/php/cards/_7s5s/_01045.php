<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01045 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("The Song of Eisen");
        $this->Image = "01045.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 45;

        $this->initializeFaction("Eisen");
        $this->Initiative = 67;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Prepared",
        ];

        $this->Text = "<p>Add a Renown to [The Forums].</p><p>Put target Mercenary from the City Deck discard pile on top of the City Deck.</p><p>[BAR]</p><p>Your Leader gains +1[inf] while parleying with a Mercenary.</p><p>Reaction: At the end of High Drama, if there are no available Mercenaries and attachments • Gain a Renown.</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01045(),
        ];
    }

    public function getParleyDiscount(Theah $theah, Character $performer, bool $parleying, Array &$explanations) : int
    {
        $discount = parent::getParleyDiscount($theah, $performer, $parleying, $explanations);
        if ($this->Location == Game::LOCATION_PLAYER_HOME && $parleying && $performer->ControllerId == $this->ControllerId && $performer instanceof Leader)
        {
            $discount += 1;
            $explanations[] = sprintf($theah->game->translate("%s: -1 because performer is a Leader Parleying with a Mercenary."), $this->getInjectCode());
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  
            Renown will be added to The Forum. 
            ${player_name} will now search the City Deck discard pile for a Mercenary to place on top of the City Deck.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($event->playerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose a mercenary out of the City Deck discard pile
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, '01045');
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01045)
        {
            $playerId = $game->getActivePlayerId();
            $card = $game->getCardObjectFromDb($id);
    
            $removeEvent = EventFactory::createCardRemovedFromCityDiscardPileEvent($playerId, $card->Id);
            $game->theah->eventCheck($removeEvent);
    
            $addEvent = EventFactory::createCardAddedToCityDeckEvent($playerId, $card->Id, true);
            $game->theah->eventCheck($addEvent);
    
            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);
    
            $game->gamestate->nextState("");    
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01045)
        {
            $deck = $game->getGameDeckObject();
            $cardObjects = $deck->getCardsInLocation(Game::LOCATION_CITY_DISCARD);   

            $ids = array_column($cardObjects, 'id');
            foreach ($ids as $id)
            {
                $card = $game->getCardObjectFromDb($id);
                if ($card->hasTrait('Mercenary') && $card->Location == Game::LOCATION_CITY_DISCARD)
                {
                    throw new \BgaUserException($game->translate("There are Mercenaries in the City Deck Discard Pile"));
                }
            }
                   
            $game->gamestate->nextState("");
 
        }
    }
}