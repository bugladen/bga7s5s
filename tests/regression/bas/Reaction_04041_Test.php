<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04041_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04041';
    }

    public function tests(): array
    {
        return [
            // WHY: Opponent effect = a source controlled by someone else, never 0.
            // The character must currently be at Jak-Sen's city location. Decline
            // requeues the move with his id in cancelDeclinedByCardIds. Cancel spends.
            'cancels an opponent move of your character at his location' => function () {
                $world = new TestWorld();
                $jak = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $source = $world->placeCard(new _04041(), Game::LOCATION_HAND, 2);
                /** @var Reaction_04041 $reaction */
                $reaction = $jak->getReactions()[0];

                $move = new EventCardMoving();
                $move->cardId = $ally->Id;
                $move->fromLocation = Game::LOCATION_CITY_DOCKS;
                $move->toLocation = Game::LOCATION_CITY_FORUM;
                $move->sourceId = $source->Id;
                $move->theah = $world->theah;
                $reaction->handleEvent($move);
                Assert::true($move->canceled, 'intercepted');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'decline');
                Assert::false($reaction->Used, 'decline does not spend');
                $requeued = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::true(in_array($jak->Id, $requeued->cancelDeclinedByCardIds, true), 'will not re-catch');

                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($requeued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'declined move');

                $again = new EventCardMoving();
                $again->cardId = $ally->Id;
                $again->fromLocation = Game::LOCATION_CITY_DOCKS;
                $again->toLocation = Game::LOCATION_CITY_FORUM;
                $again->sourceId = $source->Id;
                $again->theah = $world->theah;
                $reaction->handleEvent($again);
                $reaction->performReaction($world->game, 0, '', 'cancel');
                Assert::true($reaction->Used, 'cancel spends');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'move stays canceled');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $again->canceled = false;
                $again->sourceId = 0;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'framework source');

                $own = $world->placeCard(new _04041(), Game::LOCATION_HAND, 1);
                $again->sourceId = $own->Id;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'your own card');

                $again->sourceId = $source->Id;
                $again->unstoppable = true;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'unstoppable');

                $again->unstoppable = false;
                $jak->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'not in the city');

                $jak->Location = Game::LOCATION_CITY_DOCKS;
                $again->fromLocation = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'left his location already');
            },

            // WHY: Engaged has no cancelDeclinedByCardIds. Decline sets skipNextEngagedEvent
            // so the re-queued engage is not caught again.
            'cancels an opponent engage and skips the requeue' => function () {
                $world = new TestWorld();
                $jak = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $source = $world->placeCard(new _04041(), Game::LOCATION_CITY_FORUM, 2);
                /** @var Reaction_04041 $reaction */
                $reaction = $jak->getReactions()[0];

                $engaged = new EventCardEngaged();
                $engaged->cardId = $ally->Id;
                $engaged->sourceId = $source->Id;
                $engaged->theah = $world->theah;
                $reaction->handleEvent($engaged);
                Assert::true($engaged->canceled, 'intercepted');

                $reaction->performReaction($world->game, 0, '', 'decline');
                Assert::false($reaction->Used, 'decline');
                $requeued = $world->theah->queuedOfType(EventCardEngaged::class)[0];
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($requeued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'skip once');

                $engaged->canceled = false;
                $ally->Location = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($engaged);
                Assert::false($engaged->canceled, 'different location');
            },
        ];
    }
}
