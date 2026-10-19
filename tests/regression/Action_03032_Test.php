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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03032;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03032_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03032';
    }

    /**
     * @return array{0:_03032,1:Action_03032,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03032(), Game::LOCATION_HAND, 1);
        $sorcerer = $world->placeCharacter(new GenericCharacter('Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $sorcerer->Id);
        /** @var Action_03032 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $sorcerer];
    }

    public function tests(): array
    {
        return [
            'is a performer risk action' => function () {
                $action = new Action_03032();
                Assert::instanceOf(RiskAction::class, $action, 'risk action');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Any city location other than the current one, plus Home. A Sorcerer at
            // Home is legal. The trigger only opens the picker.
            'a Sorcerer anywhere in play can leave for any other location' => function () {
                $world = new TestWorld();
                [$risk, $action, $sorcerer] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $ids = array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$sorcerer->Id], $ids, 'sorcerer');

                $sorcerer->addTrait($world->game, 'Hero', true);
                $sorcerer->removeTrait($world->game, 'Sorcerer');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not a sorcerer');
                $sorcerer->addTrait($world->game, 'Sorcerer', true);

                $sorcerer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home sorcerer');
                $sorcerer->Location = Game::LOCATION_CITY_DOCKS;

                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'risk not in hand');
                $risk->Location = Game::LOCATION_HAND;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03032', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03032, 'x');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'not the current location');
                Assert::true(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'home');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'forum');
            },

            // WHY: Follow-up is optional and locked to this performer. Move does not engage.
            'the chosen location wounds, moves, and grants one more action' => function () {
                $world = new TestWorld();
                [, $action, $sorcerer] = $this->scene($world);
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03032, 'x', [Game::LOCATION_CITY_FORUM]);
                Assert::same($sorcerer->Id, $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0]->performerId, 'sorcery start');
                Assert::same($sorcerer->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wounded');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $move->fromLocation, 'from docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'to forum');
                Assert::false($move->engage, 'no engage');
                Assert::same(Game::LOCATION_CITY_FORUM, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0]->targetLocation, 'played');
                Assert::same(1, $world->game->globals->get(Game::EXTRA_ACTIONS), 'one more action');
                Assert::same($sorcerer->Id, $world->game->globals->get(Game::EXTRA_ACTION_PERFORMER), 'locked performer');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'location chosen');
            },

            'an illegal location throws before the wound' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03032, 'x', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'current location');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },
        ];
    }
}
