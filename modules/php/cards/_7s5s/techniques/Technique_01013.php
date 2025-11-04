<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01013 extends Technique
{
    public bool $UseThrust;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Thrust or +1 Parry");
        $this->UseThrust = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL);
        if (! $inDuel)
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        $adversary = $theah->getDuelRoundOpponent();

        return $actor->Wounds >= $adversary->Wounds;
    }

    public function handleEvent(Event $event)
    { 
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01013", $this->Id);
            $transition->priority = Event::HIGH_PRIORITY;
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            if ($this->UseThrust)
            {
                $event->thrust += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Thrust."), $this->Name);
            }
            else
            {
                $event->parry += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Parry."), $this->Name);
            }        
        }

        if ($event instanceof EventDuelEnd)
        {
            $this->UseThrust = false;
            $card = $this->getOwningCard($event->theah);
            $card->IsUpdated = true;
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_01013)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $this->UseThrust = $id == 1;
            $game->updateCardObjectInDb($owner);

            $game->gamestate->nextState();    
        }
    }
}