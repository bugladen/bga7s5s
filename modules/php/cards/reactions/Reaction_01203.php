<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01203 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Add or Remove Threat");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('A challenge is at your location. ${you} may choose to Add or Remove Threat from a Character: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $challengerId = $theah->getDuelChallengerId();
        $defenderId = $theah->getDuelDefenderId();
        $challenger = $theah->getCharacterById($challengerId);
        $defender = $theah->getCharacterById($defenderId);

        $challengerThreat = $theah->game->globals->get(GAME::CHALLENGER_THREAT);
        $defenderThreat = $theah->game->globals->get(GAME::DEFENDER_THREAT);
        
        $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('+1 Threat to %s (Current Threat: %d)'), $challenger->Name, $challengerThreat), 'addThreatChallenger');
        $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('+1 Threat to %s (Current Threat: %d)'), $defender->Name, $defenderThreat), 'addThreatDefender');
        
        if ($challengerThreat > 0)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('-1 Threat from %s (Current Threat: %d)'), $challenger->Name, $challengerThreat), 'removeThreatChallenger');
        }

        if ($defenderThreat > 0)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('-1 Threat from %s (Current Threat: %d)'), $defender->Name, $defenderThreat), 'removeThreatDefender');
        }

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelNewRound && $event->round == 1)
        {
            $leja = $this->getOwningCharacter($event->theah);

            if ($leja->isControlled())
            {
                $challenger = $event->theah->getCharacterById($event->challengerId);
                $defender = $event->theah->getCharacterById($event->defenderId);
    
                if ($leja->Location == $challenger->Location || $leja->Location == $defender->Location)
                {
                    $transition = EventFactory::createReactionTransitionEvent($leja->ControllerId, $leja->Id, $this->Id);
                    $event->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $leja = $this->getOwningCharacter($game->theah);

        $challengerId = $game->theah->getDuelChallengerId();
        $challenger = $game->theah->getCharacterById($challengerId);

        $defenderId = $game->theah->getDuelDefenderId();
        $defender = $game->theah->getCharacterById($defenderId);

        $playerName = $game->getActivePlayerName();

        if ($reactionId == 'addThreatChallenger')
        {
            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to add 1 Threat to ${challenger_inject_code}.'), [
                "owner_inject_code" => $leja->getInjectCode(),
                "player_name" => $playerName,
                "challenger_inject_code" => $challenger->getInjectCode(),
            ]);

            $event = EventFactory::createThreatModifiedEvent(1, 0);
            $game->theah->queueEvent($event);
        }

        if ($reactionId == 'addThreatDefender')
        {
            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to add 1 Threat to ${defender_inject_code}.'), [
                "owner_inject_code" => $leja->getInjectCode(),
                "player_name" => $playerName,
                "defender_inject_code" => $defender->getInjectCode(),
            ]);

            $event = EventFactory::createThreatModifiedEvent(0, 1);
            $game->theah->queueEvent($event);
        }
        
        if ($reactionId == 'removeThreatChallenger')
        {
            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to remove 1 Threat to ${challenger_inject_code}.'), [
                "owner_inject_code" => $leja->getInjectCode(),
                "player_name" => $playerName,
                "challenger_inject_code" => $challenger->getInjectCode(),
            ]);

            $event = EventFactory::createThreatModifiedEvent(-1, 0);
            $game->theah->queueEvent($event);
        }
        
        if ($reactionId == 'removeThreatDefender')
        {
            $game->notifyAllPlayers("message", clienttranslate('${owner_inject_code}: ${player_name} used Reaction to remove 1 Threat to ${defender_inject_code}.'), [
                "owner_inject_code" => $leja->getInjectCode(),
                "player_name" => $playerName,
                "defender_inject_code" => $defender->getInjectCode(),
            ]);

            $event = EventFactory::createThreatModifiedEvent(0, -1);
            $game->theah->queueEvent($event);
        }

        $this->setUsed($game->theah, true);

        $game->gamestate->nextState("done");        

    }
}
