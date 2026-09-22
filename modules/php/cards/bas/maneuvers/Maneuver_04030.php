<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;

class Maneuver_04030 extends Maneuver
{
    private bool $ChooseParry = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("+1 Parry or +1 Thrust");
        $this->ChooseParry = false;
    }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, array &$explanations): int
    {
        $discount = parent::getManeuverFromCombatCardDiscount($theah, $combatCard, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Id == $combatCard->Id)
        {
            $actor = $theah->getDuelRoundActor();
            if ($actor !== null
                && ($actor->hasTrait("Merchant") || $actor->hasTrait("Scoundrel")))
            {
                $discount += 1;
                $explanations[] = sprintf(
                    $theah->game->translate("%s: -1 because your performer is a Merchant or Scoundrel."),
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04030", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventDuelCalculateManeuverValues && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($this->ChooseParry)
            {
                $event->parry += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Parry."), $owner->getInjectCode());
            }
            else
            {
                $event->thrust += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s adds 1 Thrust."), $owner->getInjectCode());
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

        if ($state == States::DUEL_RESOLVE_MANEUVER_04030)
        {
            $owner = $this->getOwningCard($game->theah);
            if ($id == 1)
            {
                $this->ChooseParry = true;
                $game->notify->all("message", clienttranslate('${card_inject_code} adds 1 Parry.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                ]);
            }
            else if ($id == 2)
            {
                $this->ChooseParry = false;
                $game->notify->all("message", clienttranslate('${card_inject_code} adds 1 Thrust.'), [
                    "card_inject_code" => $owner->getInjectCode(),
                ]);
            }

            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState();
    }
}
