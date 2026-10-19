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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03040;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03040_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03040';
    }

    /**
     * @return array{0:_03040,1:Action_03040,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $soline = $world->placeCharacter(new _03040(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_03040 $action */
        $action = $soline->getActions()[0];
        return [$soline, $action, $foe];
    }

    public function tests(): array
    {
        return [
            // WHY: Available when she can claim OR engage someone. Engage-only stays
            // legal when the location cannot be claimed. Trigger engages her, stamps the
            // Soline pressure flag, and opens pressure — it does not resolve yet.
            'pressures with Finesse, then claims or engages' => function () {
                $world = new TestWorld();
                [$soline, $action, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'claim and engage');
                $soline->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $soline->Engaged = false;
                $soline->DashedFinesse = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed finesse');
                $soline->DashedFinesse = false;

                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                $foe->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing to do');
                $foe->Engaged = false;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'engage only');
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = true;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $action->handleEvent($triggered);
                Assert::same($soline->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'she engages');
                Assert::same(Game::STAT_FINESSE, $world->theah->queuedOfType(EventPressureOccuring::class)[0]->pressureTypes[0], 'finesse');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pressure');
                Assert::true($world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::SOLINE_PRESSURE_TYPE), 'soline flag');
                Assert::same($soline->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer');

                $result = new EventLocationPressureResult();
                $result->theah = $world->theah;
                $result->abilityId = $action->Id;
                $result->success = true;
                $world->theah->takeQueuedEvents();
                $action->handleEvent($result);
                Assert::same('03040', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'payoff');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'choice still open');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03040, 'x');
                Assert::true($args['canClaim'], 'can claim');
                Assert::same([$foe->Id], $args['ids'], 'unengaged foe');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03040, 'x', 0);
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'claimed');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['choiceMade'], array_slice($world->game->gamestate->transitions, -1), 'choice');
            },

            'failure resolves immediately, and an engaged foe cannot be the engage choice' => function () {
                $world = new TestWorld();
                [$soline, $action, $foe] = $this->scene($world);
                $result = new EventLocationPressureResult();
                $result->theah = $world->theah;
                $result->abilityId = $action->Id;
                $result->success = false;
                $action->handleEvent($result);
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'failed pressure resolves');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no payoff');

                $world->theah->takeQueuedEvents();
                $result->success = true;
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;
                $foe->Engaged = true;
                $action->handleEvent($result);
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'success with no payoff still resolves');

                $foe->Engaged = false;
                $threw = false;
                try {
                    $foe->Engaged = true;
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03040, 'x', $foe->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'already engaged');
                unset($soline);
            },
        ];
    }
}
