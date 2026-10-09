<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01107;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01109;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01109;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\ICancelReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** Stand-in for Celerity (_04047) / Unsanctioned Duel — printed "effects cannot be cancelled". */
final class UncancellableRisk_01109 extends Risk
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Uncancellable Test Risk';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Traits = [];
        $this->resetCard();
    }

    public function effectsCannotBeCancelled(): bool
    {
        return true;
    }
}

class Reaction_01109_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01109';
    }

    /** @return array{0:_01109,1:Reaction_01109} */
    private function scene(TestWorld $world): array
    {
        $nod = $world->placeCard(new _01109(), Game::LOCATION_HAND, 1);
        /** @var Reaction_01109 $reaction */
        $reaction = $nod->getReactions()[0];
        return [$nod, $reaction];
    }

    private function actionActivated(TestWorld $world, Risk $source, int $playerId = 2): EventActionActivated
    {
        $event = new EventActionActivated();
        $event->playerId = $playerId;
        $event->sourceId = $source->Id;
        $event->actionId = 'someAction';
        $event->theah = $world->theah;
        return $event;
    }

    private function riskPlayed(TestWorld $world, Risk $source, int $playerId = 2): EventRiskPlayed
    {
        $event = new EventRiskPlayed();
        $event->playerId = $playerId;
        $event->riskId = $source->Id;
        $event->theah = $world->theah;
        return $event;
    }

    private function maneuverActivated(TestWorld $world, string $maneuverId, int $playerId = 2): EventManeuverActivated
    {
        $event = new EventManeuverActivated();
        $event->playerId = $playerId;
        $event->maneuverId = $maneuverId;
        $event->theah = $world->theah;
        return $event;
    }

    private function offered(TestWorld $world): int
    {
        return count($world->theah->queuedOfType(EventTransition::class));
    }

    public function tests(): array
    {
        return [
            'is a Risk cancel reaction' => function () {
                $reaction = new Reaction_01109();
                Assert::instanceOf(ICancelReaction::class, $reaction, 'ICancelReaction');
                Assert::instanceOf(RiskReaction::class, $reaction, 'RiskReaction');
            },

            'buttons offer Cancel and Decline' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('cancel', $ids, true), 'cancel');
                Assert::true(in_array('decline', $ids, true), 'decline');
            },

            // ---- EventActionActivated (Risk Action path) ----
            'offers on opposing non-Sorcery Risk Action announce' => function () {
                $world = new TestWorld();
                [$nod, $reaction] = $this->scene($world);
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->actionActivated($world, $enemy));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'this reaction');
                Assert::same($nod->Id, $transitions[0]->sourceId, 'NoD');
                Assert::same(1, $transitions[0]->playerId, 'NoD controller');
                Assert::same($transitions[0], $world->theah->queuedEvents[0], 'stacked in front');
            },

            'does not offer for own Risk Action' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $mine = $world->placeCard(new _01107(), Game::LOCATION_HAND, 1);

                $reaction->handleEvent($this->actionActivated($world, $mine, 1));
                Assert::same(0, $this->offered($world), 'own');
            },

            'does not offer for Sorcery Risks' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $sorcery = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $sorcery->ModifiedTraits = ['Sorcery'];

                $reaction->handleEvent($this->actionActivated($world, $sorcery));
                Assert::same(0, $this->offered($world), 'Sorcery');
            },

            // WHY: Risk::effectsCannotBeCancelled() — Celerity/_04047 printed "cannot be cancelled".
            'does not offer when effectsCannotBeCancelled returns true' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $locked = $world->placeCard(new UncancellableRisk_01109(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->actionActivated($world, $locked));
                Assert::same(0, $this->offered($world), 'uncancellable');
            },

            'does not offer when Night of Drinking is not in hand' => function () {
                $world = new TestWorld();
                [$nod, $reaction] = $this->scene($world);
                $nod->Location = Game::LOCATION_PLAYER_HOME;
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->actionActivated($world, $enemy));
                Assert::same(0, $this->offered($world), 'not in hand');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->actionActivated($world, $enemy));
                Assert::same(0, $this->offered($world), 'used');
            },

            'does not double-offer when a Reaction_01109 transition is already queued' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->actionActivated($world, $enemy));
                Assert::same(1, $this->offered($world), 'first');

                $reaction->handleEvent($this->actionActivated($world, $enemy));
                Assert::same(1, $this->offered($world), 'no duplicate');
            },

            // ---- EventRiskPlayed (Risk Reaction path) ----
            // WHY (journals 2026-10-07-08 / 2026-09-20): Risk Reactions never fire ActionActivated —
            // only RiskPlayed + pending RiskReactionTriggered. Action plays must not re-offer here.
            'offers on opposing RiskPlayed when a RiskReactionTriggered for that source is queued' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $pending = new EventRiskReactionTriggered();
                $pending->sourceId = $enemy->Id;
                $pending->internalId = 'enemy_Reaction';
                $world->theah->queueEvent($pending);

                $reaction->handleEvent($this->riskPlayed($world, $enemy));

                Assert::same(1, $this->offered($world), 'offer');
            },

            'does not offer on RiskPlayed without a pending RiskReactionTriggered' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->riskPlayed($world, $enemy));
                Assert::same(0, $this->offered($world), 'Action path already handled');
            },

            'does not offer on RiskPlayed for uncancellable Risks' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $locked = $world->placeCard(new UncancellableRisk_01109(), Game::LOCATION_HAND, 2);
                $pending = new EventRiskReactionTriggered();
                $pending->sourceId = $locked->Id;
                $world->theah->queueEvent($pending);

                $reaction->handleEvent($this->riskPlayed($world, $locked));
                Assert::same(0, $this->offered($world), 'uncancellable');
            },

            // ---- EventManeuverActivated ----
            'offers on opposing Risk Maneuver announce' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $enemyRisk = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $maneuver = $enemyRisk->getManeuvers()[0];

                $reaction->handleEvent($this->maneuverActivated($world, $maneuver->Id));
                Assert::same(1, $this->offered($world), 'offer');
            },

            // WHY (journal 2026-10-01-05 Miyato): ManeuverActivated MUST require owning card instanceof Risk.
            // Miyato clones Maneuver onto Character — effectsCannotBeCancelled() does not exist there (fatal).
            'does not offer when Maneuver owner is a Character (Miyato clone), not a Risk' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new GenericCharacter('Miyato Host'), Game::LOCATION_CITY_DOCKS, 2);
                $enemyRisk = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $maneuver = $enemyRisk->getManeuvers()[0];
                // Keep maneuver findable via Risk's Maneuvers list, but point OwnerId at Character.
                $maneuver->OwnerId = $host->Id;

                $reaction->handleEvent($this->maneuverActivated($world, $maneuver->Id));
                Assert::same(0, $this->offered($world), 'non-Risk owner skipped — no fatal');
            },

            'does not offer Maneuver on Sorcery Risk' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $enemyRisk = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $enemyRisk->ModifiedTraits = ['Sorcery'];
                $maneuver = $enemyRisk->getManeuvers()[0];

                $reaction->handleEvent($this->maneuverActivated($world, $maneuver->Id));
                Assert::same(0, $this->offered($world), 'Sorcery');
            },

            'does not offer Maneuver when effectsCannotBeCancelled' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $locked = $world->placeCard(new UncancellableRisk_01109(), Game::LOCATION_HAND, 2);
                // Attach a real maneuver by borrowing from It's Personal, re-owning onto locked Risk.
                $donor = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $maneuver = $donor->getManeuvers()[0];
                $maneuver->OwnerId = $locked->Id;

                $reaction->handleEvent($this->maneuverActivated($world, $maneuver->Id));
                Assert::same(0, $this->offered($world), 'uncancellable Risk owner');
            },

            // ---- performReaction / Speed ----
            // WHY: cancel stacks EnteringPay + pay at HIGHEST_PRIORITY so NoD drains before
            // normal-priority cancels (Unyielding Loyalty) that share the same window.
            'perform cancel stacks EnteringPay then pay at HIGHEST_PRIORITY and marks Used' => function () {
                $world = new TestWorld();
                [$nod, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                $queued = $world->theah->queuedEvents;
                Assert::true(count($queued) >= 2, 'pay events stacked');

                $entering = null;
                $pay = null;
                foreach ($queued as $event) {
                    if ($event instanceof EventEnteringPayState) {
                        $entering = $event;
                    }
                    if ($event instanceof EventTransition && $event->transition === 'pay') {
                        $pay = $event;
                    }
                }
                Assert::true($entering !== null, 'EnteringPay');
                Assert::same(Event::HIGHEST_PRIORITY, $entering->priority, 'EnteringPay speed');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $entering->payStateType, 'in-hand reaction');
                Assert::same($nod->Id, $entering->cardId, 'NoD');

                Assert::true($pay !== null, 'pay transition');
                Assert::same(Event::HIGHEST_PRIORITY, $pay->priority, 'pay speed');
                Assert::same($reaction->Id, $pay->internalId, 'reaction');

                Assert::true($reaction->Used, 'Used immediately on cancel choice');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activated');
            },

            'perform decline finishes without paying or marking Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // ---- RiskReactionTriggered cancel resolve ----
            'triggered cancel after Action path deletes pending events and fires ActionResolved' => function () {
                $world = new TestWorld();
                [$nod, $reaction] = $this->scene($world);
                $enemy = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);

                $reaction->handleEvent($this->actionActivated($world, $enemy, 2));
                $world->theah->takeQueuedEvents();

                $triggered = new EventActionTriggered();
                $triggered->sourceId = $enemy->Id;
                $triggered->actionId = 'enemyAction';
                $world->theah->queueEvent($triggered);

                $played = new EventRiskPlayed();
                $played->riskId = $enemy->Id;
                $world->theah->queueEvent($played);

                $event = new EventRiskReactionTriggered();
                $event->playerId = 1;
                $event->sourceId = $nod->Id;
                $event->internalId = $reaction->Id;
                $event->reactionId = 'cancel';
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventActionTriggered::class), 'ActionTriggered deleted');
                Assert::count(0, $world->theah->queuedOfType(EventRiskPlayed::class), 'RiskPlayed deleted');
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'ActionResolved for Soline window');
                Assert::same(2, $resolved[0]->playerId, 'announcer player');
            },

            'triggered cancel after Maneuver path deletes Maneuver events and does not fire ActionResolved' => function () {
                $world = new TestWorld();
                [$nod, $reaction] = $this->scene($world);
                $enemyRisk = $world->placeCard(new _01107(), Game::LOCATION_HAND, 2);
                $maneuver = $enemyRisk->getManeuvers()[0];

                $reaction->handleEvent($this->maneuverActivated($world, $maneuver->Id));
                $world->theah->takeQueuedEvents();

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $world->theah->queueEvent($resolve);

                $event = new EventRiskReactionTriggered();
                $event->playerId = 1;
                $event->sourceId = $nod->Id;
                $event->internalId = $reaction->Id;
                $event->reactionId = 'cancel';
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventResolveManeuver::class), 'maneuver deleted');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not an Action cancel');
            },
        ];
    }
}
