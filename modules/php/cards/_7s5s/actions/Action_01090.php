<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01090 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Pre-Activate Reaction (Continuous)");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner instanceof IHasReactions)
        {
            $reaction = $owner->getReactionById($owner->Id . "_Reaction_01090");

            return $reaction->isAvailable();
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner instanceof IHasReactions)
            {
                $reaction = $owner->getReactionById($owner->Id . "_Reaction_01090");
                $reaction->setUsed($event->theah, true);
    
                $game = $event->theah->game;
                $game->globals->set(Game::OVERRIDE_AS_NOT_FIRST_PLAYER, true);
                $game->globals->set(Game::EXTRA_ACTIONS, 1);

                $game->notify->all("overrideAsNotFirstPlayer", clienttranslate('${owner_inject_code}: ${player_name} activated Reaction and will resolve their next ability as if they were not the First Player.'), [
                    "owner_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getActivePlayerName(),
                ]);

                //Always available to use
                $this->setUsed($event->theah, false);
            }
        }
    }
}