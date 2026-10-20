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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01176;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01177;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01177_Test extends TestCase
{
    public function name(): string
    {
        return '_01177 Penya Shows The Way';
    }

    /** @return _01177 */
    private function placed(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): _01177
    {
        /** @var _01177 $penya */
        $penya = $world->placeCard(new _01177(), $location, 0);
        return $penya;
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Heroic Savvy CityEventCard with no composites' => function () {
                $penya = new _01177();
                Assert::instanceOf(CityEventCard::class, $penya, 'CityEvent');
                Assert::same(1, $penya->CityCardNumber, 'city #');
                Assert::true($penya->hasTrait('Heroic'), 'Heroic');
                Assert::true($penya->hasTrait('Savvy'), 'Savvy');
            },

            'state constants registered' => function () {
                Assert::same(8001177, States::DUSK_PHASE_BEGIN_01177, 'choose');
                Assert::same(80011772, States::DUSK_PHASE_BEGIN_01177_2, 'reorder');
            },

            // WHY (journal 2026-04-08-12): "you" = location controller; needs a controlled
            // character at the event's location before the dusk transition queues.
            'Dusk queues 01177 when location controller has a character here' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $char = $world->placeCharacter(new GenericCharacter('Follower'), Game::LOCATION_CITY_DOCKS, 1);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($penya, $dusk);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01177', $transitions[0]->transition, 'name');
                Assert::same(1, $transitions[0]->playerId, 'controller');
                Assert::same($penya->Id, $transitions[0]->sourceId, 'source');
                unset($char);
            },

            'Dusk does not trigger when the location is uncontrolled' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $world->placeCharacter(new GenericCharacter('Nobody'), Game::LOCATION_CITY_DOCKS, 1);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($penya, $dusk);

                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled');
            },

            'Dusk does not trigger when the controller has no character here' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($penya, $dusk);

                Assert::count(0, $world->theah->queuedEvents, 'no char here');
            },

            'Dusk does not trigger when Penya is not in the city' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world, Game::LOCATION_CITY_DISCARD);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Follower'), Game::LOCATION_CITY_DOCKS, 1);

                $dusk = new EventDuskPhaseBegin();
                $world->fireOn($penya, $dusk);

                Assert::count(0, $world->theah->queuedEvents, 'not in city');
            },

            'args list only the location controller\'s characters here' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $mine = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $args = $penya->argsFromCard(
                    $world->game,
                    States::DUSK_PHASE_BEGIN_01177,
                    'duskPhaseBegin_01177',
                    ''
                );

                Assert::same($penya->Id, $args['sourceId'], 'source');
                Assert::same([$mine->Id], $args['ids'], 'controller only');
                Assert::false(in_array($foe->Id, $args['ids'], true), 'foe excluded');
            },

            'choosing a character stamps HELPED_BY_PENYA and transitions pickCards' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $char = $world->placeCharacter(new GenericCharacter('Follower'), Game::LOCATION_CITY_DOCKS, 1);

                $penya->actFromCardWithId(
                    $world->game,
                    States::DUSK_PHASE_BEGIN_01177,
                    'duskPhaseBegin_01177',
                    '',
                    $char->Id
                );

                Assert::true($char->hasCondition(Game::HELPED_BY_PENYA), 'helped');
                Assert::same(['pickCards'], $world->game->gamestate->transitions, 'pickCards');
            },

            // WHY (journal 2026-04-08-12): blocks EventCardMoved (not EventCardMoving) so the
            // Moving hub still runs, then queueEvent refuses Moved and the physical move never lands.
            // Condition is cleared before the throw so it is not sticky across days.
            'eventCheck blocks EventCardMoved for a helped character and clears the condition' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $char = $world->placeCharacter(new GenericCharacter('Follower'), Game::LOCATION_CITY_DOCKS, 1);
                $char->Conditions[] = Game::HELPED_BY_PENYA;

                $threw = false;
                try {
                    $penya->eventCheck($this->moved(
                        $world,
                        $char->Id,
                        Game::LOCATION_CITY_DOCKS,
                        Game::LOCATION_PLAYER_HOME
                    ));
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
                Assert::false($char->hasCondition(Game::HELPED_BY_PENYA), 'cleared');
                Assert::true($char->IsUpdated, 'dirty');
            },

            'eventCheck does not block a character without HELPED_BY_PENYA' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $char = $world->placeCharacter(new GenericCharacter('Unhelped'), Game::LOCATION_CITY_DOCKS, 1);

                $penya->eventCheck($this->moved(
                    $world,
                    $char->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_PLAYER_HOME
                ));
                Assert::true(true, 'allowed');
            },

            'reordering top 3 records deck inserts for each chosen id' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $a = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $b = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $c = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [
                    ['id' => $a->Id],
                    ['id' => $b->Id],
                    ['id' => $c->Id],
                ];

                $penya->actFromCardWithIds(
                    $world->game,
                    States::DUSK_PHASE_BEGIN_01177_2,
                    'duskPhaseBegin_01177_2',
                    '',
                    [$b->Id, $a->Id, $c->Id]
                );

                Assert::same([
                    ['id' => $b->Id, 'deck' => Game::LOCATION_CITY_DECK, 'onTop' => true],
                    ['id' => $a->Id, 'deck' => Game::LOCATION_CITY_DECK, 'onTop' => true],
                    ['id' => $c->Id, 'deck' => Game::LOCATION_CITY_DECK, 'onTop' => true],
                ], $world->game->deckInserts, 'inserts');
                // WHY: act calls bare nextState() — FakeGamestate records null.
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'reordering refuses an id not in the top 3' => function () {
                $world = new TestWorld();
                $penya = $this->placed($world);
                $a = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $b = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $c = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $outsider = $world->placeCard(new _01176(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [
                    ['id' => $a->Id],
                    ['id' => $b->Id],
                    ['id' => $c->Id],
                ];

                $threw = false;
                try {
                    $penya->actFromCardWithIds(
                        $world->game,
                        States::DUSK_PHASE_BEGIN_01177_2,
                        'x',
                        '',
                        [$outsider->Id, $a->Id, $b->Id]
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
                Assert::count(0, $world->game->deckInserts, 'no inserts');
            },
        ];
    }
}
