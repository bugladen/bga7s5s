<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03024 extends Maneuver
{
    private bool $ChooseParry = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+2 Parry or +2 Thrust");
        $this->ChooseParry = false;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        return $adversary->hasTrait('Sorcerer') || $adversary->hasTrait('Monster');
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03024", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($this->ChooseParry)
            {
                $event->parry += 2;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Parry."), $owner->getInjectCode());
            }
            else
            {
                $event->thrust += 2;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 2 Thrust."), $owner->getInjectCode());
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->ChooseParry = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03024)
        {
            $owner = $this->getOwningCard($game->theah);
            if ($id == 1)
            {
                // Choose Parry
                $this->ChooseParry = true;
                $game->notify->all("message", clienttranslate('${card_inject_code} adds 2 Parry.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                ]);
            }
            else if ($id == 2)
            {
                // Choose Thrust
                $this->ChooseParry = false;
                $game->notify->all("message", clienttranslate('${card_inject_code} adds 2 Thrust.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                ]);
            }

            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState();
    }
}
