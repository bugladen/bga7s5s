<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01201;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01201;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;

class Action_01201_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01201';
    }

    /** @return array{0:_01201,1:Action_01201} */
    private function armed(TestWorld $world): array
    {
        $ravenna = $world->placeCharacter(new _01201(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01201 $action */
        $action = $ravenna->getActions()[0];
        return [$ravenna, $action];
    }

    private function trigger(TestWorld $world, Action_01201 $action, int $playerId = 1): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction Sorcerer ability' => function () {
                $action = new Action_01201();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
            },

            'available when controlled Sorcerer is in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [$ravenna, $action] = $this->armed($world);
                $ravenna->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable when not controlled' => function () {
                $world = new TestWorld();
                [$ravenna, $action] = $this->armed($world);
                $ravenna->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled');
            },

            // WHY (journal 2026-04-08-14): Sorcerer City Action — losing Sorcerer / Fate's Silence turns it off.
            'unavailable when blanked or no longer a Sorcerer' => function () {
                $world = new TestWorld();
                [$ravenna, $action] = $this->armed($world);

                $ravenna->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
                $ravenna->removeCondition(Game::FATES_SILENCE_CONDITION);

                $ravenna->ModifiedTraits = ['Mercenary', 'Strega', 'Vodacce'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Sorcerer');
            },

            'unavailable for the opposing player' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'opponent');
            },

            // WHY (journal 2026-10-10-16): Ravenna brackets Start before the wound cost
            // (unlike Torsten) so cancel paths can still see Sorcerer Start.
            'trigger wounds self then draws inside Sorcerer bracketing' => function () {
                $world = new TestWorld();
                [$ravenna, $action] = $this->armed($world);

                $this->trigger($world, $action);

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::same([
                    EventSorcererAbilityStart::class,
                    EventCharacterBeingWounded::class,
                    EventCardDrawn::class,
                    EventActionResolved::class,
                    EventSorcererAbilityPlayed::class,
                ], $order, 'event order');

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($ravenna->Id, $wound->characterId, 'self wound');
                Assert::same(1, $wound->wounds, '1 wound');
                Assert::same($action->Id, $wound->abilityId, 'ability');

                $draw = $world->theah->queuedOfType(EventCardDrawn::class)[0];
                Assert::same(1, $draw->playerId, 'controller draws');
            },

            'trigger for another action id is ignored' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);

                $event = new EventActionTriggered();
                $event->actionId = 'someOtherAction';
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },
        ];
    }
}
