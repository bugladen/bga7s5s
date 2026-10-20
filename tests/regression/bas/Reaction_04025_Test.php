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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04023;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04025;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04025_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04025';
    }

    public function tests(): array
    {
        return [
            // WHY: Merchant reaction at end of Planning, scheme at Home. Look count is
            // 3 plus each Merchant. Two or fewer looked cards are drawn immediately.
            // Three or more open a pick of exactly two; the rest sink to the bottom.
            // Pass does not set Used.
            'looks three plus merchants, drawing two and sinking the rest' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04025(), Game::LOCATION_PLAYER_HOME, 1);
                $world->placeCharacter(new _04023(), Game::LOCATION_CITY_DOCKS, 1);
                $cards = [];
                for ($i = 0; $i < 4; $i++) {
                    $cards[] = $world->placeCard(new _04027(), 'Deck-1', 1);
                }
                $world->game->topFactionCards = array_map(fn($card) => ['id' => $card->Id], $cards);
                /** @var Reaction_04025 $reaction */
                $reaction = $scheme->getReactions()[0];

                $end = new EventPhasePlanningEnd();
                $end->theah = $world->theah;
                $reaction->handleEvent($end);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, '', 'look');
                Assert::true($reaction->Used, 'look spends');
                Assert::same('04025', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pick two');
                $args = $reaction->getArgsFromReaction($world->game, States::PLANNING_PHASE_END_04025, '');
                Assert::same(2, $args['cardsToDraw'], 'draw two');
                Assert::count(4, $args['cards'], '3 + 1 merchant');

                $threw = false;
                try {
                    $reaction->actFromReactionWithIds($world->game, States::PLANNING_PHASE_END_04025, '', [$cards[0]->Id]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must draw two');

                $reaction->actFromReactionWithIds($world->game, States::PLANNING_PHASE_END_04025, '', [$cards[0]->Id, $cards[1]->Id]);
                $drawn = array_map(fn($event) => $event->cardId, $world->theah->queuedOfType(EventCardAddedToHand::class));
                Assert::same([$cards[0]->Id, $cards[1]->Id], $drawn, 'drawn');
                Assert::count(2, $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class), 'removed from deck');
                $sunk = array_map(fn($event) => $event->cardId, $world->theah->queuedOfType(EventCardAddedToFactionDeck::class));
                Assert::same([$cards[2]->Id, $cards[3]->Id], $sunk, 'sunk');
                Assert::false($world->theah->queuedOfType(EventCardAddedToFactionDeck::class)[0]->onTop, 'bottom');

                $short = new TestWorld();
                $scheme2 = $short->placeCard(new _04025(), Game::LOCATION_PLAYER_HOME, 1);
                $short->placeCharacter(new GenericCharacter('Shop', ['Merchant']), Game::LOCATION_PLAYER_HOME, 1);
                $only = $short->placeCard(new _04027(), 'Deck-1', 1);
                $short->game->topFactionCards = [['id' => $only->Id]];
                /** @var Reaction_04025 $reaction2 */
                $reaction2 = $scheme2->getReactions()[0];
                $reaction2->performReaction($short->game, 0, '', 'look');
                Assert::count(0, $short->theah->queuedOfType(EventTransition::class), 'one card draws itself');
                Assert::same($only->Id, $short->theah->queuedOfType(EventCardAddedToHand::class)[0]->cardId, 'auto draw');

                $scheme2->Location = Game::LOCATION_HAND;
                $reaction2->Used = false;
                $short->theah->takeQueuedEvents();
                $end->theah = $short->theah;
                $reaction2->handleEvent($end);
                Assert::count(0, $short->theah->queuedOfType(EventTransition::class), 'not at home');
            },
        ];
    }
}
