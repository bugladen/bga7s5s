<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Seadog;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Seadog;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_05Seadog_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_05Seadog';
    }

    public function tests(): array
    {
        return [
            // WHY: Covert cancel-move — uncontrolled city, any Character leaving the
            // location (including framework sourceId==0). Decline requeues with owner
            // in cancelDeclinedByCardIds. Cancel spends. Claimed location / Home /
            // unstoppable / already-left do not offer.
            'cancels movement from an uncontrolled location' => function () {
                $world = new TestWorld();
                $mikael = $world->placeCharacter(new _05Seadog(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Seadog $reaction */
                $reaction = $mikael->getReactions()[0];

                $move = new EventCardMoving();
                $move->cardId = $foe->Id;
                $move->fromLocation = Game::LOCATION_CITY_DOCKS;
                $move->toLocation = Game::LOCATION_CITY_FORUM;
                $move->sourceId = 0;
                $move->theah = $world->theah;
                $reaction->handleEvent($move);
                Assert::true($move->canceled, 'intercepted framework move');
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'decline');
                Assert::false($reaction->Used, 'decline does not spend');
                $requeued = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::true(in_array($mikael->Id, $requeued->cancelDeclinedByCardIds, true), 'will not re-catch');

                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($requeued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'declined move');

                $again = new EventCardMoving();
                $again->cardId = $foe->Id;
                $again->fromLocation = Game::LOCATION_CITY_DOCKS;
                $again->toLocation = Game::LOCATION_CITY_FORUM;
                $again->sourceId = 0;
                $again->theah = $world->theah;
                $reaction->handleEvent($again);
                $reaction->performReaction($world->game, 0, '', 'cancel');
                Assert::true($reaction->Used, 'cancel spends');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'move stays canceled');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $again->canceled = false;
                $again->unstoppable = true;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'unstoppable');

                $again->unstoppable = false;
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'claimed — not Covert');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $mikael->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'not in the city');

                $mikael->Location = Game::LOCATION_CITY_DOCKS;
                $again->fromLocation = Game::LOCATION_CITY_FORUM;
                $reaction->handleEvent($again);
                Assert::false($again->canceled, 'left his location already');
            },

            'cancels an ally leaving the same uncontrolled location' => function () {
                $world = new TestWorld();
                $mikael = $world->placeCharacter(new _05Seadog(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_05Seadog $reaction */
                $reaction = $mikael->getReactions()[0];

                $move = new EventCardMoving();
                $move->cardId = $ally->Id;
                $move->fromLocation = Game::LOCATION_CITY_DOCKS;
                $move->toLocation = Game::LOCATION_CITY_FORUM;
                $move->sourceId = $ally->Id;
                $move->theah = $world->theah;
                $reaction->handleEvent($move);
                Assert::true($move->canceled, 'ally also interceptable');
            },
        ];
    }
}
