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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03037;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03037_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03037';
    }

    /**
     * @return array{0:_03037,1:Action_03037,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $sanjay = $world->placeCharacter(new _03037(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->placeCard(new _03037(), Game::LOCATION_HAND, 1);
        /** @var Action_03037 $action */
        $action = $sanjay->getActions()[0];
        return [$sanjay, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is a character action' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_03037(), 'character action');
            },

            // WHY: No Engage cost, so an engaged Sanjay can still issue it. The opponent
            // must have strictly fewer cards in hand. SANJAY_CHALLENGE_TYPE stays off the
            // auto-engage list. The challenge flow resolves the action.
            'issues an Influence challenge when their hand is smaller' => function () {
                $world = new TestWorld();
                [$sanjay, $action, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $sanjay->Engaged = true;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engaged still legal');
                $sanjay->Engaged = false;

                $sanjay->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not in the city');
                $sanjay->Location = Game::LOCATION_CITY_DOCKS;

                $sanjay->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed influence');
                $sanjay->DashedInfluence = false;

                $world->placeCard(new _03037(), Game::LOCATION_HAND, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'hands are equal');
                [$equal] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($equal, 'not fewer');

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->theah->takeQueuedEvents();
                // Remove the extra hand card so the target is legal again.
                $cards = $world->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, 2);
                foreach ($cards as $card) {
                    $card->Location = Game::LOCATION_PLAYER_HOME;
                }
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'smaller hand again');
                $action->handleEvent($triggered);
                Assert::same('03037', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'target step');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03037, 'x');
                Assert::same([$foe->Id], $args['ids'], 'the foe');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03037, 'x', $foe->Id);
                Assert::same($sanjay->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'sanjay');
                Assert::same($foe->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::same(Game::STAT_INFLUENCE, $world->game->globals->get(Game::CHALLENGE_STAT), 'influence');
                Assert::same(Game::SANJAY_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'sanjay type');
                $transition = $world->theah->queuedOfType(EventTransition::class)[1];
                Assert::same('03037_2', $transition->transition, 'challenge flow');
                Assert::same($sanjay->Id, $transition->sourceId, 'sanjay is the source');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'challenge flow resolves it');
                Assert::same(['targetChosen'], array_slice($world->game->gamestate->transitions, -1), 'target chosen');
            },

            'an uncontrolled character and a friendly character are rejected' => function () {
                $world = new TestWorld();
                [$sanjay, $action, $foe] = $this->scene($world);
                $foe->ControllerId = 0;
                [$wild] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($wild, 'uncontrolled');
                [$self] = $action->isValidTargetForAbility($world->game, $sanjay);
                Assert::false($self, 'not an opponent');
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03037, 'x', $sanjay->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'confirm rejects him');
            },
        ];
    }
}
