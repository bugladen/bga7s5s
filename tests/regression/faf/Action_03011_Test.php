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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03011;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03011_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03011';
    }

    /**
     * @return array{0:_03011,1:Action_03011,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03011(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Performer'), Game::LOCATION_CITY_DOCKS, 1);
        $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_PLAYER_HOME, 1);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_03011 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer, $thug];
    }

    public function tests(): array
    {
        return [
            'is a city action that must pick a performer' => function () {
                $action = new Action_03011();
                Assert::instanceOf(RiskCityAction::class, $action, 'city action');
                Assert::true($action->RequiresPerformerSelected, 'performer');
                Assert::same(403011, States::HIGH_DRAMA_PLAYER_TURN_03011, 'state');
            },

            // WHY: The performer must be opposed, and the mover must be your Thug or
            // Bodyguard at an adjacent city location. Home is adjacent (includeHome).
            // Two-player Docks adjacency is Forum + Home, so the Bazaar does not count.
            // Engage is not a cost.
            'an opposed performer can call an adjacent Thug or Bodyguard, including from Home' => function () {
                $world = new TestWorld();
                [, $action, $performer, $thug] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                Assert::same($performer->Id, $action->getPerformersForAction(1, $world->theah)[0]->Id, 'performer');

                $performer->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged performer');
                $performer->Engaged = false;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03011, 'highDramaPhase03011');
                Assert::same([$thug->Id], $args['ids'], 'home thug');

                $guard = $world->placeCharacter(new GenericCharacter('Guard', ['Bodyguard']), Game::LOCATION_CITY_FORUM, 1);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03011, 'highDramaPhase03011');
                Assert::same([$guard->Id, $thug->Id], $args['ids'], 'forum bodyguard then home thug');

                $bazaar = $world->placeCharacter(new GenericCharacter('Far', ['Thug']), Game::LOCATION_CITY_BAZAAR, 1);
                [$ok] = $action->isValidTargetForAbility($world->game, $bazaar);
                Assert::false($ok, 'bazaar is not adjacent to the Docks');
            },

            'no opposition, a civilian, an uncontrolled thug, or a risk outside the hand is unavailable' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $thug] = $this->scene($world);
                foreach ($world->theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS) as $character) {
                    if ($character->ControllerId === 2) {
                        $character->Location = Game::LOCATION_CITY_FORUM;
                    }
                }
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not opposed');

                $world = new TestWorld();
                [$risk, $action, , $thug] = $this->scene($world);
                // WHY: hasTrait copies Traits back when ModifiedTraits is empty, so the
                // last trait cannot be removed. Leave a second trait in place.
                $thug->addTrait($world->game, 'Scoundrel', true);
                $thug->removeTrait($world->game, 'Thug');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no trait');

                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $loose = $world->placeCharacter(new GenericCharacter('Loose', ['Thug']), Game::LOCATION_CITY_FORUM, 0);
                [$ok] = $action->isValidTargetForAbility($world->game, $loose);
                Assert::false($ok, 'uncontrolled is not yours');

                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in hand');
            },

            'trigger opens the picker, and confirm moves the thug unengaged' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer, $thug] = $this->scene($world);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03011', $transition->transition, 'picker');
                Assert::same($risk->Id, $transition->sourceId, 'risk');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03011, 'x', $thug->Id);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($thug->Id, $move->cardId, 'thug');
                Assert::same(Game::LOCATION_PLAYER_HOME, $move->fromLocation, 'from home');
                Assert::same($performer->Location, $move->toLocation, 'to the performer');
                Assert::false($move->engage, 'does not engage');
                Assert::same($risk->Id, $move->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'a missing or illegal target throws before any move' => function () {
                $world = new TestWorld();
                [, $action, , $thug] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03011, 'x', 999);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'missing');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');

                $thug->Location = Game::LOCATION_CITY_BAZAAR;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03011, 'x', $thug->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not adjacent');
                Assert::count(0, $world->theah->queuedEvents, 'still nothing');
            },
        ];
    }
}
