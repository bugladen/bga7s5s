<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01155;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01155;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01155_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01155';
    }

    /**
     * Improvised Weapon in dueling line; controller's actor in duel.
     *
     * @return array{0:_01155,1:Reaction_01155,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        /** @var _01155 $weapon */
        $weapon = $world->placeCard(new _01155(), Game::LOCATION_DUELING_LINE, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Reaction_01155 $reaction */
        $reaction = $weapon->getReactions()[0];
        return [$weapon, $reaction, $actor];
    }

    private function endOfRound(TestWorld $world, int $playerId, int $actorId): EventDuelEndOfRound
    {
        $event = new EventDuelEndOfRound();
        $event->playerId = $playerId;
        $event->actorId = $actorId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: Reaction_01155 extends RiskReaction but triggers from LOCATION_DUELING_LINE
            // (comment in production), not hand — still a RiskReaction for announce/description.
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_01155(), 'RiskReaction');
            },

            'buttons offer Equip and Decline' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['equip', 'decline'], $ids, 'buttons');
            },

            'EndOfRound offers when weapon is in the dueling line during your round' => function () {
                $world = new TestWorld();
                [$weapon, $reaction, $actor] = $this->scene($world);

                $reaction->handleEvent($this->endOfRound($world, 1, $actor->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($weapon->Id, $transitions[0]->sourceId, 'weapon');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer when weapon is not in the dueling line' => function () {
                $world = new TestWorld();
                [$weapon, $reaction, $actor] = $this->scene($world);
                $weapon->Location = Game::LOCATION_HAND;

                $reaction->handleEvent($this->endOfRound($world, 1, $actor->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not in line');
            },

            'does not offer outside a duel' => function () {
                $world = new TestWorld();
                [, $reaction, $actor] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);

                $reaction->handleEvent($this->endOfRound($world, 1, $actor->Id));

                Assert::count(0, $world->theah->queuedEvents, 'not in duel');
            },

            'does not offer on the opponent\'s round' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->endOfRound($world, 2, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'opponent round');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction, $actor] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->endOfRound($world, 1, $actor->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: the equip factory returns EventAttachmentEquipping (pipeline starts with
            // Equipping; Equipped fires later from EventHub).
            'equip queues AttachmentEquipping ignoring costs and marks Used' => function () {
                $world = new TestWorld();
                [$weapon, $reaction, $actor] = $this->scene($world);
                $reaction->handleEvent($this->endOfRound($world, 1, $actor->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'equip');

                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equip, 'equip');
                Assert::same($actor->Id, $equip[0]->characterId, 'actor');
                Assert::same($weapon->Id, $equip[0]->attachmentId, 'weapon');
                Assert::same(0, $equip[0]->cost, 'free');
                Assert::same(0, $equip[0]->discount, 'no discount');
                Assert::false($equip[0]->asAction, 'not as action');
                Assert::same($reaction->Id, $equip[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'decline clears pending actor without equipping or marking Used' => function () {
                $world = new TestWorld();
                [, $reaction, $actor] = $this->scene($world);
                $reaction->handleEvent($this->endOfRound($world, 1, $actor->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::count(0, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'no equip');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
