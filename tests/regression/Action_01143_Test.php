<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01143;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01143;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_01143_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01143';
    }

    /**
     * Scheme at Home; ready Influence-pressurer in city.
     *
     * @return array{0:_01143,1:Action_01143,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01143(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Diplomat'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_01143 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction that requires a performer' => function () {
                $action = new Action_01143();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'available when scheme is at Home and a ready city character can Influence-pressure' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when the performer is engaged' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable when Influence is dashed' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $performer->DashedInfluence = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed');
            },

            'unavailable when the scheme is not at Player Home' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);
                $scheme->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not Home');
            },

            'getPerformersForAction lists only ready Influence-pressurers in the city' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $engaged = $world->placeCharacter(new GenericCharacter('Tired'), Game::LOCATION_CITY_FORUM, 1);
                $engaged->Engaged = true;
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($performer->Id, $ids, true), 'diplomat');
                Assert::false(in_array($engaged->Id, $ids, true), 'engaged excluded');
                Assert::false(in_array($home->Id, $ids, true), 'home excluded');
            },

            'trigger engages the performer and starts Contempt Influence pressure' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::true(
                    $world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::CONTEMPT_AND_HATRED_PRESSURE_TYPE),
                    'Contempt flag'
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($performer->Id, $engages[0]->cardId, 'performer');
                Assert::same($scheme->Id, $engages[0]->sourceId, 'scheme source');

                $pressure = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressure, 'pressure');
                Assert::true(in_array(Game::STAT_INFLUENCE, $pressure[0]->pressureTypes, true), 'Influence');
                Assert::same('pressureLocation', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            // WHY: FakeGame has no UtilitiesTrait; "You succeed even if tied" for this pressure
            // lives in Game::pressureLocation / UtilitiesTrait and is source-locked here.
            'pressure resolution lets CONTEMPT_AND_HATRED pressure win ties' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/UtilitiesTrait.php');
                $tiesWin = (int)strpos($src, '//Ties win');
                Assert::true($tiesWin > 0, 'tie-win branch present');
                $conditionStart = (int)strrpos(substr($src, 0, $tiesWin), 'if (');
                $region = substr($src, $conditionStart, $tiesWin - $conditionStart);
                Assert::contains('Game::CONTEMPT_AND_HATRED_PRESSURE_TYPE', $region, 'Contempt in tie-win');
            },

            'pressure success claims the performer location and resolves' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                $claims = $world->theah->queuedOfType(EventLocationClaimed::class);
                Assert::count(1, $claims, 'claimed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claims[0]->location, 'docks');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'success at an unclaimable location resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->CanBeClaimed = false;

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'pressure failure resolves without claiming' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $result = new EventLocationPressureResult();
                $result->abilityId = $action->Id;
                $result->success = false;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
