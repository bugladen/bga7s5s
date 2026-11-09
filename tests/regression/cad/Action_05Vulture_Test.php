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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Vulture;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Vulture;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Vulture_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Vulture';
    }

    /**
     * @return array{world: TestWorld, vulture: _05Vulture, action: Action_05Vulture, scion: GenericCharacter, equipped: GenericCharacter, bare: GenericCharacter}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $vulture = $world->placeCharacter(new _05Vulture(), Game::LOCATION_CITY_DOCKS, 1);
        $scion = $world->placeCharacter(new GenericCharacter('Scion', ['Scion']), Game::LOCATION_CITY_DOCKS, 2);
        $equipped = $world->placeCharacter(new GenericCharacter('Equipped'), Game::LOCATION_CITY_DOCKS, 2);
        $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
        $bow->AttachedToId = $equipped->Id;
        $equipped->Attachments = [$bow->Id];
        $bare = $world->placeCharacter(new GenericCharacter('Bare'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_05Vulture $action */
        $action = $vulture->getActions()[0];

        return compact('world', 'vulture', 'action', 'scion', 'equipped', 'bare');
    }

    public function tests(): array
    {
        return [
            // WHY: Covert = uncontrolled City. No Engage printed. Target = opposing Scion OR equipped.
            'is only offered at an uncontrolled city with a Scion or equipped opposing character' => function () {
                ['world' => $world, 'vulture' => $vulture, 'action' => $action, 'scion' => $scion, 'equipped' => $equipped, 'bare' => $bare] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $vulture->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home is not Covert');
                $vulture->Location = Game::LOCATION_CITY_DOCKS;

                $scion->Location = Game::LOCATION_CITY_FORUM;
                $equipped->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'only bare left at location');
                $scion->Location = Game::LOCATION_CITY_DOCKS;
                $equipped->Location = Game::LOCATION_CITY_DOCKS;

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');
                Assert::false(in_array($bare->Id, $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05VULTURE, '')['ids'], true), 'bare excluded');
            },

            'queues the target chooser on ActionTriggered' => function () {
                ['world' => $world, 'vulture' => $vulture, 'action' => $action] = $this->scene();

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($vulture, $triggered);
                Assert::same('05Vulture', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
            },

            'wounds a chosen Scion or equipped opposing character' => function () {
                ['world' => $world, 'action' => $action, 'scion' => $scion, 'equipped' => $equipped, 'bare' => $bare] = $this->scene();

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05VULTURE, '')['ids'];
                Assert::same([$scion->Id, $equipped->Id], array_values($ids), 'scion and equipped');

                [$ok] = $action->isValidTargetForAbility($world->game, $bare);
                Assert::false($ok, 'bare rejected');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05VULTURE, '', $scion->Id);
                Assert::same($scion->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'done');
            },

            'rejects Covert resolve after the location becomes controlled' => function () {
                ['world' => $world, 'action' => $action, 'scion' => $scion] = $this->scene();
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05VULTURE, '', $scion->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'Covert recheck');
            },

            // WHY: Kaspar (_03014) is a Scion but opponents' abilities cannot wound him.
            // Cesca shape — hide the Action when every opposing Scion/equipped target is immune.
            'is not offered when the only opposing Scion is Kaspar' => function () {
                $world = new TestWorld();
                $vulture = $world->placeCharacter(new _05Vulture(), Game::LOCATION_CITY_DOCKS, 1);
                $kaspar = $world->placeCharacter(new _03014(), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_05Vulture $action */
                $action = $vulture->getActions()[0];

                Assert::true($kaspar->hasTrait('Scion'), 'Kaspar is a Scion');
                Assert::false($kaspar->canBeWoundedByOpponentAbilities(), 'immune');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no woundable target');
            },
        ];
    }
}
