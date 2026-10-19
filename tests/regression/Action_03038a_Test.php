<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03038a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03038a_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03038a';
    }

    /**
     * @return array{0:_03038,1:Action_03038a}
     */
    private function scene(TestWorld $world): array
    {
        $damya = $world->placeCharacter(new _03038(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_03038a $action */
        $action = $damya->getActions()[0];
        return [$damya, $action];
    }

    public function tests(): array
    {
        return [
            // WHY: An empty hand is legal when the deck or discard can still supply the
            // draw that will then be discarded. Home is not the city.
            'draws, then discards a hand card as an effect' => function () {
                $world = new TestWorld();
                [$damya, $action] = $this->scene($world);
                $hand = $world->placeCard(new _03036(), Game::LOCATION_HAND, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'hand can be discarded');

                $hand->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing to draw or discard');
                $world->game->deckLocationCounts['Deck-1'] = 1;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'deck can supply the draw');
                $hand->Location = Game::LOCATION_HAND;

                $damya->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in the city');
                $damya->Location = Game::LOCATION_CITY_DOCKS;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same(1, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'draw');
                Assert::same('03038a', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'discard step');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038a, 'x');
                Assert::same([$hand->Id], $args['ids'], 'hand');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038a, 'x', $hand->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::true($discard->asEffect, 'effect');
                Assert::false($discard->AsPayment, 'not payment');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardDiscarded'], array_slice($world->game->gamestate->transitions, -1), 'discarded');
            },

            'a card that is not in hand throws before the discard' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $elsewhere = $world->placeCard(new _03036(), Game::LOCATION_CITY_DOCKS, 1);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038a, 'x', $elsewhere->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'no discard');
            },
        ];
    }
}
