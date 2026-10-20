<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01023;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01023_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01023';
    }

    public function tests(): array
    {
        return [
            // WHY regression: journal 2026-10-01 — any player's challenge offers Ambush
            'offers when opponent issues a challenge while Ambush in hand' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_HAND, 1);

                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];

                $event = new EventChallengeIssued();
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered on opponent challenge');
                Assert::same(1, $transitions[0]->playerId, 'Ambush controller chooses');
            },

            'does not offer when not in hand' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_PLAYER_HOME, 1);
                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];

                $event = new EventChallengeIssued();
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            // WHY regression: audit 2026-04-25 — discount uses ControllerId Brute at challenge loc
            'discount +1 when you control a Brute at challenge location' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('My Brute', ['Brute']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];
                $explanations = [];
                $discount = $reaction->getReactionFromHandDiscount($world->theah, $reaction, $explanations);
                Assert::same(1, $discount, 'Brute discount');
            },

            'no discount when only opponent controls Brute at location' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Foe Brute', ['Brute']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $world->game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);

                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];
                $explanations = [];
                Assert::same(0, $reaction->getReactionFromHandDiscount($world->theah, $reaction, $explanations), 'foe Brute');
            },

            'performReaction preventIntervention queues pay state' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'preventIntervention');

                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'EventRiskReactionTriggered sets PreventIntervention' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];

                $event = new EventRiskReactionTriggered();
                $event->internalId = $reaction->Id;
                $event->reactionId = 'preventIntervention';
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($reaction->PreventIntervention, 'flag set');
            },

            'eventCheck blocks intervention when PreventIntervention and Ambush in discard' => function () {
                $world = new TestWorld();
                $discard = $world->game->getPlayerDiscardDeckName(1);
                $ambush = $world->placeCard(new _01023(), $discard, 1);
                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];
                $reaction->PreventIntervention = true;

                $event = new EventCharacterIntervened();
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $reaction->eventCheck($event);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'intervention blocked');
            },

            'buttons include Prevent Intervention and Pass' => function () {
                $world = new TestWorld();
                $ambush = $world->placeCard(new _01023(), Game::LOCATION_HAND, 1);
                /** @var Reaction_01023 $reaction */
                $reaction = $ambush->getReactions()[0];
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('preventIntervention', $ids, true), 'prevent');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },
        ];
    }
}
