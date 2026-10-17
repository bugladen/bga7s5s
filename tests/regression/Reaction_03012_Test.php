<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03012_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03012';
    }

    /**
     * @return array{0:_03012,1:Reaction_03012,2:_03004}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _03012(), Game::LOCATION_HAND, 1);
        $strega = $world->placeCharacter(new _03004(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_03012 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $strega];
    }

    private function intervened(TestWorld $world, int $newTargetId, int $playerId = 1): EventCharacterIntervened
    {
        $event = new EventCharacterIntervened();
        $event->theah = $world->theah;
        $event->playerId = $playerId;
        $event->newTargetId = $newTargetId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a sorcerer risk reaction' => function () {
                $reaction = new Reaction_03012();
                Assert::instanceOf(RiskReaction::class, $reaction, 'risk reaction');
                Assert::instanceOf(ISorcererAbility::class, $reaction, 'sorcery');
            },

            // WHY: Your Strega must be the new target, and the risk must be in hand.
            // An opponent's intervene, or a non-Strega, does not offer.
            'offers when your Strega intervenes' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $strega] = $this->scene($world);
                $reaction->handleEvent($this->intervened($world, $strega->Id));
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same(1, $transition->playerId, 'owner');
                Assert::same($risk->Id, $transition->sourceId, 'risk');
                Assert::true($risk->IsUpdated, 'intervener latched');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['use', 'pass'], $buttons, 'buttons');

                $world->theah->takeQueuedEvents();
                $civilian = $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->handleEvent($this->intervened($world, $civilian->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not a strega');

                $reaction->handleEvent($this->intervened($world, $strega->Id, 2));
                Assert::count(0, $world->theah->queuedEvents, 'opponent intervene');

                $risk->Location = 'Discard-1';
                $reaction->handleEvent($this->intervened($world, $strega->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'use opens payment; the paid reaction makes the challenge Influence' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $strega] = $this->scene($world);
                $reaction->handleEvent($this->intervened($world, $strega->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class)[0];
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay->payStateType, 'in-hand reaction');
                Assert::same($risk->Id, $pay->cardId, 'risk');
                Assert::false($reaction->Used, 'not spent at the button');

                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::same(Game::STAT_INFLUENCE, $world->game->globals->get(Game::CHALLENGE_STAT), 'influence');
                $start = $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0];
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0];
                Assert::same($strega->Id, $start->performerId, 'start performer');
                Assert::same($strega->Id, $played->performerId, 'played performer');
                Assert::same($risk->Id, $start->sourceId, 'source');
                Assert::true($reaction->Used, 'spent');
            },

            'pass forgets the intervener so a later trigger does nothing' => function () {
                $world = new TestWorld();
                [, $reaction, $strega] = $this->scene($world);
                $reaction->handleEvent($this->intervened($world, $strega->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::count(0, $world->theah->queuedEvents, 'cleared');
                Assert::false($reaction->Used, 'pass');
                Assert::same(null, $world->game->globals->get(Game::CHALLENGE_STAT), 'stat untouched');
            },
        ];
    }
}
