<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04023;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04023_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04023';
    }

    public function tests(): array
    {
        return [
            // WHY: En Garde reaction when Monet herself moves to a city location, and only
            // if the faction deck has a card. Pass before reveal does not set Used. Used
            // is burned when the sink finishes, not at reveal, or the requeue is dropped.
            'offers after she moves to a city, and sinking the reveal spends it' => function () {
                $world = new TestWorld();
                $monet = $world->placeCharacter(new _04023(), Game::LOCATION_PLAYER_HOME, 1);
                $risk = $world->placeCard(new _04027(), 'Deck-1', 1);
                $world->game->topFactionCards = [['id' => $risk->Id]];
                /** @var Reaction_04023 $reaction */
                $reaction = $monet->getReactions()[0];

                $move = new EventCardMoved();
                $move->cardId = $monet->Id;
                $move->fromLocation = Game::LOCATION_PLAYER_HOME;
                $move->toLocation = Game::LOCATION_CITY_DOCKS;
                $move->theah = $world->theah;
                $reaction->handleEvent($move);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'reveal');
                Assert::false($reaction->Used, 'reveal does not spend');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('discard-' . $risk->Id, $buttons, true), 'no attachment, so discard');
                Assert::true(in_array('sinkRest', $buttons, true), 'sink the rest');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'sinkRest');
                Assert::true($reaction->Used, 'finished');
                $sink = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0];
                Assert::same($risk->Id, $sink->cardId, 'sunk card');
                Assert::false($sink->onTop, 'bottom');

                $reaction->Used = false;
                $monet->Engaged = true;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($move);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged');

                $monet->Engaged = false;
                $move->toLocation = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($move);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a city');

                $world->game->topFactionCards = [];
                $move->toLocation = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($move);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'empty deck');
            },

            // WHY: A zero-cost revealed attachment equips immediately, then the reaction
            // finishes if nothing remains. The equip event is EventAttachmentEquipping.
            'a free revealed attachment equips and finishes' => function () {
                $world = new TestWorld();
                $monet = $world->placeCharacter(new _04023(), Game::LOCATION_CITY_DOCKS, 1);
                $bow = $world->placeCard(new _04017(), 'Deck-1', 1);
                $bow->WealthCost = 0;
                $world->game->topFactionCards = [['id' => $bow->Id]];
                /** @var Reaction_04023 $reaction */
                $reaction = $monet->getReactions()[0];

                $reaction->performReaction($world->game, 0, '', 'reveal');
                $reaction->performReaction($world->game, 0, '', 'attach-' . $bow->Id);
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'equip_' . $monet->Id);
                Assert::true($reaction->Used, 'nothing left to sink');
                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class)[0];
                Assert::same($monet->Id, $equip->characterId, 'host');
                Assert::same($bow->Id, $equip->attachmentId, 'attachment');
                Assert::same(0, $equip->cost, 'free');
            },
        ];
    }
}
