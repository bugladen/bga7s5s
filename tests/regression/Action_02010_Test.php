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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02010;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02010_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02010';
    }

    /**
     * Twist in hand; Sorcerer Strega + another of yours at Docks; optional wounds on FROM.
     *
     * @return array{0:_02010,1:Action_02010,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, int $fromWounds = 2, bool $stregaWounded = false): array
    {
        $risk = $world->placeCard(new _02010(), Game::LOCATION_HAND, 1);
        $strega = $world->placeCharacter(
            new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        if ($stregaWounded) {
            $strega->Wounds = 1;
        }
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $ally->Wounds = $fromWounds;
        /** @var Action_02010 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $strega, $ally];
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction Sorcerer ability that targets characters' => function () {
                $action = new Action_02010();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcerer');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY (journal 2026-03-16-08): availability needs Strega + ≥2 yours at location + a wounded
            // character so the effect can move wounds.
            'available with Strega, ally, and a wounded character at location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when nobody at the location is wounded' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'healthy');
            },

            'unavailable with only one character at the location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02010(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $strega->Wounds = 1;
                /** @var Action_02010 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'unavailable without Sorcerer Strega' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _02010(), Game::LOCATION_HAND, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Wounds = 1;
                unset($b);
                /** @var Action_02010 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no strega');
            },

            'performers are Stregas at locations with ≥2 yours and a wounded character' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world);
                $lonely = $world->placeCharacter(
                    new GenericCharacter('Lonely', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $lonely->Wounds = 2;

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($strega->Id, $ids, true), 'strega');
                Assert::false(in_array($lonely->Id, $ids, true), 'lonely');
                Assert::false(in_array($ally->Id, $ids, true), 'ally not performer');
            },

            'trigger queues transition 02010' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('02010', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'name');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'source');
            },

            'args step 1 lists wounded characters at performer location' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world, 2, true);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02010,
                    'highDramaPlayerTurn_02010'
                );

                Assert::true(in_array($ally->Id, $args['ids'], true), 'ally');
                Assert::true(in_array($strega->Id, $args['ids'], true), 'strega wounded');
            },

            'isValidTargetForAbility rejects unwounded and opposing characters' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->Wounds = 2;

                [$okAlly,] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($okAlly, 'unwounded');
                [$okFoe,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($okFoe, 'opposing');
            },

            'act step 1 stores FROM in CHOSEN_CARD' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02010,
                    'highDramaPlayerTurn_02010',
                    $ally->Id
                );

                Assert::same($ally->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'from');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            // WHY: FROM wounded → TO other yours at same location (cannot pick FROM again).
            'args step 2 lists other yours at location excluding FROM' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02010_2,
                    'highDramaPlayerTurn_02010_2'
                );

                Assert::same([$strega->Id], $args['ids'], 'strega only');
                Assert::false(in_array($ally->Id, $args['ids'], true), 'from excluded');
            },

            'act step 2 rejects moving wounds to the same character' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02010_2,
                        'highDramaPlayerTurn_02010_2',
                        $ally->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            'act step 2 stores TO in CHOSEN_TARGET' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02010_2,
                    'highDramaPlayerTurn_02010_2',
                    $strega->Id
                );

                Assert::same($strega->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'to');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named');
            },

            // WHY: move up to 2 wounds — heal FROM, wound TO, then sorcerer played + resolved.
            'act step 3 moves up to 2 wounds from FROM to TO' => function () {
                $world = new TestWorld();
                [$risk, $action, $strega, $ally] = $this->scene($world, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $strega->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02010_3,
                    'highDramaPlayerTurn_02010_3',
                    2
                );

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                $heal = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                Assert::count(1, $heal, 'heal');
                Assert::same($ally->Id, $heal[0]->characterId, 'from');
                Assert::same(2, $heal[0]->wounds, 'two');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wound, 'wound');
                Assert::same($strega->Id, $wound[0]->characterId, 'to');
                Assert::same(2, $wound[0]->wounds, 'two');
                Assert::same($risk->Id, $wound[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['woundsChosen'], $world->game->gamestate->transitions, 'named');
            },

            'act step 3 rejects moving more wounds than FROM has' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $strega->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02010_3,
                        'highDramaPlayerTurn_02010_3',
                        2
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            'act step 3 rejects wound counts other than 1 or 2' => function () {
                $world = new TestWorld();
                [, $action, $strega, $ally] = $this->scene($world, 3);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $ally->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $strega->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02010_3,
                        'highDramaPlayerTurn_02010_3',
                        3
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
