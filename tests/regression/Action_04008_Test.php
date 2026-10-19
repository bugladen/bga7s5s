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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04008;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04008_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04008';
    }

    public function tests(): array
    {
        return [
            'is a sorcerer risk action' => function () {
                $action = new Action_04008();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Performer must be a Sorcerer Strega in the city with an opposing non-Leader.
            // The equip is a risk attachment named 04008_Silence, not the risk itself.
            'equips silence to an opposing non-leader' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04008(), Game::LOCATION_HAND, 1);
                $strega = $world->placeCharacter(new GenericCharacter('Strega', ['Sorcerer', 'Strega']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_04008 $action */
                $action = $risk->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $foe->ModifiedTraits = ['Leader'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'only a leader');
                $foe->ModifiedTraits = [];
                $strega->ModifiedTraits = ['Sorcerer'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not a strega');
                $strega->ModifiedTraits = ['Sorcerer', 'Strega'];

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04008', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04008, '', $foe->Id);
                Assert::same('04008_Silence', $world->game->createdRiskAttachments[0]['className'], 'silence shell');
                Assert::same($foe->Id, $world->game->createdRiskAttachments[0]['targetId'], 'foe');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0]->sourceId, 'start');
                Assert::same($strega->Id, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0]->performerId, 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('characterChosen', $world->game->gamestate->transitions[0], 'next');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04008, '', $strega->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'own character');
            },
        ];
    }
}
