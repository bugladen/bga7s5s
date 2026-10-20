<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02035_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02035';
    }

    /**
     * @return array{0:_02035,1:Action_02035,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _02035(), Game::LOCATION_PLAYER_HOME, 1);
        $scoundrel = $world->placeCharacter(
            new GenericCharacter('Scoundrel', ['Scoundrel']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_02035 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $scoundrel];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction that requires a performer' => function () {
                $action = new Action_02035();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // Opposed Scoundrel in the city, location has Renown, scheme at Home.
            'available to an opposed Scoundrel at a location with Renown' => function () {
                $world = new TestWorld();
                [, $action, $scoundrel] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one');
                Assert::same($scoundrel->Id, $performers[0]->Id, 'scoundrel');
            },

            'unavailable without opposition, Renown, the Scoundrel trait, or the scheme at Home' => function () {
                $world = new TestWorld();
                [$scheme, $action, $scoundrel] = $this->scene($world);
                $foe = null;
                foreach ($world->theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS) as $character) {
                    if ($character->Id !== $scoundrel->Id) {
                        $foe = $character;
                    }
                }
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unopposed');

                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no renown');

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $scoundrel->ModifiedTraits = ['Hero'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not scoundrel');

                $scoundrel->ModifiedTraits = ['Scoundrel'];
                $scoundrel->DashedFinesse = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot finesse pressure');

                $scoundrel->DashedFinesse = false;
                $scheme->Location = Game::LOCATION_HAND;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not home');
            },

            // Sets the Caper pressure flag, Finesse stat, then pressureLocation.
            'trigger starts Finesse pressure flagged as Castillian Caper' => function () {
                $world = new TestWorld();
                [$scheme, $action, $scoundrel] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $scoundrel->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::PRESSURE_STAT), 'finesse');
                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CASTILLIAN_CAPER_PRESSURE_TYPE),
                    'caper flag'
                );

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::same($scoundrel->Id, $pressure[0]->performerId, 'performer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $pressure[0]->location, 'docks');
                Assert::true(in_array(Game::STAT_FINESSE, $pressure[0]->pressureTypes, true), 'stat');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('pressureLocation', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'successful pressure collects one Renown and resolves the action' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = true;
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $removed, 'removed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $removed[0]->location, 'docks');
                Assert::same(1, $removed[0]->amount, 'one');
                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'gain');
                Assert::same(1, $gains[0]->playerId, 'player');
                Assert::same(1, $gains[0]->amount, 'amount');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'failed pressure resolves the action without collecting Renown' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = false;
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventRenownRemovedFromLocation::class), 'no remove');
                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no gain');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'success with no Renown left does not collect, but still resolves' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = true;
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no gain');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'ignores a pressure result from another ability' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = 'other';
                $event->success = true;
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
