<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01036;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01036_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01036';
    }

    public function tests(): array
    {
        return [
            'available only in duel' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01036 $technique */
                $technique = $daniella->getTechniques()[0];

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');
            },

            // WHY: Harpoon/Shackles checked at activate so player sees failure before locking location
            'eventCheck refuses Harpooned activation in duel' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                $daniella->addCondition(Game::HARPOON_CONDITION);
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Technique_01036 $technique */
                $technique = $daniella->getTechniques()[0];

                $event = new EventTechniqueActivated();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $technique->eventCheck($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'harpooned');
            },

            'eventCheck refuses Shackled activation' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                $daniella->addCondition(Game::SHACKLES_CONDITION);
                /** @var Technique_01036 $technique */
                $technique = $daniella->getTechniques()[0];

                $event = new EventTechniqueActivated();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $technique->eventCheck($event);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'shackled');
            },

            'resolve queues transition 01036' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01036 $technique */
                $technique = $daniella->getTechniques()[0];

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same('01036', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY: move deferred to EndOfRound so adversary pool threat commits while co-located
            'choosing location arms EndOfRound move' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01036 $technique */
                $technique = $daniella->getTechniques()[0];

                $technique->actFromTechniqueWithIds(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01036,
                    'duelChooseTechnique_01036',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moveFlag = new \ReflectionProperty(Technique_01036::class, 'MoveDaniela');
                $moveFlag->setAccessible(true);
                $moveLoc = new \ReflectionProperty(Technique_01036::class, 'MoveLocation');
                $moveLoc->setAccessible(true);
                Assert::true($moveFlag->getValue($technique), 'armed');
                Assert::same(Game::LOCATION_CITY_FORUM, $moveLoc->getValue($technique), 'location');

                $eor = new EventDuelEndOfRound();
                $eor->theah = $world->theah;
                $technique->handleEvent($eor);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to forum');
                Assert::false($moveFlag->getValue($technique), 'cleared');
            },

            'duel end clears pending move without moving' => function () {
                $world = new TestWorld();
                $daniella = $world->placeCharacter(new _01036(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_01036 $technique */
                $technique = $daniella->getTechniques()[0];
                $moveFlag = new \ReflectionProperty(Technique_01036::class, 'MoveDaniela');
                $moveFlag->setAccessible(true);
                $moveLoc = new \ReflectionProperty(Technique_01036::class, 'MoveLocation');
                $moveLoc->setAccessible(true);
                $moveFlag->setValue($technique, true);
                $moveLoc->setValue($technique, Game::LOCATION_CITY_FORUM);

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($moveFlag->getValue($technique), 'cleared');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}
