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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04002;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04002;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04002_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04002';
    }

    /**
     * @return array{0:TestWorld,1:_04002,2:Action_04002,3:GenericCharacter}
     */
    private function ready(): array
    {
        $world = new TestWorld();
        $danilo = $world->placeCharacter(new _04002(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_04002 $action */
        $action = $danilo->getActions()[0];
        return [$world, $danilo, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is a character action' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_04002(), 'CharacterAction');
            },

            // WHY: Engage is printed, so only an unengaged Danilo in the city qualifies.
            // Targets are opposing characters at his location.
            'available only for an unengaged Danilo with an opposing target' => function () {
                [$world, $danilo, $action, $foe] = $this->ready();
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $danilo->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $danilo->Engaged = false;
                $danilo->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed influence');
                $danilo->DashedInfluence = false;
                $danilo->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $danilo->Location = Game::LOCATION_CITY_DOCKS;
                $foe->ControllerId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'uncontrolled neighbor');
            },

            // WHY: DANILO_CHALLENGE_TYPE keys the intervene wound-or-draw. The challenge
            // flow resolves the action later, so the target click does not.
            'choosing a target issues an influence challenge and does not resolve yet' => function () {
                [$world, $danilo, $action, $foe] = $this->ready();
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);
                Assert::same('04002', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04002, '', $foe->Id);
                Assert::same(Game::STAT_INFLUENCE, $world->game->globals->get(Game::CHALLENGE_STAT), 'influence');
                Assert::same(Game::DANILO_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'danilo type');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same('04002_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');
                Assert::same('targetChosen', $world->game->gamestate->transitions[0], 'next');
            },

            'an intervener can be wounded or replaced with a draw' => function () {
                [$world, $danilo, $action] = $this->ready();
                $intervener = $world->placeCharacter(new GenericCharacter('Intervener'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::DANILO_CHALLENGE_TYPE);
                $event = new EventCharacterIntervened();
                $event->newTargetId = $intervener->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);
                Assert::same($intervener->Id, $action->IntervenerId, 'stashed');
                Assert::same('04002_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'choice');

                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04002_3, '', 0);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($intervener->Id, $wound->characterId, 'wounded');
                Assert::same(0, $action->IntervenerId, 'cleared');
                Assert::same('done', $world->game->gamestate->transitions[0], 'done');

                $action->IntervenerId = $intervener->Id;
                $world->theah->takeQueuedEvents();
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04002_3, '', 1);
                Assert::same($danilo->ControllerId, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'draw');

                $action->IntervenerId = $intervener->Id;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04002_3, '', 2);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid choice');
            },
        ];
    }
}
