<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

abstract class Leader extends Character
{

    public int $CrewCap;
    public int $ModifiedCrewCap;
    public int $Panache;
    public int $ModifiedPanache;

    public function __construct(){
        parent::__construct();

        $this->CrewCap = 0;
        $this->ModifiedCrewCap = 0;
        $this->Panache = 0;
        $this->ModifiedPanache = 0;
    }

    public function resetCard()
    {
        parent::resetCard();
        
        $this->ModifiedCrewCap = $this->CrewCap;
        $this->ModifiedPanache = $this->Panache;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        
        if ($event instanceof EventSchemeCardRevealed) 
        {
            $scheme = $event->theah->getSchemeById($event->schemeId);
            if ($scheme && $scheme->PanacheModifier != 0 && $event->playerId == $this->ControllerId) 
            {
                $this->ModifiedPanache += $scheme->PanacheModifier;
                $this->IsUpdated = true;

                $event->theah->game->notify->all("panacheModified", clienttranslate('${leader_inject_code}: Panache modified to ${panache} by ${scheme_inject_code}'), [
                    "leader_inject_code" => $this->getInjectCode(),
                    "panache" => $this->ModifiedPanache,
                    "scheme_inject_code" => $scheme->getInjectCode(),
                    "playerId" => $this->ControllerId,
                    "leader" => $this->getPropertyArray($event->theah->game),
                ]);
            }
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $game = $event->theah->game;
            $playerCount = (int) $game->globals->get(Game::PLAYER_COUNT);

            // WHY: Assassination = sole *player* with a Leader in play (rules). Use the
            // Leader *trait*, not instanceof Leader — cards can gain/lose the trait
            // without being a Leader subclass. Count by ControllerId (Bravos can put
            // multiple Leaders under one controller). Exclude this dying card (hub has
            // not moved it yet).
            $playersWithLeaders = [];
            foreach ($event->theah->getCharactersInPlay() as $character)
            {
                if ($character->Id != $this->Id && $character->hasTrait("Leader"))
                {
                    $playersWithLeaders[$character->ControllerId] = true;
                }
            }

            // WHY 2p always ends: classic rule — destroy a Leader, game over. Do not
            // fall through to half-Renown if the trait scan finds 0 or 2+ (e.g. opponent
            // Leader blanked / already gone, or dying player still holds another Leader).
            // Multiplayer only ends when exactly one Leader-holder remains.
            $soleLeaderRemains = count($playersWithLeaders) === 1;
            if ($playerCount === 2 || $soleLeaderRemains)
            {
                if ($soleLeaderRemains)
                {
                    $winnerId = (int) array_key_first($playersWithLeaders);
                }
                else
                {
                    // 2p with no remaining Leader-trait character found — opponent wins.
                    $winnerId = 0;
                    foreach ($game->loadPlayersBasicInfos() as $playerId => $player)
                    {
                        if ((int) $playerId !== (int) $this->ControllerId)
                        {
                            $winnerId = (int) $playerId;
                            break;
                        }
                    }
                }

                $game->notify->all("message", clienttranslate('${player_name} has achieved an ASSASSINATION VICTORY by being the only player with a Leader in play.'), [
                    "player_name" => $game->getPlayerNameById($winnerId),
                ]);

                // WHY: Same score wipe as Dominance — BGA ranks by player_score. Without
                // this, multiplayer losers who still hold Renown after earlier half-
                // penalties could outrank the winner. 2p previously only zeroed the
                // destroyed Leader's controller; wiping all non-winners is equivalent
                // there and correct for 3–4p.
                $players = $game->loadPlayersBasicInfos();
                foreach ($players as $playerId => $player)
                {
                    if ((int) $playerId !== $winnerId)
                    {
                        $game->setPlayerReknown((int) $playerId, -1);
                    }
                }

                $transition = $event->theah->createEvent(Events::Transition);
                if ($transition instanceof EventTransition)
                {
                    $transition->playerId = $this->ControllerId;
                    $transition->transition = "endOfGame";
                }
                $event->theah->queueEvent($transition);
            }
            else
            {
                // Multiplayer with 2+ Leader-holders still in play: half Renown, continue.
                $current = $game->getPlayerReknown($this->ControllerId);

                // Modify current by half, rounded up (keep ceil(current/2))
                $new = (int) ceil($current / 2);

                $game->notify->all("message", clienttranslate('${player_name} will lose half of their Renown (${old_reknown} to ${new_reknown}).'), [
                    "player_name" => $game->getPlayerNameById($this->ControllerId),
                    "old_reknown" => $current,
                    "new_reknown" => $new,
                ]);

                $reknown = $event->theah->createEvent(Events::PlayerLosesReknown);
                if ($reknown instanceof EventPlayerLosesReknown) {
                    $reknown->playerId = $this->ControllerId;
                    $reknown->amount = $current - $new;
                }
                $event->theah->queueEvent($reknown);
            }
        }
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);

        //Add leader specific properties
        $properties['crewCap'] = $this->CrewCap;
        $properties['modifiedCrewCap'] = $this->ModifiedCrewCap;
        $properties['panache'] = $this->Panache;
        $properties['modifiedPanache'] = $this->ModifiedPanache;

        return $properties;
    }

}
