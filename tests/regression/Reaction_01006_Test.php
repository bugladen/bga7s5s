<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01006;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterLostBrute;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01006_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01006';
    }

    public function tests(): array
    {
        return [
            'dusk end offers reaction when controlled Brute shares Don location' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);
                $brute = $world->placeCharacter(
                    new GenericCharacter('Merc Brute', ['Mercenary', 'Brute']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );

                /** @var Reaction_01006 $reaction */
                $reaction = $don->getReactions()[0];

                $event = new EventDuskPhaseEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction transition queued');
                Assert::same($don->Id, $transitions[0]->sourceId, 'source is Don');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'internal id is reaction');
            },

            'dusk end does not offer reaction with no Brutes' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(
                    new GenericCharacter('Non-Brute', ['Diplomat']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );

                /** @var Reaction_01006 $reaction */
                $reaction = $don->getReactions()[0];
                $event = new EventDuskPhaseEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no reaction when no Brutes');
            },

            'buttons only list own Brutes at Don location excluding Don' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);
                $ownBrute = $world->placeCharacter(
                    new GenericCharacter('Own Brute', ['Brute']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Enemy Brute', ['Brute']),
                    Game::LOCATION_PLAYER_HOME,
                    2
                );

                /** @var Reaction_01006 $reaction */
                $reaction = $don->getReactions()[0];
                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $ids = array_column($buttons, 'reaction');

                Assert::true(in_array('loseBrute-' . $ownBrute->Id, $ids, true), 'own Brute offered');
                Assert::false(
                    in_array('loseBrute-' . $don->Id, $ids, true),
                    'Don himself not offered'
                );
                Assert::true(in_array('pass', $ids, true), 'pass offered');

                foreach ($ids as $id) {
                    if (str_starts_with($id, 'loseBrute-')) {
                        Assert::same('loseBrute-' . $ownBrute->Id, $id, 'only own Brute button');
                    }
                }
            },

            'performReaction removes Brute and queues lost-Brute event' => function () {
                $world = new TestWorld();
                $don = $world->placeCharacter(new _01006(), Game::LOCATION_PLAYER_HOME, 1);
                $brute = $world->placeCharacter(
                    new GenericCharacter('Own Brute', ['Mercenary', 'Brute']),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );

                /** @var Reaction_01006 $reaction */
                $reaction = $don->getReactions()[0];
                Assert::true($brute->hasTrait('Brute'), 'starts with Brute');

                $reaction->performReaction(
                    $world->game,
                    0,
                    $reaction->Id,
                    'loseBrute-' . $brute->Id
                );

                Assert::false($brute->hasTrait('Brute'), 'Brute removed');
                Assert::true($reaction->Used, 'reaction marked used');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterLostBrute::class), 'lost Brute event');
                Assert::same(['done'], $world->game->gamestate->transitions, 'nextState done');
            },
        ];
    }
}
