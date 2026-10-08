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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01092;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01092;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01092_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01092';
    }

    /**
     * Makepeace (P1, Influence 2) at Docks vs an engaged opposing character with Influence 2.
     *
     * @return array{0:_01092,1:Action_01092,2:Character}
     */
    private function scene(TestWorld $world): array
    {
        $makepeace = $world->placeCharacter(new _01092(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Engaged = true;
        $foe->ModifiedInfluence = 2;
        /** @var Action_01092 $action */
        $action = $makepeace->getActions()[0];
        return [$makepeace, $action, $foe];
    }

    private function blocked(callable $fn): bool
    {
        try {
            $fn();
        } catch (UserException $e) {
            return true;
        }
        return false;
    }

    public function tests(): array
    {
        return [
            'targets characters' => function () {
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, new Action_01092(), 'IAbilityThatTargetsCharacters');
            },

            'available with an engaged opposing character of equal Influence' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'equal Influence');
            },

            'available with an engaged opposing character of lower Influence' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->ModifiedInfluence = 0;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'lower Influence');
            },

            'unavailable when the opposing character has more Influence' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->ModifiedInfluence = 3;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'higher Influence');
            },

            // WHY: the comparison reads ModifiedInfluence on both sides, so Influence buffs on Makepeace widen the target set.
            'Makepeace\'s modified Influence is what is compared' => function () {
                $world = new TestWorld();
                [$makepeace, $action, $foe] = $this->scene($world);
                $foe->ModifiedInfluence = 3;
                $makepeace->ModifiedInfluence = 3;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'buffed Makepeace');
            },

            'unavailable when the opposing character is en garde' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not engaged');
            },

            'unavailable when the only engaged character is her own' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Engaged = false;
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $ally->Engaged = true;
                $ally->ModifiedInfluence = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'own character');
            },

            'unavailable when the engaged opposing character is elsewhere' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'other location');
            },

            'unavailable when Makepeace is at Home' => function () {
                $world = new TestWorld();
                [$makepeace, $action, $foe] = $this->scene($world);
                $makepeace->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable to the opponent' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'not controller');
            },

            'unavailable when Fate\'s Silence blanks Makepeace' => function () {
                $world = new TestWorld();
                [$makepeace, $action] = $this->scene($world);
                $makepeace->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            'trigger queues transition 01092' => function () {
                $world = new TestWorld();
                [$makepeace, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01092', $transitions[0]->transition, 'name');
                Assert::same($makepeace->Id, $transitions[0]->sourceId, 'source');
            },

            'args list only engaged opposing characters at her location within Influence' => function () {
                $world = new TestWorld();
                [$makepeace, $action, $foe] = $this->scene($world);
                $tooStrong = $world->placeCharacter(new GenericCharacter('Too Strong'), Game::LOCATION_CITY_DOCKS, 2);
                $tooStrong->Engaged = true;
                $tooStrong->ModifiedInfluence = 5;
                $enGarde = $world->placeCharacter(new GenericCharacter('En Garde'), Game::LOCATION_CITY_DOCKS, 2);
                $enGarde->ModifiedInfluence = 1;
                $far = $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 2);
                $far->Engaged = true;
                $far->ModifiedInfluence = 0;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'highDramaPlayerTurn_01092');

                Assert::same($makepeace->Id, $args['performerId'], 'performer');
                Assert::same([$foe->Id], $args['ids'], 'only the valid target');
            },

            'validation accepts a valid target' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'valid');
            },

            'validation rejects her own character, other locations, en garde and stronger characters' => function () {
                $world = new TestWorld();
                [$makepeace, $action, $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $ally->Engaged = true;
                [$okAlly] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($okAlly, 'own character');

                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$okFar] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($okFar, 'other location');

                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $foe->Engaged = false;
                [$okEnGarde] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($okEnGarde, 'en garde');

                $foe->Engaged = true;
                $foe->ModifiedInfluence = $makepeace->ModifiedInfluence + 1;
                [$okStrong] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($okStrong, 'more Influence');
            },

            'act moves the target Home without engaging and resolves' => function () {
                $world = new TestWorld();
                [$makepeace, $action, $foe] = $this->scene($world);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', $foe->Id);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($foe->Id, $moves[0]->cardId, 'target moved');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::false($moves[0]->engage, 'ability move does not engage');
                Assert::same($makepeace->Id, $moves[0]->sourceId, 'source');
                Assert::same($action->Id, $moves[0]->abilityId, 'ability');
                Assert::same(1, $moves[0]->initiatingPlayerId, 'initiator is Makepeace\'s controller');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'nextState');
            },

            'act refuses an unknown character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($this->blocked(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', 999999)), 'unknown');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },

            'act refuses a stronger target' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->ModifiedInfluence = 9;
                Assert::true($this->blocked(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', $foe->Id)), 'stronger');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'act refuses her own character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $ally->Engaged = true;
                Assert::true($this->blocked(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', $ally->Id)), 'own');
            },

            'act refuses an en garde target' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Engaged = false;
                Assert::true($this->blocked(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', $foe->Id)), 'en garde');
            },

            'act refuses a target at another location' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::true($this->blocked(fn() => $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_01092, 'x', $foe->Id)), 'other location');
            },
        ];
    }
}
