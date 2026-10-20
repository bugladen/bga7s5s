<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02003_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02003';
    }

    /**
     * Mourad + friendly Strega at same city location.
     *
     * @return array{0:_02003,1:Reaction_02003,2:GenericCharacter}
     */
    private function scene(TestWorld $world, bool $engaged = false): array
    {
        $mourad = $world->placeCharacter(new _02003(), Game::LOCATION_CITY_DOCKS, 1);
        $mourad->Engaged = $engaged;
        $strega = $world->placeCharacter(
            new GenericCharacter('Ally Strega', ['Strega', 'Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Reaction_02003 $reaction */
        $reaction = $mourad->getReactions()[0];
        return [$mourad, $reaction, $strega];
    }

    private function issued(TestWorld $world, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = 0;
        $event->defenderId = $defenderId;
        $event->playerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_02003(), 'CardReaction');
            },

            // WHY: Mourad may intervene for Strega at his location even while Engaged.
            'offers when friendly Strega at Mourad\'s location is challenged' => function () {
                $world = new TestWorld();
                [$mourad, $reaction, $strega] = $this->scene($world, true);

                $reaction->handleEvent($this->issued($world, $strega->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($mourad->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::true($mourad->Engaged, 'still engaged when offered');
            },

            'does not offer for non-Strega ally' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->issued($world, $ally->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not Strega');
            },

            'does not offer when Strega is at another location' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $far = $world->placeCharacter(
                    new GenericCharacter('Far Strega', ['Strega']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $reaction->handleEvent($this->issued($world, $far->Id));
                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');
            },

            'does not offer when Mourad is at Home' => function () {
                $world = new TestWorld();
                [$mourad, $reaction, $strega] = $this->scene($world);
                $mourad->Location = Game::LOCATION_PLAYER_HOME;
                $strega->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->issued($world, $strega->Id));
                Assert::count(0, $world->theah->queuedEvents, 'Home');
            },

            'does not offer for opposing Strega' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $enemy = $world->placeCharacter(
                    new GenericCharacter('Enemy Strega', ['Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                $reaction->handleEvent($this->issued($world, $enemy->Id));
                Assert::count(0, $world->theah->queuedEvents, 'enemy');
            },

            'intervene swaps defender to Mourad and queues Intervened' => function () {
                $world = new TestWorld();
                [$mourad, $reaction, $strega] = $this->scene($world, true);
                $strega->addCondition(Game::DUEL_DEFENDER);
                $world->game->globals->set(Game::CHOSEN_TARGET, $strega->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'intervene');

                Assert::false($strega->hasCondition(Game::DUEL_DEFENDER), 'strega cleared');
                Assert::true($mourad->hasCondition(Game::DUEL_DEFENDER), 'mourad defender');
                Assert::same($mourad->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'target');
                Assert::true($world->game->globals->get(Game::CHALLENGE_ACCEPTED), 'accepted');
                $intervened = $world->theah->queuedOfType(EventCharacterIntervened::class);
                Assert::count(1, $intervened, 'intervened');
                Assert::same($strega->Id, $intervened[0]->oldTargetId, 'old');
                Assert::same($mourad->Id, $intervened[0]->newTargetId, 'new');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not intervene' => function () {
                $world = new TestWorld();
                [, $reaction, $strega] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $strega->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedEvents, 'no intervene');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
