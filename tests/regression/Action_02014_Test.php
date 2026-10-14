<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02014_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02014';
    }

    /**
     * Scheme at Home; Leader in city who can Combat-pressure.
     *
     * @return array{0:_02014,1:Action_02014,2:GenericLeader}
     */
    private function scene(TestWorld $world): array
    {
        /** @var _02014 $scheme */
        $scheme = $world->placeCard(new _02014(), Game::LOCATION_PLAYER_HOME, 1);
        $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_02014 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $leader];
    }

    public function tests(): array
    {
        return [
            'is a CardAction that requires performer selected' => function () {
                $action = new Action_02014();
                Assert::instanceOf(CardAction::class, $action, 'CardAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when Leader in city can pressure Combat' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without a Leader in the city' => function () {
                $world = new TestWorld();
                [, $action, $leader] = $this->scene($world);
                $leader->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no city Leader');
            },

            'unavailable when Leader has dashed Combat' => function () {
                $world = new TestWorld();
                [, $action, $leader] = $this->scene($world);
                $leader->DashedCombat = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed Combat');
            },

            // WHY: KASPARS_OCCUPATION_PRESSURE_TYPE (256) ORs onto PRESSURE_TYPE so ties win.
            'trigger sets pressure globals and queues PressureOccuring + pressureLocation' => function () {
                $world = new TestWorld();
                [$scheme, $action, $leader] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $leader->Id);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::KASPARS_OCCUPATION_PRESSURE_TYPE),
                    'Kaspar flag'
                );
                Assert::count(1, $world->theah->queuedOfType(EventPressureOccuring::class), 'pressure');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('pressureLocation', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: success peeks top 4 city deck into CHOSEN_CARD JSON then transition "02014".
            'successful pressure peeks top 4 city deck and transitions 02014' => function () {
                $world = new TestWorld();
                [$scheme, $action, $leader] = $this->scene($world);

                $ids = [];
                for ($i = 0; $i < 4; $i++) {
                    $card = new GenericCharacter('City' . $i);
                    $card->setId(800 + $i);
                    $card->Location = Game::LOCATION_CITY_DECK;
                    $world->game->registerDbCard($card);
                    $world->theah->addCardToWorld($card);
                    $ids[] = ['id' => 800 + $i];
                }
                $world->game->topCityCards = $ids;

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = true;
                $result->playerId = 1;
                $result->performerId = $leader->Id;
                $result->location = $leader->Location;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                $cards = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::count(4, $cards, 'peeked 4');
                Assert::same(800, $cards[0]->id, 'first');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02014', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'failed pressure does not peek' => function () {
                $world = new TestWorld();
                [, $action, $leader] = $this->scene($world);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = false;
                $result->playerId = 1;
                $result->performerId = $leader->Id;
                $result->location = $leader->Location;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::same(null, $world->game->globals->get(Game::CHOSEN_CARD, null), 'no peek');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no transition');
            },
        ];
    }
}
