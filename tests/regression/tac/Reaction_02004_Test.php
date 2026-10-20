<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02004;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02004_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02004';
    }

    /**
     * Scheme at Home; adjacent en garde performer at Forum (adjacent to Docks for 2p).
     *
     * @return array{0:_02004,1:Reaction_02004,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _02004(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_02004 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $performer];
    }

    private function pressure(TestWorld $world, int $playerId, string $location = Game::LOCATION_CITY_DOCKS): EventPressureOccuring
    {
        $event = new EventPressureOccuring();
        $event->location = $location;
        $event->playerId = $playerId;
        $event->performerId = 0;
        $event->pressureTypes = [Game::STAT_INFLUENCE];
        $event->theah = $world->theah;
        return $event;
    }

    private function locationProp(Reaction_02004 $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02004::class, 'location');
        $prop->setAccessible(true);
        return $prop;
    }

    private function result(TestWorld $world, string $location, bool $success): EventLocationPressureResult
    {
        $event = new EventLocationPressureResult();
        $event->location = $location;
        $event->success = $success;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02004(), 'CardReaction');
            },

            // WHY (2026-03-16-04): only when OPPONENT initiates pressure.
            'offers when opponent pressures and adjacent en garde performer exists' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->pressure($world, 2));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'scheme');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(
                    Game::LOCATION_CITY_DOCKS,
                    $this->locationProp($reaction)->getValue($reaction),
                    'location armed'
                );
            },

            'does not offer when owner initiates pressure' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->pressure($world, 1));
                Assert::count(0, $world->theah->queuedEvents, 'own pressure');
            },

            'does not offer without adjacent en garde performer' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $performer->Engaged = true;

                $reaction->handleEvent($this->pressure($world, 2));
                Assert::count(0, $world->theah->queuedEvents, 'engaged only');
            },

            'does not offer when performer is not adjacent' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $performer->Location = Game::LOCATION_CITY_BAZAAR;

                $reaction->handleEvent($this->pressure($world, 2, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedEvents, 'not adjacent');
            },

            'moveTo queues CardMoving to pressured location' => function () {
                $world = new TestWorld();
                [, $reaction, $performer] = $this->scene($world);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveTo-' . $performer->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'to');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY (2026-03-16-04 / 2026-10-03-06): Pass clears $location so declined
            // reaction does NOT draw on later pressure result.
            'pass clears armed location without drawing later' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::same('', $this->locationProp($reaction)->getValue($reaction), 'cleared');
                Assert::false($reaction->Used, 'not used');

                $reaction->handleEvent($this->result($world, Game::LOCATION_CITY_DOCKS, true));
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw after pass');
            },

            // WHY (2026-10-03-06): draw listens on EventLocationPressureResult (success),
            // NOT EventLocationPressured (Objection race).
            'successful pressure result draws when location still armed' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->handleEvent($this->result($world, Game::LOCATION_CITY_DOCKS, true));

                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'controller');
                Assert::same('', $this->locationProp($reaction)->getValue($reaction), 'cleared after');
                Assert::contains($scheme->getInjectCode(), $draws[0]->reason, 'reason');
            },

            'failed pressure result does not draw but clears location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $this->locationProp($reaction)->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->handleEvent($this->result($world, Game::LOCATION_CITY_DOCKS, false));

                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');
                Assert::same('', $this->locationProp($reaction)->getValue($reaction), 'cleared');
            },

            'source draws on LocationPressureResult not LocationPressured' => function () {
                $src = file_get_contents(dirname(__DIR__, 3) . '/modules/php/cards/tac/reactions/Reaction_02004.php');
                Assert::contains('EventLocationPressureResult', $src, 'Result');
                Assert::notContains('EventLocationPressured', $src, 'not Pressured');
                Assert::contains("\$this->location = '';", $src, 'pass clears');
            },
        ];
    }
}
