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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03009;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03009_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03009';
    }

    /**
     * @return array{0:_03009,1:Action_03009,2:_03004}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03009(), Game::LOCATION_HAND, 1);
        $elena = $world->placeCharacter(new _03004(), Game::LOCATION_CITY_DOCKS, 1);
        $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $elena->Id);
        /** @var Action_03009 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $elena];
    }

    public function tests(): array
    {
        return [
            'is a sorcerer risk action that must pick a performer' => function () {
                $action = new Action_03009();
                Assert::instanceOf(RiskAction::class, $action, 'risk action');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcery');
                Assert::true($action->RequiresPerformerSelected, 'performer');
                Assert::same(403009, States::HIGH_DRAMA_PLAYER_TURN_03009, 'state');
            },

            // WHY: Not a City Action. Home is a legal origin. Destinations are adjacent city
            // locations only — two-player Docks adjacency is the Forum, and Home is excluded.
            'a Sorcerer Strega can move toward an enemy or an available Mercenary' => function () {
                $world = new TestWorld();
                [, $action, $elena] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'forum foe');
                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::same($elena->Id, $performers[0]->Id, 'elena');

                $civilian = $world->placeCharacter(new GenericCharacter('Civilian', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::false(in_array($civilian->Id, $ids, true), 'sorcerer without Strega');

                $elena->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home origin');
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03009, 'highDramaPhase03009');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'empty Docks and Bazaar are dropped');

                $elena->Location = Game::LOCATION_CITY_DOCKS;
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03009, 'highDramaPhase03009');
                Assert::same([Game::LOCATION_CITY_FORUM], $args['locationIds'], 'docks adjacency excludes Home');
            },

            'a friendly-only location and an uncontrolled non-Mercenary do not qualify' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03009(), Game::LOCATION_HAND, 1);
                $elena = $world->placeCharacter(new _03004(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);
                $crowd = $world->placeCharacter(new GenericCharacter('Crowd'), Game::LOCATION_CITY_FORUM, 2);
                $crowd->ControllerId = 0;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $elena->Id);
                /** @var Action_03009 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nobody to follow');

                $crowd->addTrait($world->game, 'Mercenary', true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available mercenary');
            },

            'trigger opens the location chooser' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('03009', $transitions[0]->transition, 'name');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
            },

            'confirm moves without engaging, then resolves and plays the sorcery' => function () {
                $world = new TestWorld();
                [$risk, $action, $elena] = $this->scene($world);
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03009, 'x', [Game::LOCATION_CITY_FORUM]);

                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class);
                $move = $world->theah->queuedOfType(EventCardMoving::class);
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::same($elena->Id, $start[0]->performerId, 'start performer');
                Assert::same($risk->Id, $start[0]->sourceId, 'start source');
                Assert::same($elena->Id, $move[0]->cardId, 'moved');
                Assert::same(Game::LOCATION_CITY_DOCKS, $move[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $move[0]->toLocation, 'to');
                Assert::false($move[0]->engage, 'does not engage');
                Assert::same($action->Id, $move[0]->abilityId, 'ability');
                Assert::same(1, $resolved[0]->playerId, 'resolved');
                Assert::same($elena->Id, $played[0]->performerId, 'played');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'transition');
            },

            'an illegal location throws before anything is queued' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03009, 'x', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bazaar is not adjacent to the docks');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },
        ];
    }
}
