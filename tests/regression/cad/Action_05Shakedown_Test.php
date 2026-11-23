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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Shakedown;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Shakedown;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Shakedown_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Shakedown';
    }

    /**
     * Risk in hand (player 1), performer + foe (player 2) at uncontrolled Docks.
     *
     * @return array{world: TestWorld, risk: Risk, action: Action_05Shakedown, performer: GenericCharacter, foe: GenericCharacter}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $risk = $world->placeCard(new _05Shakedown(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Thief'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_05Shakedown $action */
        $action = $risk->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

        return compact('world', 'risk', 'action', 'performer', 'foe');
    }

    public function tests(): array
    {
        return [
            'is a Castille risk with a single Covert equip action' => function () {
                $card = new _05Shakedown();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Riposte, 'riposte');
                Assert::same(0, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::count(1, $card->getActions(), 'one action');
                Assert::instanceOf(RiskAction::class, $card->getActions()[0], 'RiskAction');
            },

            // WHY: Covert = uncontrolled City. Equip needs opposing at the performer location.
            'is only offered at an uncontrolled city location with an opposing character' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'foe' => $foe] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled location is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home performer');
                $performer->Location = Game::LOCATION_CITY_DOCKS;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'foe must share the performer location');
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');
            },

            // WHY: Action-only E.2 — Scoundrel or Villain performer discounts Action pay, not combat-card pay.
            'discounts -1 when the performer is a Scoundrel or Villain' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer] = $this->scene();
                $explanations = [];

                Assert::same(0, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'no trait');

                $performer->ModifiedTraits = ['Scoundrel'];
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'scoundrel');

                $performer->ModifiedTraits = ['Villain'];
                $explanations = [];
                Assert::same(1, $action->getActionFromHandDiscount($world->theah, $performer, $action, $explanations), 'villain');

                Assert::same(0, $action->getActionFromHandDiscount($world->theah, null, $action, $explanations), 'null performer');
            },

            'trigger queues transition 05Shakedown' => function () {
                ['world' => $world, 'risk' => $risk, 'action' => $action] = $this->scene();

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same('05Shakedown', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
            },

            // WHY: Confirm only queues CharacterTargeted. Equip runs from handleEvent when
            // that hook is not canceled, so UL / Terraria can stop the shell.
            'queues CharacterTargeted on confirm; equips shell only if targeting survives' => function () {
                ['world' => $world, 'risk' => $risk, 'action' => $action, 'foe' => $foe] = $this->scene();

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SHAKEDOWN, '', $foe->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterTargeted::class), 'hook only');
                Assert::count(0, $world->game->createdRiskAttachments, 'not equipped yet');
                Assert::same('characterChosen', $world->game->gamestate->transitions[0], 'next');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                Assert::same($foe->Id, $targeted->targetId, 'foe');
                Assert::same($action->Id, $targeted->abilityId, 'ability');

                $targeted->canceled = true;
                $world->theah->takeQueuedEvents();
                $world->fireOn($risk, $targeted);
                Assert::count(0, $world->game->createdRiskAttachments, 'cancel stops equip');

                $targeted->canceled = false;
                $world->fireOn($risk, $targeted);
                Assert::same('05Shakedown_Att', $world->game->createdRiskAttachments[0]['className'], 'shell');
                Assert::same($foe->Id, $world->game->createdRiskAttachments[0]['targetId'], 'foe');
                Assert::same(2, $world->game->createdRiskAttachments[0]['controllerId'], 'target controller');
                Assert::same(1, $world->game->createdRiskAttachments[0]['ownerId'], 'risk owner');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05SHAKEDOWN, '', $world->game->globals->get(Game::CHOSEN_PERFORMER));
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'own character');
            },
        ];
    }
}
