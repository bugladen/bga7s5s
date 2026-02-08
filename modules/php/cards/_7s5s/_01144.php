<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01144;

class _01144 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Filling The Ranks");
        $this->Image = "01144.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 144;

        $this->Initiative = 50;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Conscription",
        ];

        $this->resetCard();

        $this->Reactions = [
            new Reaction_01144(),
        ];
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
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2)
        {
            $args["location"] = $game->globals->get(GAME::CHOSEN_LOCATION);
        }

        return $args;
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
    }

}