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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03042;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03042_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03042';
    }

    /**
     * @return array{0:_03042,1:Action_03042,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _03042(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
        /** @var Action_03042 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer, $foe];
    }

    public function tests(): array
    {
        return [
            'is a scheme city action' => function () {
                $action = new Action_03042();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Engage is queued here and the challenge type stays off the auto-engage
            // list, so setup does not engage a second time. The action is not resolved here.
            'an unengaged city performer issues a Finesse challenge' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer, $foe] = $this->scene($world);
                Assert::same([$performer->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'performer');
                $performer->Engaged = true;
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'engaged');
                $performer->Engaged = false;
                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'home');
                $performer->Location = Game::LOCATION_CITY_DOCKS;

                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'opposing');
                $foe->ControllerId = 0;
                [$wild] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($wild, 'uncontrolled');
                $foe->ControllerId = 2;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                Assert::same(Game::WHEN_LEAST_EXPECTED_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'type');
                Assert::same(Game::STAT_FINESSE, $world->game->globals->get(Game::CHALLENGE_STAT), 'finesse');
                Assert::same($scheme->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'the scheme');
                Assert::same('03042', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'challenge');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved here');
            },

            // WHY: When the performer is a Duelist, refuse costs a discard, then the
            // challenge is rejected. The discard is an effect.
            'refusing discards a card and rejects the challenge' => function () {
                $world = new TestWorld();
                [, $action, $performer, $foe] = $this->scene($world);
                $hand = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $world->game->activePlayerId = 2;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03042, 'x', $hand->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::true($discard->asEffect, 'effect');
                Assert::false($discard->AsPayment, 'not payment');
                $rejected = $world->theah->queuedOfType(EventChallengeRejected::class)[0];
                Assert::same($performer->Id, $rejected->challengerId, 'challenger');
                Assert::same($foe->Id, $rejected->targetId, 'target');
                Assert::false((bool) $world->game->globals->get(Game::CHALLENGE_ACCEPTED), 'not accepted');
                Assert::same(['cardDiscarded'], $world->game->gamestate->transitions, 'discarded');

                $hand->Location = Game::LOCATION_CITY_DOCKS;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03042, 'x', $hand->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
            },
        ];
    }
}
