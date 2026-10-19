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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03062;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03062_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03062';
    }

    public function tests(): array
    {
        return [
            'is a scheme city action that needs a performer' => function () {
                $action = new Action_03062();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Villain trait gate. Locker characters that are Undead or Mercenary are skipped.
            // Monster and Undead are granted when the muster event resolves, not in the click.
            // A character who already has Monster does not also gain the granted-monster condition.
            'wounds a villain and musters a locker character' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03062(), Game::LOCATION_PLAYER_HOME, 1);
                $villain = $world->placeCharacter(new GenericCharacter('Villain', ['Villain']), Game::LOCATION_CITY_DOCKS, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero', ['Hero']), Game::LOCATION_CITY_FORUM, 1);
                $locker = $world->game->getPlayerLockerName(1);
                $corpse = $world->placeCharacter(new GenericCharacter('Corpse'), $locker, 1);
                $undead = $world->placeCharacter(new GenericCharacter('Undead', ['Undead']), $locker, 1);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary']), $locker, 1);
                $world->placeCard(new _03055(), $locker, 1);
                /** @var Action_03062 $action */
                $action = $scheme->getActions()[0];
                Assert::same([$villain->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'villain');
                Assert::same([$corpse->Id], array_map(fn ($c) => $c->Id, $action->getEligibleLockerCharacters(1, $world->theah)), 'corpse');
                unset($hero, $merc);

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $villain->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03062', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not yet');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03062, 'x', $undead->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'undead');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03062, 'x', $corpse->Id);
                Assert::same($villain->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wounded');
                Assert::same($corpse->Id, $world->theah->queuedOfType(EventCharacterMustered::class)[0]->characterId, 'mustered');
                Assert::same($corpse->Id, $action->pendingMusterId, 'pending');
                Assert::false($corpse->hasTrait('Undead'), 'not yet');

                $mustered = new EventCharacterMustered();
                $mustered->theah = $world->theah;
                $mustered->characterId = $corpse->Id;
                $action->handleEvent($mustered);
                Assert::true($corpse->hasTrait('Monster'), 'monster');
                Assert::true($corpse->hasTrait('Undead'), 'undead');
                Assert::true($corpse->hasCondition(Game::DEAL_WITH_THE_DEVIL), 'condition');
                Assert::true($corpse->hasCondition(Game::DEAL_WITH_THE_DEVIL_GRANTED_MONSTER), 'granted monster');
                Assert::same(0, $action->pendingMusterId, 'cleared');
            },
        ];
    }
}
