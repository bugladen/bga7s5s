<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseHighDrama;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _01144 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Filling The Ranks");
        $this->Image = "img/cards/7s5s/144.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 144;

        $this->Faction = "";
        $this->Initiative = 50;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Conscription",
        ];

        $this->resetCard();
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;
            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. ${player_name} must choose a city location to place renown onto.
            Then if they have the fewest Renown, they may add a Renown to a different location.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, '01144');
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhaseHighDrama && $this->Location == Game::LOCATION_PLAYER_HOME) 
        {
            $game = $event->theah->game;
            list($playerIdWithLeastCharacters, $lowestCount) = $game->getPlayerControllingFewestCharacters();

            if ($playerIdWithLeastCharacters == $this->ControllerId) 
            {
                $characters = $game->theah->getAllCards();
                $characters = array_filter($characters, fn($card) => $card instanceof Character && ! $card->isControlled() && $game->theah->cardInCity($card));
                if (count($characters) == 0)
                {
                    return;
                }
    
                $players = $game->loadPlayersBasicInfos();
    
                // Get the higest stat for the player's leader
                $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);
                $discount = max($leader->ModifiedCombat, $leader->ModifiedFinesse, $leader->ModifiedInfluence);
    
                //Set the discount for recruiting a mercenary.
                $game->globals->set(Game::DISCOUNT, $discount);
    
                $game->notify->all("message", clienttranslate('${scheme_inject_code} Leader Reaction: ${player_name} has the least (non-tied) amount of characters in play (${amount}).
                They may now Recruit a mercenary at a discount of their Leader\'s highest stat.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "amount" => $lowestCount,
                    "player_name" => $players[$this->ControllerId]['player_name'],
                ]);
    
                //Transition to the state where player can choose a mercenary to recruit.
                $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, '01144');
                $event->theah->queueEvent($transition);
            }
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2)
        {
            $args["location"] = $game->globals->get(GAME::CHOSEN_LOCATION);
        }

        if ($state == States::HIGH_DRAMA_BEGINNING_01144)
        {
            $args["discount"] = $game->globals->get(GAME::DISCOUNT);
        }

        if ($state == States::HIGH_DRAMA_BEGINNING_01144_2)
        {
            $args["mercenaryId"] = $game->globals->get(Game::CHOSEN_CARD);
            $args["discount"] = $game->globals->get(GAME::DISCOUNT);
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::HIGH_DRAMA_BEGINNING_01144)
        {
            $mercenary = $game->theah->getCharacterById($id);
            if ($mercenary == null)
            {
                throw new \BgaUserException($game->translate("Invalid character"));
            }

            if ($mercenary->isControlled())
            {
                throw new \BgaUserException($game->translate("Character is already controlled"));
            }

            if (! $game->theah->cardInCity($mercenary))
            {
                throw new \BgaUserException($game->translate("Character is not in the city"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $mercenary->Id);

            $game->gamestate->nextState("mercenaryChosen");    
        }
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01144)
        {
            $location = $ids[0];
            $activePlayerId = $game->getActivePlayerId();
    
            $event = EventFactory::createReknownAddedToLocationEvent($activePlayerId, $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
    
            $game->globals->set(GAME::CHOSEN_LOCATION, $location);
    
            // Get all the reknown to compare
            $players = $game->getObjectListFromDb("SELECT player_id, player_score score FROM player ORDER BY player_score DESC");

            //Find the player with the fewest reknown
            $lowestPlayer = 0;
            $lowestScore = 99;
            foreach ($players as $player) {
                if ($player['score'] < $lowestScore) {
                    $lowestPlayer = $player['player_id'];
                    $lowestScore = $player['score'];
                }
            }

            //See if there is a tie for the fewest reknown
            $lowestScoreCount = 0;
            foreach ($players as $player) 
            {
                if ($player['score'] == $lowestScore) {
                    $lowestScoreCount++;
                }
            }

            if ($activePlayerId == $lowestPlayer && $lowestScoreCount == 1) {
                $game->gamestate->nextState("fewestReknown");                
                return;
            }

            $game->gamestate->nextState("notFewestReknown");                
        }

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2)
        {
            $location = $ids[0];
    
            $event = EventFactory::createReknownAddedToLocationEvent($game->getActivePlayerId(), $location, 1, $this->getInjectCode());
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);
            
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_BEGINNING_01144_2)
        {
            $recruitId = $game->globals->get(Game::CHOSEN_CARD);
            $game->actRecruitMercenary($recruitId, json_encode($ids));

            $game->gamestate->nextState();
        }
    }

}