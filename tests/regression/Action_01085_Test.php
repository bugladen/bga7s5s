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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01085_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01085';
    }

    /**
     * Player 1: Sorcerer (performer) at Docks, Ally at Forum. Player 2: Foe at Docks.
     *
     * @return array{0:_01085,1:Action_01085,2:Character,3:Character}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01085(), Game::LOCATION_HAND, 1);
        $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
        $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

        /** @var Action_01085 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $sorcerer, $ally];
    }

    private function act(TestWorld $world, Action_01085 $action, Character $performer, int $id): void
    {
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01085, 'x', $id);
    }

    public function tests(): array
    {
        return [
            // WHY: pre-commit hook requires Start/Played events for ISorcererAbility; Action_01085 queues
            // Start per repeat and Played on "done".
            'is a repeatable Sorcerer ability that targets characters on a RiskAction' => function () {
                $action = new Action_01085();
                Assert::instanceOf(ISorcererAbility::class, $action, 'ISorcererAbility');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets characters');
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer selected');
            },

            'available with a Sorcerer and one of your characters elsewhere' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Sorcerer' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                // WHY: an EMPTY ModifiedTraits is reset to Traits by Card::hasTrait (old-game hack).
                $sorcerer->ModifiedTraits = ['Scoundrel'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Sorcerer');
            },

            'unavailable when all your characters share the Sorcerer location' => function () {
                $world = new TestWorld();
                [, $action, , $ally] = $this->scene($world);
                $ally->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody to move');
            },

            'available when the other character is at Player Home' => function () {
                $world = new TestWorld();
                [, $action, , $ally] = $this->scene($world);
                $ally->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'Home counts as elsewhere');
            },

            'unavailable when the Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'performers are the player\'s Sorcerers only' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Their Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);

                $ids = array_values(array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)));
                Assert::same([$sorcerer->Id], $ids, 'performers');
            },

            'trigger queues transition 01085 from the Risk' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01085', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'internal id');
            },

            'getArgs lists only your characters away from the performer' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer, $ally] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Docks Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01085, 'x');
                Assert::same($sorcerer->Id, $args['performerId'], 'performer');
                Assert::same([$ally->Id], $args['charactersIds'], 'only the far ally');
            },

            'isValidTargetForAbility accepts own character at another location' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer, $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
                Assert::true($action->isValidTargetForAbility($world->game, $ally)[0], 'ally');
            },

            'isValidTargetForAbility rejects performer, opponents and same-location characters' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $pal = $world->placeCharacter(new GenericCharacter('Docks Pal'), Game::LOCATION_CITY_DOCKS, 1);
                $foeFar = $world->placeCharacter(new GenericCharacter('Far Foe'), Game::LOCATION_CITY_FORUM, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);

                Assert::false($action->isValidTargetForAbility($world->game, $sorcerer)[0], 'performer');
                Assert::false($action->isValidTargetForAbility($world->game, $pal)[0], 'same location');
                Assert::false($action->isValidTargetForAbility($world->game, $foeFar)[0], 'opponent');
            },

            // WHY: Repeatable — each pick wounds the performer and moves one target; ActionResolved /
            // SorcererAbilityPlayed wait for the "done" (id 0) so repeats stay inside one Action.
            'picking a target queues wound, ability start, move (no engage) and a repeat transition' => function () {
                $world = new TestWorld();
                [$risk, $action, $sorcerer, $ally] = $this->scene($world);

                $this->act($world, $action, $sorcerer, $ally->Id);

                $queued = $world->theah->queuedEvents;
                Assert::count(4, $queued, 'wound, start, move, transition');

                Assert::instanceOf(EventCharacterBeingWounded::class, $queued[0], 'wound first');
                Assert::same($sorcerer->Id, $queued[0]->characterId, 'performer wounded');
                Assert::same(1, $queued[0]->wounds, 'one wound');
                Assert::same($risk->Id, $queued[0]->sourceId, 'source');
                Assert::same($action->Id, $queued[0]->abilityId, 'ability');

                Assert::instanceOf(EventSorcererAbilityStart::class, $queued[1], 'start second');
                Assert::same($ally->Id, $queued[1]->targetId, 'start target');
                Assert::same($sorcerer->Id, $queued[1]->performerId, 'start performer');

                Assert::instanceOf(EventCardMoving::class, $queued[2], 'move third');
                Assert::same($ally->Id, $queued[2]->cardId, 'target moves');
                Assert::same(Game::LOCATION_CITY_FORUM, $queued[2]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_DOCKS, $queued[2]->toLocation, 'to performer');
                Assert::false($queued[2]->engage, 'no engage');

                Assert::instanceOf(EventTransition::class, $queued[3], 'transition last');
                Assert::same('01085', $queued[3]->transition, 'repeat the state');
                Assert::same($action->Id, $queued[3]->internalId, 'internal id');

                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'not played yet');
                Assert::same($ally->Id, $action->LastTargetId, 'remembers target');
                Assert::same(Game::LOCATION_CITY_FORUM, $action->LastTargetLocation, 'remembers origin');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },

            'done (id 0) queues ActionResolved and SorcererAbilityPlayed with the last target' => function () {
                $world = new TestWorld();
                [$risk, $action, $sorcerer, $ally] = $this->scene($world);
                $this->act($world, $action, $sorcerer, $ally->Id);
                $world->theah->takeQueuedEvents();

                $this->act($world, $action, $sorcerer, 0);

                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'resolved');
                Assert::same(1, $resolved[0]->playerId, 'controller');

                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::count(1, $played, 'played');
                Assert::same($risk->Id, $played[0]->sourceId, 'source');
                Assert::same($action->Id, $played[0]->abilityId, 'ability');
                Assert::same($sorcerer->Id, $played[0]->performerId, 'performer');
                Assert::same($ally->Id, $played[0]->targetId, 'last target');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no extra wound on done');
            },

            'picking an invalid target is rejected without queuing anything' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $pal = $world->placeCharacter(new GenericCharacter('Docks Pal'), Game::LOCATION_CITY_DOCKS, 1);

                $threw = false;
                try {
                    $this->act($world, $action, $sorcerer, $pal->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'picking an unknown character is rejected' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);

                $threw = false;
                try {
                    $this->act($world, $action, $sorcerer, 987654);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not found');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
