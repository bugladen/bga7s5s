<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01202;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01202;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterPutIntoApproachDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01202_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01202';
    }

    /**
     * Object of Wonder on Leader; savable ally in play.
     *
     * @return array{0:_01202,1:Reaction_01202,2:GenericLeader,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
        $world->theah->leadersByPlayerId[1] = $leader;
        /** @var _01202 $wonder */
        $wonder = $world->placeCard(new _01202(), Game::LOCATION_PLAYER_HOME, 1);
        $wonder->AttachedToId = $leader->Id;
        $leader->Attachments[] = $wonder->Id;
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_01202 $reaction */
        $reaction = $wonder->getReactions()[0];
        return [$wonder, $reaction, $leader, $ally];
    }

    private function destroy(TestWorld $world, int $characterId): EventCharacterDestroyed
    {
        $event = new EventCharacterDestroyed();
        $event->characterId = $characterId;
        $event->theah = $world->theah;
        return $event;
    }

    private function waitAfterDuel(Reaction_01202 $reaction): bool
    {
        $prop = new \ReflectionProperty(Reaction_01202::class, 'WaitAfterDuel');
        $prop->setAccessible(true);
        return (bool)$prop->getValue($reaction);
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_01202(), 'AttachmentReaction');
            },

            'buttons offer save and pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['saveCharacter', 'pass'], $ids, 'buttons');
            },

            'CharacterDestroyed offers for a non-Mercenary ally' => function () {
                $world = new TestWorld();
                [$wonder, $reaction, , $ally] = $this->scene($world);

                $reaction->handleEvent($this->destroy($world, $ally->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($wonder->Id, $transitions[0]->sourceId, 'wonder');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            'does not offer for a Mercenary' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $merc = $world->placeCharacter(
                    new GenericCharacter('Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $reaction->handleEvent($this->destroy($world, $merc->Id));

                Assert::count(0, $world->theah->queuedEvents, 'Mercenary');
            },

            // WHY (journal 2026-09-19-13): canEnterApproachDeck covers Brutes + Damned.
            'does not offer for a Brute (cannot enter Approach)' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $brute = $world->placeCharacter(
                    new GenericCharacter('Brute', ['Brute']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $reaction->handleEvent($this->destroy($world, $brute->Id));

                Assert::count(0, $world->theah->queuedEvents, 'Brute');
            },

            'does not offer for an opposing character' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->destroy($world, $foe->Id));

                Assert::count(0, $world->theah->queuedEvents, 'opponent');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->destroy($world, $ally->Id));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'does not offer when unattached' => function () {
                $world = new TestWorld();
                [$wonder, $reaction, , $ally] = $this->scene($world);
                $wonder->AttachedToId = 0;

                $reaction->handleEvent($this->destroy($world, $ally->Id));

                Assert::count(0, $world->theah->queuedEvents, 'unattached');
            },

            // WHY (journal 2026-04-08-11): outside a duel, save runs immediately.
            'save outside a duel queues Approach + locker package and marks Used' => function () {
                $world = new TestWorld();
                [$wonder, $reaction, $leader, $ally] = $this->scene($world);
                $reaction->handleEvent($this->destroy($world, $ally->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'saveCharacter');

                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromLocker::class), 'remove locker');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterPutIntoApproachDeck::class), 'approach');
                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($wonder->Id, $unequip[0]->attachmentId, 'wonder');
                Assert::same($leader->Id, $unequip[0]->characterId, 'leader');
                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'wonder to locker');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: setUsed early so a second destroy mid-duel cannot overwrite SavedCharacterId.
            'save during a duel defers effects to DuelEnd and marks Used immediately' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, true);
                $reaction->handleEvent($this->destroy($world, $ally->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'saveCharacter');

                Assert::true($reaction->Used, 'used immediately');
                Assert::true($this->waitAfterDuel($reaction), 'deferred');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterPutIntoApproachDeck::class), 'not yet');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterPutIntoApproachDeck::class), 'at DuelEnd');
                Assert::false($this->waitAfterDuel($reaction), 'cleared');
            },

            'pass leaves the reaction available' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally] = $this->scene($world);
                $reaction->handleEvent($this->destroy($world, $ally->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterPutIntoApproachDeck::class), 'no save');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
