<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02031;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;

class Card_02031_Test extends TestCase
{
    public function name(): string
    {
        return '_02031 Lucas Martinez “Doomed”';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Villain Pirate' => function () {
                $lucas = new _02031();
                Assert::instanceOf(Character::class, $lucas, 'Character');
                Assert::same(3, $lucas->Resolve, 'Resolve');
                Assert::same(2, $lucas->Combat, 'Combat');
                Assert::same(1, $lucas->Finesse, 'Finesse');
                Assert::same(3, $lucas->Influence, 'Influence');
                Assert::true($lucas->hasFaction('Castille'), 'Castille');
                Assert::true($lucas->hasTrait('Villain'), 'Villain');
                Assert::true($lucas->hasTrait('Pirate'), 'Pirate');
                Assert::true($lucas->hasTrait('Castille'), 'Castille trait');
            },

            // WHY: Muster creates Damned in the controller locker and queues SentToLocker.
            'muster sends Damned to The Locker' => function () {
                $world = new TestWorld();
                $doomed = $world->placeCharacter(new _02031(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterMustered();
                $event->characterId = $doomed->Id;
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($doomed, $event);

                $created = $world->game->createdCardsInLocation;
                Assert::count(1, $created, 'one Damned');
                Assert::instanceOf(_02032::class, $created[0], '02032');
                Assert::same('Locker-1', $created[0]->Location, 'locker');
                Assert::same(1, $created[0]->ControllerId, 'controller');

                $sent = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $sent, 'sent');
                Assert::same($created[0]->Id, $sent[0]->cardId, 'card');
                Assert::same(1, $sent[0]->playerId, 'player');
            },

            'Approach play also sends Damned to The Locker' => function () {
                $world = new TestWorld();
                $doomed = $world->placeCharacter(new _02031(), Game::LOCATION_CITY_FORUM, 2);

                $event = new EventApproachCharacterPlayed();
                $event->characterId = $doomed->Id;
                $event->playerId = 2;
                $world->fireOn($doomed, $event);

                Assert::count(1, $world->game->createdCardsInLocation, 'created');
                Assert::same('Locker-2', $world->game->createdCardsInLocation[0]->Location, 'locker 2');
                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'sent');
            },

            'ignores another character mustering' => function () {
                $world = new TestWorld();
                $doomed = $world->placeCharacter(new _02031(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterMustered();
                $event->characterId = 999;
                $world->fireOn($doomed, $event);

                Assert::count(0, $world->game->createdCardsInLocation, 'none');
                Assert::count(0, $world->theah->queuedEvents, 'no events');
            },

            // WHY: Destroy musters Damned from the locker back to Doomed's location.
            'destroy musters Damned from the locker at that location' => function () {
                $world = new TestWorld();
                $doomed = $world->placeCharacter(new _02031(), Game::LOCATION_CITY_FORUM, 1);
                $damned = $world->placeCharacter(new _02032(), 'Locker-1', 1);

                $event = new EventCharacterDestroyed();
                $event->characterId = $doomed->Id;
                $world->fireOn($doomed, $event);

                $removed = $world->theah->queuedOfType(EventCardRemovedFromLocker::class);
                Assert::count(1, $removed, 'removed');
                Assert::same($damned->Id, $removed[0]->cardId, 'damned');

                $mustered = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::count(1, $mustered, 'muster');
                Assert::same($damned->Id, $mustered[0]->characterId, 'who');
                Assert::same(Game::LOCATION_CITY_FORUM, $mustered[0]->location, 'where');
                Assert::same(1, $mustered[0]->playerId, 'player');
            },

            'destroy does nothing when Damned is not in the locker' => function () {
                $world = new TestWorld();
                $doomed = $world->placeCharacter(new _02031(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new _02032(), Game::LOCATION_HAND, 1);

                $event = new EventCharacterDestroyed();
                $event->characterId = $doomed->Id;
                $world->fireOn($doomed, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'ignores another character being destroyed' => function () {
                $world = new TestWorld();
                $doomed = $world->placeCharacter(new _02031(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new _02032(), 'Locker-1', 1);

                $event = new EventCharacterDestroyed();
                $event->characterId = 999;
                $world->fireOn($doomed, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
