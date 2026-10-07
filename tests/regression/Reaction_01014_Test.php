<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Thomas;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01014_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01014';
    }

    public function tests(): array
    {
        return [
            'wound from opposing IAbilityThatTargetsCharacters offers in-play Thug redirect' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $vittoria->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->wounds = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'held wound canceled');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'reaction offered');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('putIntoPlay-' . $thug->Id, $ids, true), 'in-play Thug button');
                Assert::true(in_array('decline', $ids, true), 'decline');
            },

            // WHY: Challenges skip shouldReactToEvent / IAbilityThatTargetsCharacters
            // (Premonition / Technique overwrite of abilityId — journal 2026-09-05-07)
            'challenge against Vittoria offers reaction without ability interface' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $vittoria->Id;
                $event->sourceId = $challenger->Id;
                $event->playerId = 2;
                $event->abilityId = 'not-an-ability';
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'challenge held');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'reaction offered');
            },

            'hand Thug path musters then opens in-play redirect' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $handThug = $world->placeCharacter(
                    new GenericCharacter('Hand Thug', ['Thug']),
                    Game::LOCATION_HAND,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $vittoria->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'putIntoPlay-' . $handThug->Id);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterMustered::class), 'muster queued');
                // After hand pick, inPlayThug becomes true and another reaction transition queues
                Assert::true(
                    count($world->theah->queuedOfType(EventTransition::class)) >= 1,
                    'follow-up transition'
                );
            },

            'redirecting in-play Thug offers moveHome when wound reassigned' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $thug = $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $vittoria->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'putIntoPlay-' . $thug->Id);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound re-queued');
                Assert::same($thug->Id, $wounds[0]->characterId, 'Thug is new target');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('moveHome', $ids, true), 'moveHome offered after successful redirect');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveHome');
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::same($vittoria->Id, $moves[0]->cardId, 'Vittoria moves');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
            },

            // WHY regression: audit 2026-03-31 — moveHome only if Thug was actually targeted
            'source gates moveHome on thugWasTargeted' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/_7s5s/reactions/Reaction_01014.php');
                Assert::contains('$thugWasTargeted', $src, 'thugWasTargeted flag');
                Assert::contains('if ($thugWasTargeted)', $src, 'moveHome gated');
                Assert::contains('deleteEventBatch', $src, 'cancelHeldEvent clears batch');
            },

            // WHY: Bruno cannot enter play from hand except during a duel — outside
            // duel he must not count for thugsInHand or appear as a putIntoPlay button.
            'hand Bruno alone does not open hand-Thug path outside duel' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $bruno = $world->placeCard(new _05Thomas(), Game::LOCATION_HAND, 1);
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $vittoria->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                // No in-play Thug and Bruno ineligible → reaction should not offer
                // (nothing to redirect to).
                Assert::false($event->canceled, 'no legal Thug — do not hold');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no reaction');
            },

            'hand Bruno is eligible during duel' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $bruno = $world->placeCard(new _05Thomas(), Game::LOCATION_HAND, 1);
                $world->game->globals->set(Game::IN_DUEL, true);
                $sibella = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 2);
                $sibellaAction = $sibella->getActions()[0];

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $vittoria->Id;
                $event->sourceId = $sibella->Id;
                $event->abilityId = $sibellaAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'held — Bruno legal in duel');
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('putIntoPlay-' . $bruno->Id, $ids, true), 'Bruno button in duel');
            },

            'does not react when own ability wounds Vittoria' => function () {
                $world = new TestWorld();
                $vittoria = $world->placeCharacter(new _01014(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Thug']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $ally = $world->placeCharacter(new _01012(), Game::LOCATION_CITY_DOCKS, 1);
                $allyAction = $ally->getActions()[0];

                /** @var Reaction_01014 $reaction */
                $reaction = $vittoria->getReactions()[0];

                $event = new EventCharacterBeingWounded();
                $event->characterId = $vittoria->Id;
                $event->sourceId = $ally->Id;
                $event->abilityId = $allyAction->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($event->canceled, 'own source ignored');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no reaction');
            },
        ];
    }
}
