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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01068;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01068_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01068';
    }

    /** @return array{0:_01068,1:Action_01068} */
    private function armed(TestWorld $world): array
    {
        $leontine = $world->placeCharacter(new _01068(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01068 $action */
        $action = $leontine->getActions()[0];
        return [$leontine, $action];
    }

    private function trigger(TestWorld $world, Action_01068 $action, int $playerId = 1): void
    {
        $event = new EventActionTriggered();
        $event->actionId = $action->Id;
        $event->playerId = $playerId;
        $event->theah = $world->theah;
        $action->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'available in the city (she can target herself)' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable at Home' => function () {
                $world = new TestWorld();
                [$leontine, $action] = $this->armed($world);
                $leontine->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in city');
            },

            'unavailable for the opposing player' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'opponent');
            },

            // WHY: Sorcerer City Action - losing the Sorcerer trait (or Fate's Silence) turns the ability off.
            'unavailable when blanked or no longer a Sorcerer' => function () {
                $world = new TestWorld();
                [$leontine, $action] = $this->armed($world);

                $leontine->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
                $leontine->removeCondition(Game::FATES_SILENCE_CONDITION);

                // hasTrait reads ModifiedTraits (printed Traits are only the reset source).
                $leontine->ModifiedTraits = ['Duelist', 'Musketeer'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Sorcerer');
            },

            'trigger queues transition 01068' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);

                $this->trigger($world, $action);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01068', $transitions[0]->transition, 'name');
            },

            'trigger throws when Leontine is not in the city' => function () {
                $world = new TestWorld();
                [$leontine, $action] = $this->armed($world);
                $leontine->Location = Game::LOCATION_PLAYER_HOME;

                $threw = false;
                try {
                    $this->trigger($world, $action);
                } catch (\Throwable $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in city');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'step 1 args list only own characters at her location' => function () {
                $world = new TestWorld();
                [$leontine, $action] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01068,
                    'highDramaPlayerTurn_01068'
                );

                $ids = $args['characterIds'];
                sort($ids);
                $expected = [$leontine->Id, $ally->Id];
                sort($expected);
                Assert::same($expected, $ids, 'Leontine and ally');
            },

            'step 2 args list every city location except hers' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01068_2,
                    'highDramaPlayerTurn_01068_2'
                );

                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'own location excluded');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'Forum');
                Assert::true(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'Bazaar (any location, not just adjacent)');
            },

            'target choice stores the character and goes to characterChosen' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01068,
                    'highDramaPlayerTurn_01068',
                    $ally->Id
                );

                Assert::same($ally->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'target choice rejects opposing and other-location characters' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);

                foreach ([$foe, $far] as $bad) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId(
                            $world->game,
                            States::HIGH_DRAMA_PLAYER_TURN_01068,
                            'highDramaPlayerTurn_01068',
                            $bad->Id
                        );
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, $bad->Name);
                }
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            'target choice rejects an unknown character id' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01068,
                        'highDramaPlayerTurn_01068',
                        999999
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not found');
            },

            // WHY: Sorcerer abilities must bracket effects with Start/Played; wound-self is the cost, then the move.
            'location choice wounds Leontine then moves the target inside Sorcerer bracketing' => function () {
                $world = new TestWorld();
                [$leontine, $action] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01068_2,
                    'highDramaPlayerTurn_01068_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                $order = array_map(fn($e) => $e::class, $world->theah->queuedEvents);
                Assert::same([
                    EventSorcererAbilityStart::class,
                    EventCharacterBeingWounded::class,
                    EventCardMoving::class,
                    EventSorcererAbilityPlayed::class,
                    EventActionResolved::class,
                ], $order, 'event order');

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($leontine->Id, $wound->characterId, 'Leontine wounded');
                Assert::same(1, $wound->wounds, '1 wound');

                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($ally->Id, $move->cardId, 'ally moves');
                Assert::same(Game::LOCATION_CITY_DOCKS, $move->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'to');
                Assert::false($move->engage, 'no engage');
                Assert::same($leontine->Id, $move->sourceId, 'source');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'Leontine may move herself and still takes the wound' => function () {
                $world = new TestWorld();
                [$leontine, $action] = $this->armed($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $leontine->Id);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01068_2,
                    'highDramaPlayerTurn_01068_2',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same($leontine->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::same($leontine->Id, $world->theah->queuedOfType(EventCardMoving::class)[0]->cardId, 'self move');
            },

            'location choice rejects her own location without queuing anything' => function () {
                $world = new TestWorld();
                [, $action] = $this->armed($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);

                $threw = false;
                try {
                    $action->actFromActionWithIds(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01068_2,
                        'highDramaPlayerTurn_01068_2',
                        [Game::LOCATION_CITY_DOCKS]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }

                Assert::true($threw, 'same location');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
