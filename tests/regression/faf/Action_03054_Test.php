<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03054;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03054_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03054';
    }

    public function tests(): array
    {
        return [
            'is a scheme city action that needs a performer' => function () {
                $action = new Action_03054();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Unequipped, can pressure Resolve, and an opposing character must be there.
            // The trigger wounds and opens pressure; it clears CHOSEN_PERFORMER so a dying
            // performer does not make pressure read a locker location. Success with a foe
            // opens the wound-and-send-home pick and does not resolve yet.
            'wounds an unequipped performer and pressures with resolve' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03054(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_03054 $action */
                $action = $scheme->getActions()[0];

                $harpoon = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 1);
                $harpoon->AttachedToId = $performer->Id;
                $performer->Attachments[] = $harpoon->Id;
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'equipped');
                $performer->Attachments = [];
                Assert::same([$performer->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'unequipped');

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'no opposing');
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wounded');
                Assert::same(0, (int) $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer cleared');
                Assert::same($performer->Id, (int) $world->game->globals->get(Game::CHOSEN_CARD), 'stashed');
                Assert::same(Game::STAT_RESOLVE, $world->game->globals->get(Game::PRESSURE_STAT), 'resolve');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pressure');
                Assert::count(1, $world->theah->queuedOfType(EventPressureOccuring::class), 'occuring');

                $world->theah->queuedEvents = [];
                $result = new EventLocationPressureResult();
                $result->theah = $world->theah;
                $result->abilityId = $action->Id;
                $result->success = false;
                $result->location = Game::LOCATION_CITY_DOCKS;
                $action->handleEvent($result);
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'failure resolves');

                $world->theah->queuedEvents = [];
                $result->success = true;
                $action->handleEvent($result);
                Assert::same('03054', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'target');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not yet');

                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03054, 'x', $foe->Id);
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'foe wounded');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->toLocation, 'home');
                Assert::false($move->engage, 'no engage');
            },

            'a killing wound does not also move them home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03054(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Wounds = 2;
                $action = $scheme->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03054, 'x', $foe->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no home move');

                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03054, 'x', $ally->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'ally');
            },
        ];
    }
}
