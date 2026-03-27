<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01143;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01143 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Contempt and Hatred");
        $this->Image = "01143.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 143;

        $this->Initiative = 43;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Demoralize"), 
            clienttranslate("Duress"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Forums]. Then, you may add another Renown to any location. If you do, discard all City Cards there.</p><p>[BAR]</p><p>All Mercenaries have -1 [Influence].</p><p>City Action: Engage your performer • Pressure with [Influence]. You succeed even if tied. If successful, claim the location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01143(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id) 
        {

            //Decrease the influence of all mercenaries by 1
            $mercenaries = $event->theah->getCharactersInPlay();
            $mercenaries = array_filter($mercenaries, fn($character) => $character->hasTrait("Mercenary"));
            foreach ($mercenaries as $mercenary)
            {
                $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                    $this->ControllerId, 
                    $mercenary->Id, 
                    $mercenary->ModifiedInfluence, 
                    $mercenary->ModifiedInfluence - 1,
                    $this->getInjectCode()
                );
        
                $event->theah->queueEvent($modifiedEvent);      
            }

            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  Renown will be added to The City Forum.
            Then ${player_name} may choose a city location to place Renown onto. If they do, all City Cards will be discarded from that location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            //Transition to the state where player can choose any location.
            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01143");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCardSentToLocker && $event->cardId == $this->Id)
        {
            //Restore the influence of all mercenaries
            $mercenaries = $event->theah->getCharactersInPlay();
            $mercenaries = array_filter($mercenaries, fn($character) => $character->hasTrait("Mercenary"));
            foreach ($mercenaries as $mercenary)
            {
                $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                    $this->ControllerId, 
                    $mercenary->Id, 
                    $mercenary->ModifiedInfluence, 
                    $mercenary->ModifiedInfluence + 1,
                    $this->getInjectCode()
                );
        
                $event->theah->queueEvent($modifiedEvent);      
            }
        }

        if ($event instanceof EventCharacterRecruited && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            $modifiedEvent = EventFactory::createCharacterInfluenceModifiedEvent(
                $event->playerId, 
                $event->characterId, 
                $character->ModifiedInfluence, 
                $character->ModifiedInfluence - 1,
                $this->getInjectCode()
            );

            $event->theah->queueEvent($modifiedEvent);
        }
    }
    
    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state === States::PLANNING_PHASE_RESOLVE_SCHEMES_01143) 
        {
            $location = $ids[0];
            $playerId = $game->getActivePlayerId();
    
            $event = EventFactory::createReknownAddedToLocationEvent($playerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            //Get all cards in the chosen location
            $game->theah->buildCity();
            $cards = $game->theah->getCardObjectsAtLocation($location);
            foreach ($cards as $card)
            {
                //Discard all city cards
                if ($card instanceof ICityDeckCard)
                {
                    $discard = EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $card->Id, $location, $this->Id, $asEffect = true);
                    $game->theah->queueEvent($discard);
                }
            }
    
            $game->gamestate->nextState("");
        }
    }
}