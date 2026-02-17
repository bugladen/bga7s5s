<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Maneuver implements ICardAbility
{
    use CardAbilityTrait;

    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;

    public function __construct()
    {
        $this->initializeAbility();
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
    }

    public function eventCheck(Event $event) {}

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuskEndOfDay && $this->ResetOnDayEnd)
        {
            $this->setUsed($event->theah, false);
        }

        if ($event instanceof EventDuelEnd && $this->ResetOnDuelEnd)
        {
            $this->setUsed($event->theah, false);
        }
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        return true;
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        return [];
    }

    public function actFromManeuverPass(Game $game, int $state): void { }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void { }

    public function stateFromManeuver(Game $game, int $state, string $stateName): void { }

    public function actFromManeuverWithIds(Game $game, int $state, string $stateName, array $ids): void { }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int { return 0; }

    public function getManeuverFromCombatCardDiscount(Theah $theah, Card $combatCard, Array &$explanations): int { return 0; }

    public function doCost(Game $game): void {}

    public function doEffect(Game $game): void {}

}