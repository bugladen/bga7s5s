<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01072;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** City-deck stand-in so Action_01072 can discard "a City Card" at the Leader's location. */
final class TestCityCard_01072 extends CityAttachment
{
    public function __construct(bool $discardable = true)
    {
        parent::__construct();
        $this->Name = 'Test City Card';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Discardable = $discardable;
        $this->resetCard();
    }

    public bool $Discardable = true;

    public function canBeDiscardedFromCity(): bool
    {
        return $this->Discardable;
    }
}

class Action_01072_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01072';
    }

    /** @return array{0:_01072,1:GenericLeader} scheme, leader */
    private function arm(TestWorld $world, string $leaderLocation = Game::LOCATION_CITY_DOCKS): array
    {
        $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
        /** @var GenericLeader $leader */
        $leader = $world->placeCharacter(new GenericLeader('Leader'), $leaderLocation, 1);
        $world->theah->leadersByPlayerId[1] = $leader;
        return [$scheme, $leader];
    }

    public function tests(): array
    {
        return [
            'available with a Leader in the city who can pressure with Influence' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Leader is at Home' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world, Game::LOCATION_PLAYER_HOME);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'leader home');
            },

            'unavailable when only a non-Leader is in the city' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01072(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no leader');
            },

            'unavailable when Leader has dashed Influence' => function () {
                $world = new TestWorld();
                [$scheme, $leader] = $this->arm($world);
                $leader->DashedInfluence = true;
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'cannot pressure');
            },

            'getPerformersForAction returns only Leaders' => function () {
                $world = new TestWorld();
                [$scheme, $leader] = $this->arm($world);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($leader->Id, $ids, true), 'leader');
                Assert::false(in_array($crew->Id, $ids, true), 'crew');
            },

            'trigger engages Leader + scheme and starts REPUTATION_MERITEE Influence pressure' => function () {
                $world = new TestWorld();
                [$scheme, $leader] = $this->arm($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);
                // Stale pressure flag from another effect must not leak into this pressure.
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::PACK_TACTICS_PRESSURE_TYPE);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($leader->Id, $engages[0]->cardId, 'leader engaged (cost)');
                Assert::same(
                    Game::REPUTATION_MERITEE_PRESSURE_TYPE,
                    $world->game->globals->get(Game::PRESSURE_TYPE),
                    'only REPUTATION_MERITEE flag (stale flag reset)'
                );
                Assert::true($world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE), 'flag');
                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring player');
                Assert::same($leader->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::true(in_array(Game::STAT_INFLUENCE, $pressure[0]->pressureTypes, true), 'Influence pressure');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'pressure success queues transition 01072 (discard + muster)' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::same('01072', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
            },

            'pressure failure resolves the action with no discard/muster' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no transition');
            },

            'pressure result for another ability is ignored' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $result = new EventLocationPressureResult();
                $result->abilityId = 'someOtherAbility';
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // --- step 1: choose City Card to discard ---

            'args step 1 lists uncontrolled discardable City Cards at the Leader location' => function () {
                $world = new TestWorld();
                [$scheme, $leader] = $this->arm($world);
                $good = $world->placeCard(new TestCityCard_01072(), Game::LOCATION_CITY_DOCKS, 0);
                $stuck = $world->placeCard(new TestCityCard_01072(false), Game::LOCATION_CITY_DOCKS, 0);
                $elsewhere = $world->placeCard(new TestCityCard_01072(), Game::LOCATION_CITY_FORUM, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x');

                Assert::same([$good->Id], $args['targetCardIds'], 'only discardable local card');
                Assert::same($scheme->Id, $args['schemeId'], 'scheme id');
            },

            'step 1 choosing a City Card at the Leader location records CHOSEN_CARD' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $card = $world->placeCard(new TestCityCard_01072(), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x', $card->Id);

                Assert::same($card->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'step 1 refuses City Card at a different location' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $card = $world->placeCard(new TestCityCard_01072(), Game::LOCATION_CITY_FORUM, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x', $card->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'wrong location');
            },

            'step 1 refuses a non-City card' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x', $crew->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not a city card');
            },

            'step 1 refuses an undiscardable City Card' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $card = $world->placeCard(new TestCityCard_01072(false), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x', $card->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'cannot discard');
            },

            'step 1 id 0 allowed only when no discardable City Card is available' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x', 0);
                Assert::same(0, $world->game->globals->get(Game::CHOSEN_CARD), 'skip');

                $world->placeCard(new TestCityCard_01072(), Game::LOCATION_CITY_DOCKS, 0);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072, 'x', 0);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'cannot skip when one is available');
            },

            // --- step 2: muster from Approach ---

            'step 2 discards chosen City Card, musters Approach character at Leader location, resolves' => function () {
                $world = new TestWorld();
                [$scheme, $leader] = $this->arm($world);
                $card = $world->placeCard(new TestCityCard_01072(), Game::LOCATION_CITY_DOCKS, 0);
                $approach = $world->placeCharacter(new GenericCharacter('Approach Char'), Game::LOCATION_APPROACH, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $card->Id);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072_2, 'x', $approach->Id);

                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'city discard');
                Assert::same($card->Id, $discards[0]->cardId, 'discarded city card');
                $musters = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::count(1, $musters, 'muster');
                Assert::same($approach->Id, $musters[0]->characterId, 'mustered');
                Assert::same($leader->Location, $musters[0]->location, 'at the Leader location');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'step 2 with no City Card chosen only musters' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $approach = $world->placeCharacter(new GenericCharacter('Approach Char'), Game::LOCATION_APPROACH, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072_2, 'x', $approach->Id);

                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'no discard');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterMustered::class), 'muster');
            },

            'step 2 refuses id 0 while an Approach Character is available' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $world->placeCharacter(new GenericCharacter('Approach Char'), Game::LOCATION_APPROACH, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072_2, 'x', 0);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must muster');
            },

            'step 2 id 0 with empty Approach deck just resolves' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $world->game->globals->set(Game::CHOSEN_CARD, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072_2, 'x', 0);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterMustered::class), 'no muster');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'step 2 refuses a card id that is not in the Approach deck' => function () {
                $world = new TestWorld();
                [$scheme] = $this->arm($world);
                $notApproach = $world->placeCharacter(new GenericCharacter('Docks'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, 0);
                /** @var Action_01072 $action */
                $action = $scheme->getActions()[0];

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01072_2, 'x', $notApproach->Id);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not approach');
            },

            // --- Pressure rules live in Game::pressureLocation (UtilitiesTrait; not loadable under FakeGame) ---

            // WHY: FakeGame has no UtilitiesTrait, so the "non-Mercenaries only" filter and
            // "ties succeed" rule for REPUTATION_MERITEE_PRESSURE_TYPE are source-locked.
            'pressure resolution excludes Mercenaries and wins ties for REPUTATION_MERITEE' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/UtilitiesTrait.php');
                Assert::contains(
                    'isGlobalFlagSet(Game::PRESSURE_TYPE, Game::REPUTATION_MERITEE_PRESSURE_TYPE))',
                    $src,
                    'merc filter gated on flag'
                );
                Assert::contains('! $character->hasTrait("Mercenary")', $src, 'Mercenaries not counted');
                $tieBlock = strstr($src, '//Ties win');
                Assert::true($tieBlock !== false, 'tie-win branch present');
                $conditionStart = strrpos(substr($src, 0, (int)strpos($src, '//Ties win')), 'TABARD_PRESSURE_TYPE');
                Assert::true($conditionStart !== false, 'tie-win list begins with TABARD');
                $listRegion = substr($src, (int)$conditionStart, (int)strpos($src, '//Ties win') - (int)$conditionStart);
                Assert::contains('REPUTATION_MERITEE_PRESSURE_TYPE', $listRegion, 'REPUTATION_MERITEE in tie-win list');
            },
        ];
    }
}
