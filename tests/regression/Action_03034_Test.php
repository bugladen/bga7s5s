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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03034;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03034;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03034_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03034';
    }

    /**
     * @return array{0:_03034,1:Action_03034,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03034(), Game::LOCATION_HAND, 1);
        $diplomat = $world->placeCharacter(new GenericCharacter('Diplomat', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $ally->Engaged = true;
        $ally->Wounds = 1;
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $diplomat->Id);
        /** @var Action_03034 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $diplomat, $ally];
    }

    public function tests(): array
    {
        return [
            'is a city action' => function () {
                $action = new Action_03034();
                Assert::instanceOf(RiskCityAction::class, $action, 'city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Engage is paid at announce, before ActionTriggered, so a cancel of the
            // trigger still leaves the engage. Trigger does not queue a second one.
            'announce engages the Diplomat, then the trigger opens the target step' => function () {
                $world = new TestWorld();
                [, $action, $diplomat, $ally] = $this->scene($world);
                Assert::same([$diplomat->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'unengaged diplomat');
                $diplomat->Engaged = true;
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'engaged diplomat');
                $diplomat->Engaged = false;

                $action->announceAction($world->game);
                Assert::same($diplomat->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage at announce');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'not engaged twice');
                Assert::same('03034', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'target step');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03034, 'x');
                Assert::same([$ally->Id], $args['ids'], 'engaged ally');
                [$ok] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::true($ok, 'valid');
                [$self] = $action->isValidTargetForAbility($world->game, $diplomat);
                Assert::false($self, 'not the performer');
                $ally->Engaged = false;
                [$ready] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($ready, 'must be engaged');
            },

            'a civilian performer throws before the target step' => function () {
                $world = new TestWorld();
                [, $action, $diplomat] = $this->scene($world);
                $diplomat->addTrait($world->game, 'Hero', true);
                $diplomat->removeTrait($world->game, 'Diplomat');
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $threw = false;
                try {
                    $action->handleEvent($triggered);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not a diplomat');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no step');
            },

            // WHY: No wounds means they cannot heal, so the draw happens immediately.
            // A wounded target waits for the heal-or-draw choice.
            'an unwounded target draws; a wounded target waits' => function () {
                $world = new TestWorld();
                [, $action, , $ally] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03034, 'x', $ally->Id);
                Assert::same($ally->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'en garde');
                Assert::same('03034_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'target');

                $world->theah->takeQueuedEvents();
                $ally->Wounds = 0;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03034, 'x', $ally->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'healing or drawing both resolve the action' => function () {
                $world = new TestWorld();
                [, $action, , $ally] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $ally->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03034_2, 'x', 1);
                Assert::same($ally->Id, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->characterId, 'healed');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03034_2, 'x', 2);
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'draw instead');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'not healed');

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03034_2, 'x', 3);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid choice');
            },
        ];
    }
}
