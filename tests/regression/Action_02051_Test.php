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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02051;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02051_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02051';
    }

    /**
     * @return array{0:_02051,1:Action_02051,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02051(), Game::LOCATION_HAND, 1);
        $sorcerer = $world->placeCharacter(new GenericCharacter('Galdr', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
        $engaged = $world->placeCharacter(new GenericCharacter('Bruiser'), Game::LOCATION_CITY_DOCKS, 2);
        $engaged->Engaged = true;
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
        /** @var Action_02051 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $sorcerer, $engaged];
    }

    public function tests(): array
    {
        return [
            'is a sorcerer Risk City Action that must pick a performer' => function () {
                $action = new Action_02051();
                Assert::instanceOf(RiskCityAction::class, $action, 'city action');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcery');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
                Assert::same(402051, States::HIGH_DRAMA_PLAYER_TURN_02051, 'state');
            },

            'available when an unengaged Sorcerer shares a city location with an engaged character' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer, $engaged] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one performer');
                Assert::same($sorcerer->Id, $performers[0]->Id, 'sorcerer');

                $engaged->Engaged = false;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody engaged');
                $engaged->Engaged = true;

                $sorcerer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'sorcerer engaged');
                $sorcerer->Engaged = false;

                $sorcerer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home is not the city');
            },

            // WHY: The availability scan uses getCharactersAtLocation, which omits uncontrolled
            // characters. isValidTarget does not repeat that filter.
            // WHY: The availability scan uses getCharactersAtLocation, which omits uncontrolled
            // characters. isValidTarget does not repeat that filter.
            'an uncontrolled engaged neighbor does not open the action, but is a legal click' => function () {
                $world = new TestWorld();
                [, $action, , $engaged] = $this->scene($world);
                $engaged->Location = Game::LOCATION_CITY_FORUM;
                $crowd = $world->placeCharacter(new GenericCharacter('Crowd'), Game::LOCATION_CITY_DOCKS, 2);
                $crowd->Engaged = true;
                $crowd->ControllerId = 0;

                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled neighbor is invisible');
                [$valid] = $action->isValidTargetForAbility($world->game, $crowd);
                Assert::true($valid, 'click still accepts them');
            },

            'not available outside the hand unless the hand check is overridden' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'played');
                Assert::true($action->isAvailableToPlayer(1, $world->theah, true), 'override');
            },

            'trigger opens the en-garde chooser' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02051', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            'args list every other engaged character at the performer location' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer, $engaged] = $this->scene($world);
                $friend = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $friend->Engaged = true;
                $ready = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_DOCKS, 2);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 2);
                $elsewhere->Engaged = true;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_02051, 'highDramaPhase02051');
                Assert::same($sorcerer->Id, $args['performerId'], 'performer');
                Assert::same([$engaged->Id, $friend->Id], $args['ids'], 'engaged neighbors');
                Assert::false(in_array($ready->Id, $args['ids'], true), 'ready excluded');
                Assert::false(in_array($sorcerer->Id, $args['ids'], true), 'performer excluded');
            },

            'rejects the performer, a different location, and a ready character' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer, $engaged] = $this->scene($world);
                [$ok] = $action->isValidTargetForAbility($world->game, $engaged);
                Assert::true($ok, 'engaged neighbor');

                [$self, $selfMessage] = $action->isValidTargetForAbility($world->game, $sorcerer);
                Assert::false($self, 'self');
                Assert::contains('performer', $selfMessage, 'self message');

                $engaged->Location = Game::LOCATION_CITY_FORUM;
                [$away] = $action->isValidTargetForAbility($world->game, $engaged);
                Assert::false($away, 'location');

                $engaged->Location = Game::LOCATION_CITY_DOCKS;
                $engaged->Engaged = false;
                [$ready] = $action->isValidTargetForAbility($world->game, $engaged);
                Assert::false($ready, 'not engaged');
            },

            'confirm engages the performer, en gardes the target, and sinks the risk' => function () {
                $world = new TestWorld();
                [$risk, $action, $sorcerer, $engaged] = $this->scene($world);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_02051, 'highDramaPhase02051', $engaged->Id);

                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class);
                $engage = $world->theah->queuedOfType(EventCardEngaged::class);
                $engarde = $world->theah->queuedOfType(EventCardEngarded::class);
                $locker = $world->theah->queuedOfType(EventCardSentToLocker::class);
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $start, 'start');
                Assert::same($sorcerer->Id, $start[0]->performerId, 'performer');
                Assert::same($action->Id, $start[0]->abilityId, 'ability');
                Assert::same($sorcerer->Id, $engage[0]->cardId, 'engage performer');
                Assert::same($risk->Id, $engage[0]->sourceId, 'engage source');
                Assert::same($engaged->Id, $engarde[0]->cardId, 'en garde');
                Assert::same($engaged->ControllerId, $engarde[0]->playerId, 'target controller');
                Assert::same($risk->Id, $locker[0]->cardId, 'locker');
                Assert::same($engaged->Id, $played[0]->targetId, 'played target');
                Assert::same($engaged->Location, $played[0]->targetLocation, 'played location');
                Assert::same($risk->ControllerId, $resolved[0]->playerId, 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'sole empty transition');
            },

            'confirm rejects a missing or illegal target' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_02051, 'highDramaPhase02051', 999);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_02051, 'highDramaPhase02051', $sorcerer->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'performer');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
