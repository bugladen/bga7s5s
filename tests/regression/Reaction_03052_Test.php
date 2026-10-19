<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03052_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03052';
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03052(), 'reaction');
            },

            // WHY: Unlabelled dusk "you may" is continuous — look does not setUsed.
            // Sink goes to the bottom of the City Deck. One leftover is replaced on top;
            // two or more open the reorder. An empty deck, locker, or controller 0 offers nothing.
            'looks at the city deck, sinks one, and reorders the rest' => function () {
                $world = new TestWorld();
                $yev = $world->placeCharacter(new _03052(), Game::LOCATION_CITY_DOCKS, 1);
                $reaction = $yev->getReactions()[0];
                $dusk = new EventDuskPhaseBegin();
                $dusk->theah = $world->theah;

                $reaction->handleEvent($dusk);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'empty deck');

                $a = $world->placeCard(new _03056(), Game::LOCATION_CITY_DECK, 0);
                $b = $world->placeCard(new _03056(), Game::LOCATION_CITY_DECK, 0);
                $c = $world->placeCard(new _03056(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $a->Id], ['id' => $b->Id], ['id' => $c->Id]];
                $reaction->handleEvent($dusk);
                Assert::same($yev->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'offered');

                $yev->ControllerId = 0;
                $world->theah->queuedEvents = [];
                $reaction->handleEvent($dusk);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'uncontrolled');
                $yev->ControllerId = 1;

                $world->game->discardOrLockerByCardId[$yev->Id] = true;
                $reaction->handleEvent($dusk);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'locker');
                $world->game->discardOrLockerByCardId[$yev->Id] = false;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'look');
                Assert::false($reaction->Used, 'continuous');
                $stored = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::count(3, $stored, 'three cards');
                Assert::same('03052', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'sink state');
                Assert::same(['done'], array_slice($world->game->gamestate->transitions, -1), 'done');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');

                $threw = false;
                try {
                    $reaction->actFromReactionWithId($world->game, States::DUSK_PHASE_BEGIN_03052, 'x', 999);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not looked at');

                $reaction->actFromReactionWithId($world->game, States::DUSK_PHASE_BEGIN_03052, 'x', $a->Id);
                $sink = $world->theah->queuedOfType(EventCardAddedToCityDeck::class)[0];
                Assert::same($a->Id, $sink->cardId, 'sunk');
                Assert::false($sink->onTop, 'bottom');
                Assert::same(['reorder'], array_slice($world->game->gamestate->transitions, -1), 'reorder');

                $threw = false;
                try {
                    $reaction->actFromReactionWithIds($world->game, States::DUSK_PHASE_BEGIN_03052_2, 'x', []);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must order all');

                $reaction->actFromReactionWithIds($world->game, States::DUSK_PHASE_BEGIN_03052_2, 'x', [$b->Id, $c->Id]);
                Assert::same($b->Id, $world->game->deckInserts[0]['id'], 'first');
                Assert::same($c->Id, $world->game->deckInserts[1]['id'], 'second');
                Assert::true($world->game->deckInserts[1]['onTop'], 'on top');
                Assert::same(Game::LOCATION_CITY_DECK, $world->game->deckInserts[1]['deck'], 'city deck');
            },

            'a single leftover card is put back without a reorder' => function () {
                $world = new TestWorld();
                $yev = $world->placeCharacter(new _03052(), Game::LOCATION_CITY_DOCKS, 1);
                $reaction = $yev->getReactions()[0];
                $only = $world->placeCard(new _03056(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $only->Id]];
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([$only->getPropertyArray($world->game)]));
                $reaction->actFromReactionWithId($world->game, States::DUSK_PHASE_BEGIN_03052, 'x', $only->Id);
                Assert::same(['done'], array_slice($world->game->gamestate->transitions, -1), 'done');
                Assert::count(0, $world->game->deckInserts, 'nothing left to replace');
            },
        ];
    }
}
