<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03cd20;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03cd20_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03cd20';
    }

    /**
     * @return array{0:_03cd20,1:Reaction_03cd20,2:GenericCharacter}
     */
    private function atHome(TestWorld $world): array
    {
        $card = $world->placeCard(new _03cd20(), Game::LOCATION_PLAYER_HOME, 1);
        $character = $world->placeCharacter(new GenericCharacter('Mover'), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_03cd20 $reaction */
        $reaction = $card->getReactions()[0];
        return [$card, $reaction, $character];
    }

    /** @param list<array{text:string,reaction:string}> $buttons */
    private function reactionIds(array $buttons): array
    {
        return array_map(static fn(array $button): string => $button['reaction'], $buttons);
    }

    public function tests(): array
    {
        return [
            // WHY: End of Planning, card in your Home, you have a character in play.
            // Adjacency is measured from Home, which is never empty on the 2-player map.
            'planning end at Home offers the move' => function () {
                $world = new TestWorld();
                [$card, $reaction, $character] = $this->atHome($world);
                $locker = $world->placeCharacter(new GenericCharacter('Locker'), 'Locker-1', 1);

                $end = new EventPhasePlanningEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'offered');
                Assert::same(1, $transition[0]->playerId, 'controller');
                Assert::same($card->Id, $transition[0]->sourceId, 'card');
                Assert::same($reaction->Id, $transition[0]->internalId, 'reaction');

                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('char-' . $character->Id, $ids, true), 'in-play character');
                Assert::false(in_array('char-' . $locker->Id, $ids, true), 'locker is not in play');
                Assert::contains('characters to move', $reaction->getReactionDescription($world->theah), 'character prompt');
            },

            'not at Home, with no characters, or already used, does not offer' => function () {
                $world = new TestWorld();
                [$card, $reaction] = $this->atHome($world);
                $card->Location = Game::LOCATION_CITY_DOCKS;

                $end = new EventPhasePlanningEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                Assert::count(0, $world->theah->queuedEvents, 'still in the city');

                $card->Location = Game::LOCATION_PLAYER_HOME;
                $world->theah->queuedEvents = [];
                foreach ($world->theah->getCharactersInPlayByPlayerId(1) as $character) {
                    $character->Location = 'Locker-1';
                }
                $reaction->handleEvent($end);
                Assert::count(0, $world->theah->queuedEvents, 'no characters');

                $world->placeCharacter(new GenericCharacter('Back'), Game::LOCATION_PLAYER_HOME, 1);
                $reaction->setUsed($world->theah, true);
                $world->theah->queuedEvents = [];
                $reaction->handleEvent($end);
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: Destinations are adjacent to the card (Home), not to the character.
            // A Forum character may be sent to the Docks. Controller 0 in the city counts
            // as "player 0's" character because the lookup does not require isControlled.
            'choosing a character lists city locations adjacent to Home' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->atHome($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'char-' . $character->Id);
                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('loc-' . Game::LOCATION_CITY_DOCKS, $ids, true), 'docks');
                Assert::true(in_array('loc-' . Game::LOCATION_CITY_FORUM, $ids, true), 'forum');
                Assert::true(in_array('loc-' . Game::LOCATION_CITY_BAZAAR, $ids, true), 'bazaar');
                Assert::false(in_array('loc-' . Game::LOCATION_CITY_OLES_INN, $ids, true), 'two-player map');
                Assert::true(in_array('back', $ids, true), 'back');
                Assert::false($reaction->Used, 'not spent yet');

                $world->game->globals->set(Game::PLAYER_COUNT, 4);
                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('loc-' . Game::LOCATION_CITY_OLES_INN, $ids, true), 'four-player map');
                Assert::true(in_array('loc-' . Game::LOCATION_CITY_GOVERNORS_GARDEN, $ids, true), 'garden');
            },

            'a legal location moves that character and discards the card' => function () {
                $world = new TestWorld();
                [$card, $reaction, $character] = $this->atHome($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'char-' . $character->Id);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'loc-' . Game::LOCATION_CITY_DOCKS);

                $move = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $move, 'move');
                Assert::same($character->Id, $move[0]->cardId, 'character');
                Assert::same(Game::LOCATION_CITY_FORUM, $move[0]->fromLocation, 'from forum');
                Assert::same(Game::LOCATION_CITY_DOCKS, $move[0]->toLocation, 'to docks');
                Assert::false($move[0]->engage, 'does not engage');
                Assert::same($card->Id, $move[0]->sourceId, 'source');
                Assert::same($reaction->Id, $move[0]->abilityId, 'ability');

                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discard, 'discard');
                Assert::same($card->Id, $discard[0]->cardId, 'this card');
                Assert::same(Game::LOCATION_PLAYER_HOME, $discard[0]->fromLocation, 'from home');
                Assert::true($discard[0]->asEffect, 'effect');
                Assert::true($reaction->Used, 'spent');
            },

            // WHY: A location that is not adjacent, or a character you do not control,
            // returns before queueing — and the reaction is still marked used.
            'an illegal location or character spends the reaction with no move' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->atHome($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'char-' . $character->Id);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'loc-' . Game::LOCATION_CITY_OLES_INN);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'not adjacent');
                Assert::true($reaction->Used, 'spent');

                $world = new TestWorld();
                [, $reaction] = $this->atHome($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'char-' . $foe->Id);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'loc-' . Game::LOCATION_CITY_DOCKS);
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'not yours');
                Assert::true($reaction->Used, 'spent');
            },

            'decline and back do not move anyone' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->atHome($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                Assert::true($reaction->Used, 'declined');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');

                $world = new TestWorld();
                [, $reaction, $character] = $this->atHome($world);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'char-' . $character->Id);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'back');
                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('char-' . $character->Id, $ids, true), 'characters again');
                Assert::false($reaction->Used, 'not spent');
            },

            'controller 0 treats uncontrolled city characters as eligible' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _03cd20(), Game::LOCATION_PLAYER_HOME, 0);
                $street = $world->placeCharacter(new GenericCharacter('Street'), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Reaction_03cd20 $reaction */
                $reaction = $card->getReactions()[0];

                $end = new EventPhasePlanningEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);

                $ids = $this->reactionIds($reaction->getReactionButtonProperties($world->theah));
                Assert::true(in_array('char-' . $street->Id, $ids, true), 'uncontrolled counts for controller 0');
            },
        ];
    }
}
