<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01127;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01127;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01127_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01127';
    }

    /**
     * Hammer equipped on host; host is duel actor.
     *
     * @return array{0:_01127,1:Reaction_01127,2:GenericCharacter,3:GenericCharacter}
     */
    private function duel(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $hammer = $world->placeCard(new _01127(), Game::LOCATION_CITY_DOCKS, 1);
        $hammer->AttachedToId = $host->Id;
        $host->Attachments[] = $hammer->Id;
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Reaction_01127 $reaction */
        $reaction = $hammer->getReactions()[0];
        return [$hammer, $reaction, $host, $foe];
    }

    private function newRound(TestWorld $world, int $actorId): EventDuelNewRound
    {
        $event = new EventDuelNewRound();
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_01127(), 'AttachmentReaction');
            },

            'offers when the equipped host\'s duel round begins' => function () {
                $world = new TestWorld();
                [$hammer, $reaction, $host] = $this->duel($world);

                $reaction->handleEvent($this->newRound($world, $host->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($hammer->Id, $transitions[0]->sourceId, 'hammer');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer on the adversary\'s round' => function () {
                $world = new TestWorld();
                [, $reaction, , $foe] = $this->duel($world);

                $reaction->handleEvent($this->newRound($world, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not host');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $host] = $this->duel($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->newRound($world, $host->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'does not offer when the Hammer is not attached' => function () {
                $world = new TestWorld();
                [$hammer, $reaction, $host] = $this->duel($world);
                $hammer->AttachedToId = 0;

                $reaction->handleEvent($this->newRound($world, $host->Id));

                Assert::count(0, $world->theah->queuedEvents, 'unattached');
            },

            'buttons offer unequip and pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->duel($world);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['unequipAndPlayAsCombatCard', 'pass'], $ids, 'buttons');
            },

            'unequip path announces combat card, unequips, gains Lethal, marks used' => function () {
                $world = new TestWorld();
                [$hammer, $reaction, $host] = $this->duel($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'unequipAndPlayAsCombatCard');

                Assert::true($reaction->Used, 'used');
                Assert::same($hammer->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen combat card');
                Assert::count(1, $world->theah->queuedOfType(EventCombatCardAnnounced::class), 'announce');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlay::class);
                Assert::count(1, $removed, 'removed');
                Assert::same(Game::LOCATION_DUELING_LINE, $removed[0]->toLocation, 'dueling line');
                Assert::count(1, $world->theah->queuedOfType(EventThreatModified::class), 'Lethal');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::true(
                    count(array_filter($transitions, fn($t) => $t->transition === '01127')) === 1,
                    '01127 transition'
                );
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the reaction available' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->duel($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCombatCardAnnounced::class), 'no announce');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
