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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01158;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01159;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01158;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01158_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01158';
    }

    /**
     * Jacket on host; optional hand card for discard→draw.
     *
     * @return array{0:_01158,1:Action_01158,2:GenericCharacter,3:?\Bga\Games\SeventhSeaCityOfFiveSails\cards\Card}
     */
    private function scene(TestWorld $world, bool $withHand = true): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $jacket = $world->placeCard(new _01158(), Game::LOCATION_CITY_DOCKS, 1);
        $jacket->AttachedToId = $host->Id;
        $host->Attachments[] = $jacket->Id;
        $hand = null;
        if ($withHand) {
            $hand = $world->placeCard(new _01159(), Game::LOCATION_HAND, 1);
        }
        /** @var Action_01158 $action */
        $action = $jacket->getActions()[0];
        return [$jacket, $action, $host, $hand];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction' => function () {
                Assert::instanceOf(AttachmentAction::class, new Action_01158(), 'AttachmentAction');
            },

            'available when attached and the controller has a hand card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with empty hand' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no hand');
            },

            'unavailable when not attached' => function () {
                $world = new TestWorld();
                $jacket = $world->placeCard(new _01158(), Game::LOCATION_HAND, 1);
                $world->placeCard(new _01159(), Game::LOCATION_HAND, 1);
                /** @var Action_01158 $action */
                $action = $jacket->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unattached');
            },

            'trigger queues transition 01158' => function () {
                $world = new TestWorld();
                [$jacket, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01158', $transitions[0]->transition, 'name');
                Assert::same($jacket->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'action');
            },

            'choosing a hand card queues discard, draw, and ActionResolved' => function () {
                $world = new TestWorld();
                [$jacket, $action, , $hand] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01158,
                    'highDramaPlayerTurn_01158',
                    $hand->Id
                );

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($hand->Id, $discards[0]->cardId, 'hand');
                Assert::same($jacket->Id, $discards[0]->sourceId, 'source');
                $draws = $world->theah->queuedOfType(EventCardDrawn::class);
                Assert::count(1, $draws, 'draw');
                Assert::same(1, $draws[0]->playerId, 'drawer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'named');
            },

            'refuses a card not in hand' => function () {
                $world = new TestWorld();
                [, $action, , $hand] = $this->scene($world);
                $hand->Location = Game::LOCATION_PLAYER_HOME;
                $world->game->activePlayerId = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01158,
                        'highDramaPlayerTurn_01158',
                        $hand->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
            },

            'refuses an opponent\'s hand card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $opp = $world->placeCard(new _01159(), Game::LOCATION_HAND, 2);
                $world->game->activePlayerId = 1;

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01158,
                        'highDramaPlayerTurn_01158',
                        $opp->Id
                    );
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'opponent card');
            },

            'state constant registered' => function () {
                Assert::same(401158, States::HIGH_DRAMA_PLAYER_TURN_01158, '01158');
            },
        ];
    }
}
