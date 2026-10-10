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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01149;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01149;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

final class TestCityAttachment_01149 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'City Attachment';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }
}

final class TestCityEvent_01149 extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'City Event';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }
}

class Action_01149_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01149';
    }

    /** @return array{0:_01149,1:Action_01149,2:GenericCharacter} */
    private function scene(TestWorld $world, string $performerAt = Game::LOCATION_CITY_DOCKS): array
    {
        $scheme = $world->placeCard(new _01149(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Sailor'), $performerAt, 1);
        /** @var Action_01149 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'available with performer at Docks and no uncontrolled city cards' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY: CityEventCard implements ICityDeckCard, so event counts on both tallies —
            // cityCards == eventCards → not (city > event) → available ("only events").
            'available when Docks has only Event city cards' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $event = $world->placeCard(new TestCityEvent_01149(), Game::LOCATION_CITY_DOCKS, 0);
                $event->ControllerId = 0;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'events only');
            },

            'unavailable when Docks has an uncontrolled non-Event city card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $att = $world->placeCard(new TestCityAttachment_01149(), Game::LOCATION_CITY_DOCKS, 0);
                $att->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'city card');
            },

            'unavailable when performer is not at Docks' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, Game::LOCATION_CITY_FORUM);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not docks');
            },

            'performers are only controller\'s characters at Docks' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $forum = $world->placeCharacter(new GenericCharacter('Forum'), Game::LOCATION_CITY_FORUM, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], $ids, 'docks only');
                Assert::false(in_array($forum->Id, $ids, true), 'forum excluded');
            },

            'trigger queues transition 01149' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01149', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'args include scheme and performer' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01149, 'x');

                Assert::same($scheme->Id, $args['schemeId'], 'scheme');
                Assert::same($performer->Id, $args['performerId'], 'performer');
            },

            'moving to a city location queues move (no engage) and ActionResolved' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01149,
                    'x',
                    [Game::LOCATION_CITY_FORUM]
                );

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($performer->Id, $moves[0]->cardId, 'performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'forum');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($scheme->Id, $moves[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'named');
            },

            'refuses a non-city location name' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01149,
                        'x',
                        [Game::LOCATION_PLAYER_HOME]
                    );
                } catch (UserException | \Exception $e) {
                    $threw = true;
                }
                Assert::true($threw, 'home refused');
            },
        ];
    }
}
