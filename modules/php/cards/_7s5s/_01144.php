<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseHighDrama;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class _01144 extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Filling The Ranks";
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
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} now resolves. ${player_name} must choose a city location to place reknown onto.
            Then if they have the fewest Reknown, they may add a Reknown to a different location.'), [
                "scheme_name" => "<span style='font-weight:bold'>{$this->Name}</span>",
                "player_name" => $event->playerName,
            ]);

            //Transition to the state where player can choose two locations.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $event->playerId;
                $transition->transition = '01144';
            }
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventPhaseHighDrama) 
        {
            list($playerIdWithLeastCharacters, $lowestCount) = $event->theah->game->getPlayerControllingFewestCharacters();

            if ($playerIdWithLeastCharacters != $this->ControllerId) 
            {
                $player_name = $event->theah->game->getPlayerNameById($this->ControllerId);

                $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} Leader Reaction: ${player_name} DOES NOT have the least (non-tied) amount of characters in play.'), [
                    "scheme_name" => "<strong>{$this->Name}</strong>",
                    "player_name" => $player_name,
                ]);

                return;
            }

            $players = $event->theah->game->loadPlayersBasicInfos();

            // Get the higest stat for the player's leader
            $leader = $event->theah->getLeaderByPlayerId($this->ControllerId);
            $discount = max($leader->ModifiedCombat, $leader->ModifiedFinesse, $leader->ModifiedInfluence);

            //Set the discount for recruiting a mercenary.
            $event->theah->game->globals->set(Game::DISCOUNT, $discount);

            $event->theah->game->notifyAllPlayers("message", clienttranslate('${scheme_name} Leader Reaction: ${player_name} has the least (non-tied) amount of characters in play (${amount}).
            They may now Recruit a mercenary at a discount of their Leader\'s highest stat.'), [
                "scheme_name" => "<strong>{$this->Name}</strong>",
                "amount" => $lowestCount,
                "player_name" => $players[$this->ControllerId]['player_name'],
            ]);

            //Transition to the state where player can choose a mercenary to recruit.
            $transition = $event->theah->createEvent(Events::Transition);
            if ($transition instanceof EventTransition) {
                $transition->playerId = $this->ControllerId;
                $transition->transition = '01144';
            }
            $event->theah->queueEvent($transition);
        }
    }
}