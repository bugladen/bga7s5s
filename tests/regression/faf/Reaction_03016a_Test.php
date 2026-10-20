<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03016a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03016a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03016a';
    }

    /**
     * @return array{0:_03016,1:Reaction_03016a}
     */
    private function scene(TestWorld $world): array
    {
        $ise = $world->placeCharacter(new _03016(), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->setGameStateValue(Game::TURN_PHASE, Game::DUSK);
        /** @var Reaction_03016a $reaction */
        $reaction = $ise->getReactions()[0];
        return [$ise, $reaction];
    }

    private function movingHome(TestWorld $world, _03016 $ise, int $sourceId = 0): EventCardMoving
    {
        $event = new EventCardMoving();
        $event->theah = $world->theah;
        $event->cardId = $ise->Id;
        $event->initiatingPlayerId = 1;
        $event->fromLocation = Game::LOCATION_CITY_DOCKS;
        $event->toLocation = Game::LOCATION_PLAYER_HOME;
        $event->sourceId = $sourceId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03016a(), 'card reaction');
            },

            // WHY: Only the Dusk cleanup move (sourceId 0) to Home. An ability move
            // carries a source and must not offer. Staying spends. Declining requeues
            // the move with her id so the same event does not prompt again.
            'cancels the dusk move home and can keep her in the city' => function () {
                $world = new TestWorld();
                [$ise, $reaction] = $this->scene($world);
                $event = $this->movingHome($world, $ise);
                $reaction->handleEvent($event);
                Assert::true($event->canceled, 'held');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same($ise->Id, $transition->sourceId, 'ise');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['stay', 'decline'], $buttons, 'buttons');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'stay');
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'not requeued');
            },

            'declining sends the move again, marked so she does not re-prompt' => function () {
                $world = new TestWorld();
                [$ise, $reaction] = $this->scene($world);
                $event = $this->movingHome($world, $ise);
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                $again = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::false($again->canceled, 'the copy is live');
                Assert::same([$ise->Id], $again->cancelDeclinedByCardIds, 'self marker');
                Assert::false($reaction->Used, 'decline does not spend');

                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($again);
                Assert::count(0, $world->theah->queuedEvents, 'marker suppresses the second offer');
            },

            'an ability move, or a move outside dusk, is left alone' => function () {
                $world = new TestWorld();
                [$ise, $reaction] = $this->scene($world);
                $ability = $this->movingHome($world, $ise, $ise->Id);
                $reaction->handleEvent($ability);
                Assert::false($ability->canceled, 'ability move');
                Assert::count(0, $world->theah->queuedEvents, 'no offer');

                $world->game->setGameStateValue(Game::TURN_PHASE, 0);
                $dusk = $this->movingHome($world, $ise);
                $reaction->handleEvent($dusk);
                Assert::false($dusk->canceled, 'not dusk');
            },
        ];
    }
}
