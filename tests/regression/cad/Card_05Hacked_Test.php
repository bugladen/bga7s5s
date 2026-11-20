<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Hacked;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardWhenRevealedEffect;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Hacked_Test extends TestCase
{
    public function name(): string
    {
        return '_05Hacked Stolen Schematics';
    }

    public function tests(): array
    {
        return [
            'constructs Discovery Stealth scheme with When Revealed' => function () {
                $scheme = new _05Hacked();
                Assert::same('Stolen Schematics', $scheme->Name, 'name');
                Assert::same(0, $scheme->Initiative, 'initiative');
                Assert::same(0, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasTrait('Discovery'), 'Discovery');
                Assert::true($scheme->hasTrait('Stealth'), 'Stealth');
                Assert::true($scheme->hasWhenRevealedEffect(), 'when revealed');
                Assert::true(str_contains($scheme->Text, 'Academic'), 'Academic text');
            },

            // WHY: Academic + opponent Home scheme → pick transition on When Revealed.
            'When Revealed with Academic queues scheme-copy pick' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericScheme('Enemy Plan', 10), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventCardWhenRevealedEffect();
                $event->playerId = 1;
                $event->cardId = $hacked->Id;
                $world->fireOn($hacked, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('05Hacked', $transitions[0]->transition, 'copy pick key');
            },

            'When Revealed without Academic does not queue copy pick' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCard(new GenericScheme('Enemy Plan', 10), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventCardWhenRevealedEffect();
                $event->playerId = 1;
                $event->cardId = $hacked->Id;
                $world->fireOn($hacked, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no copy');
            },

            'When Revealed Academic with no targets does not queue copy pick' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCardWhenRevealedEffect();
                $event->playerId = 1;
                $event->cardId = $hacked->Id;
                $world->fireOn($hacked, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'nothing to copy');
            },

            // WHY: Locker schemes are eligible even though locker is not in $theah->cards.
            'eligible ids include opponent locker schemes' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $locker = $world->placeCard(
                    new GenericScheme('Sunk Plan', 20),
                    $world->game->getPlayerLockerName(2),
                    2
                );

                $ids = $hacked->getEligibleOpponentSchemeIds($world->theah, 1);
                Assert::true(in_array($locker->Id, $ids, true), 'locker scheme');
            },

            // WHY: Gary same-Id swap so resolve runs as the source class.
            'becomeCopyOf swaps to source scheme class with copy condition' => function () {
                $world = new TestWorld();
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $source = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 2);

                $copy = $hacked->becomeCopyOf($source, $world->theah);
                Assert::instanceOf(_01071::class, $copy, 'epee class');
                Assert::same($hacked->Id, $copy->Id, 'same id');
                Assert::same(1, $copy->ControllerId, 'controller');
                Assert::same($source->Initiative, $copy->Initiative, 'initiative from source');
                Assert::same($source->PanacheModifier, $copy->PanacheModifier, 'panache from source');
                Assert::true($copy->hasCondition(Game::STOLEN_SCHEMATICS_COPY), 'marker');
                Assert::instanceOf(_01071::class, $world->theah->getCardById($hacked->Id), 'world updated');
            },

            // WHY: Shell is 0/0; copy must take Épée's printed 26 / -1 and adjust Leader panache.
            'becomeCopyOf applies source PanacheModifier delta to Leader' => function () {
                $world = new TestWorld();
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $leader->ModifiedPanache = $leader->Panache; // shell revealed with +0
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $source = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 2);
                Assert::same(-1, $source->PanacheModifier, 'epee panache');

                $hacked->becomeCopyOf($source, $world->theah);

                Assert::same($leader->Panache - 1, $leader->ModifiedPanache, 'leader panache');
            },

            // WHY: Non-Academic resolve offers Renown; Academic path already spent When Revealed.
            'Resolve without Academic queues Renown location pick' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $event->scheme = $hacked;
                $world->fireOn($hacked, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'renown pick');
                Assert::same('05Hacked_2', $transitions[0]->transition, 'renown key');
            },

            'Resolve with Academic does not queue Renown pick' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventResolveScheme();
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $event->scheme = $hacked;
                $world->fireOn($hacked, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no renown');
            },

            // WHY: EventHub reverts after locker move so next-day pick is still Stolen Schematics.
            'CardSentToLocker hub reverts stolen copy to 05Hacked' => function () {
                $world = new TestWorld();
                /** @var _05Hacked $hacked */
                $hacked = $world->placeCard(new _05Hacked(), Game::LOCATION_PLAYER_HOME, 1);
                $source = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 2);
                $copy = $hacked->becomeCopyOf($source, $world->theah);
                Assert::instanceOf(_01071::class, $copy, 'pre-locker copy');

                $lockerEvent = EventFactory::createCardSentToLockerEvent(1, $copy->Id);
                $world->applyHub($lockerEvent);

                $restored = $world->theah->getCardById($copy->Id);
                Assert::instanceOf(_05Hacked::class, $restored, 'reverted');
                Assert::false($restored->hasCondition(Game::STOLEN_SCHEMATICS_COPY), 'marker cleared');
                Assert::same($world->game->getPlayerLockerName(1), $restored->Location, 'in locker');
            },

            'state constants encode exp 5 card 24' => function () {
                Assert::same(2405024, States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_05HACKED, 'when revealed');
                Assert::same(2605024, States::PLANNING_PHASE_RESOLVE_SCHEMES_05HACKED, 'resolve renown');
            },
        ];
    }
}
