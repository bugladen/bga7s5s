<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd05;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd18;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd18;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03cd18_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03cd18';
    }

    /**
     * @return array{0:_03cd18,1:Reaction_03cd18}
     */
    private function recruited(TestWorld $world): array
    {
        $kalla = $world->placeCharacter(new _03cd18(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Reaction_03cd18 $reaction */
        $reaction = $kalla->getReactions()[0];
        return [$kalla, $reaction];
    }

    /** @param list<array{text:string,reaction:string}> $buttons */
    private function reactionIds(array $buttons): array
    {
        return array_map(static fn(array $button): string => $button['reaction'], $buttons);
    }

    public function tests(): array
    {
        return [
            // WHY: Only their own recruit, and only while a search or a destroy target exists.
            'recruiting them with an attachment in deck offers the choice' => function () {
                $world = new TestWorld();
                [$kalla, $reaction] = $this->recruited($world);
                $world->placeCard(new _03cd05(), 'Deck-1', 1);

                $event = new EventCharacterRecruited();
                $event->characterId = $kalla->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'offered');
                Assert::same(1, $transition[0]->playerId, 'controller');
                Assert::same($kalla->Id, $transition[0]->sourceId, 'kalla');
                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('optionA', $ids, true), 'search');
                Assert::false(in_array('optionB', $ids, true), 'nothing to destroy');
                Assert::true(in_array('decline', $ids, true), 'decline');
            },

            'no deck attachment and no opposing attachment does not offer' => function () {
                $world = new TestWorld();
                [$kalla, $reaction] = $this->recruited($world);
                $world->placeCard(new _03cd05(), 'Deck-2', 2);

                $event = new EventCharacterRecruited();
                $event->characterId = $kalla->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'recruiting a different character, or them outside the city, does not offer' => function () {
                $world = new TestWorld();
                [$kalla, $reaction] = $this->recruited($world);
                $world->placeCard(new _03cd05(), 'Deck-1', 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterRecruited();
                $event->characterId = $other->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not them');

                $kalla->Location = Game::LOCATION_PLAYER_HOME;
                $event->characterId = $kalla->Id;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'not in the city');
            },

            // WHY: Buttons are one per attachment name. The click removes that card from
            // the faction deck, adds it to hand, and shuffles. A stale id still spends the reaction.
            'searching reveals one copy and shuffles' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->recruited($world);
                $first = $world->placeCard(new _03cd05(), 'Deck-1', 1);
                $world->placeCard(new _03cd05(), 'Deck-1', 1);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'optionA');
                $buttons = $reaction->getReactionButtonProperties($world->theah);
                $search = array_values(array_filter(
                    $buttons,
                    static fn(array $button): bool => str_starts_with($button['reaction'], 'searchA-')
                ));
                Assert::count(1, $search, 'one button for the shared name');
                Assert::same('searchA-' . $first->Id, $search[0]['reaction'], 'first copy');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'searchA-' . $first->Id);

                $remove = $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class);
                Assert::count(1, $remove, 'removed');
                Assert::same($first->Id, $remove[0]->cardId, 'first copy');
                Assert::same(1, $remove[0]->playerId, 'owner');
                $hand = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::same($first->Id, $hand[0]->cardId, 'to hand');
                Assert::same(['Deck-1'], $world->game->deckShuffles, 'shuffled');
                Assert::true($reaction->Used, 'spent');
            },

            'a stale search id spends the reaction and does not shuffle' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->recruited($world);
                $character = $world->placeCharacter(new GenericCharacter('NotAttachment'), 'Deck-1', 1);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'searchA-' . $character->Id);

                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class), 'no search');
                Assert::count(0, $world->game->deckShuffles, 'no shuffle');
                Assert::true($reaction->Used, 'spent anyway');
            },

            // WHY: The move is queued before the destroy choice. Decline is still on the
            // destroy step, and there is no Back — the move already committed.
            'move then destroy unequips an opposing city attachment' => function () {
                $world = new TestWorld();
                [$kalla, $reaction] = $this->recruited($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_FORUM, 2);
                $bones->AttachedToId = $foe->Id;
                $foe->Attachments[] = $bones->Id;
                $friend = $world->placeCharacter(new GenericCharacter('Friend'), Game::LOCATION_CITY_DOCKS, 1);
                $own = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $own->AttachedToId = $friend->Id;
                $friend->Attachments[] = $own->Id;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'optionB');
                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('moveB-' . Game::LOCATION_CITY_FORUM, $ids, true), 'forum');
                Assert::false(in_array('moveB-' . Game::LOCATION_CITY_DOCKS, $ids, true), 'own attachment is not opposing');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveB-' . Game::LOCATION_CITY_FORUM);
                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move queued');
                Assert::same($kalla->Id, $moves[0]->cardId, 'kalla');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from docks');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to forum');
                Assert::false($moves[0]->engage, 'does not engage');
                Assert::false($reaction->Used, 'not spent until destroy');

                $destroyIds = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::false(in_array('back', $destroyIds, true), 'no back after the move');
                Assert::true(in_array('decline', $destroyIds, true), 'decline remains');
                // The move has not been applied, so opposing is still queried at Docks.
                Assert::false(in_array('destroyB-' . $bones->Id, $destroyIds, true), 'forum attachment is not here yet');

                $kalla->Location = Game::LOCATION_CITY_FORUM;
                $destroyIds = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('destroyB-' . $bones->Id, $destroyIds, true), 'now at the forum');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'destroyB-' . $bones->Id);
                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($bones->Id, $unequip[0]->attachmentId, 'bones');
                Assert::same($foe->Id, $unequip[0]->characterId, 'foe');
                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discard, 'city attachment');
                Assert::same($bones->Id, $discard[0]->cardId, 'bones');
                Assert::true($discard[0]->asEffect, 'effect');
                Assert::true($reaction->Used, 'spent');
            },

            'destroying your own attachment or one that left is a no-op that still spends' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->recruited($world);
                $friend = $world->placeCharacter(new GenericCharacter('Friend'), Game::LOCATION_CITY_DOCKS, 1);
                $own = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $own->AttachedToId = $friend->Id;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'destroyB-' . $own->Id);
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'not opposing');
                Assert::true($reaction->Used, 'spent');
            },

            'decline spends the reaction with no effect' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->recruited($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'back from the search returns to the choice' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->recruited($world);
                $world->placeCard(new _03cd05(), 'Deck-1', 1);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'optionA');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'back');

                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('optionA', $ids, true), 'choice again');
                Assert::false($reaction->Used, 'not spent');
            },
        ];
    }
}
