<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01120;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01120;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Card_01120_Test extends TestCase
{
    public function name(): string
    {
        return '_01120 Pavel Ivanov';
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->initiatingPlayerId = 1;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->theah = $world->theah;
        return $event;
    }

    /** @return list<EventCharacterInfluenceModified> */
    private function influence(TestWorld $world): array
    {
        return $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Academic with Reaction and Technique_01120' => function () {
                $pavel = new _01120();
                Assert::instanceOf(Character::class, $pavel, 'Character');
                Assert::instanceOf(IHasReactions::class, $pavel, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $pavel, 'techniques');
                Assert::same(3, $pavel->Resolve, 'Resolve');
                Assert::same(0, $pavel->Combat, 'Combat');
                Assert::same(2, $pavel->Finesse, 'Finesse');
                Assert::same(3, $pavel->Influence, 'Influence');
                Assert::true($pavel->hasFaction('Ussura'), 'Ussura');
                Assert::true($pavel->hasTrait('Academic'), 'Academic');
                Assert::instanceOf(Reaction_01120::class, $pavel->getReactions()[0], 'Reaction_01120');
                Assert::instanceOf(Technique_PlusOneParry::class, $pavel->getTechniques()[0], 'PlusOneParry');
                Assert::same('Technique_01120', $pavel->getTechniques()[0]->Id, 'technique id');
            },

            // WHY: +1 Influence only while at a city location you control — Home never qualifies.
            'moving onto a controlled city location grants +1 Influence' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($pavel, $this->moved(
                    $world,
                    $pavel->Id,
                    Game::LOCATION_PLAYER_HOME,
                    Game::LOCATION_CITY_DOCKS
                ));

                $mods = $this->influence($world);
                Assert::count(1, $mods, 'gained');
                Assert::same(3, $mods[0]->OldInfluence, 'old');
                Assert::same(4, $mods[0]->NewInfluence, 'new');
            },

            'moving onto an uncontrolled location grants nothing' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_PLAYER_HOME, 1);

                $world->fireOn($pavel, $this->moved(
                    $world,
                    $pavel->Id,
                    Game::LOCATION_PLAYER_HOME,
                    Game::LOCATION_CITY_DOCKS
                ));

                Assert::count(0, $this->influence($world), 'uncontrolled');
            },

            'leaving a controlled location removes the Influence bonus' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($pavel, $this->moved(
                    $world,
                    $pavel->Id,
                    Game::LOCATION_PLAYER_HOME,
                    Game::LOCATION_CITY_DOCKS
                ));
                $world->theah->takeQueuedEvents();
                $pavel->ModifiedInfluence = 4;

                $world->fireOn($pavel, $this->moved(
                    $world,
                    $pavel->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_PLAYER_HOME
                ));

                $mods = $this->influence($world);
                Assert::count(1, $mods, 'lost');
                Assert::same(3, $mods[0]->NewInfluence, 'back to printed');
            },

            'claiming Pavel\'s location grants +1 Influence' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventLocationClaimed();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($pavel, $event);

                Assert::count(1, $this->influence($world), 'gained on claim');
                Assert::same(4, $this->influence($world)[0]->NewInfluence, '+1');
            },

            'opponent claiming Pavel\'s location removes the bonus' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);
                $claim = new EventLocationClaimed();
                $claim->playerId = 1;
                $claim->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($pavel, $claim);
                $world->theah->takeQueuedEvents();
                $pavel->ModifiedInfluence = 4;

                $event = new EventLocationClaimed();
                $event->playerId = 2;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($pavel, $event);

                Assert::same(3, $this->influence($world)[0]->NewInfluence, 'lost');
            },

            'location becoming uncontrolled removes the bonus' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);
                $claim = new EventLocationClaimed();
                $claim->playerId = 1;
                $claim->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($pavel, $claim);
                $world->theah->takeQueuedEvents();
                $pavel->ModifiedInfluence = 4;

                $event = new EventLocationBecomesUncontrolled();
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($pavel, $event);

                Assert::same(3, $this->influence($world)[0]->NewInfluence, 'lost');
            },

            // WHY: Technique_PlusOneParry with id Technique_01120 — cover on the card suite.
            'Technique_01120 adds +1 Parry while in a duel' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Technique_PlusOneParry $technique */
                $technique = $pavel->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, '+1 Parry');
                Assert::count(1, $event->explanations, 'explained');
            },

            'Technique_01120 unavailable outside a duel' => function () {
                $world = new TestWorld();
                $pavel = $world->placeCharacter(new _01120(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_PlusOneParry $technique */
                $technique = $pavel->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },
        ];
    }
}
