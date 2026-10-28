<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\DeckTraitHost;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;

/**
 * DeckTrait::playerDrawCard — reshuffle-from-discard, factionDeckCount batch nesting,
 * mid-gamble peek skip (Desideria/Unravel), Ivy-style reveal shrink when nothing under peek.
 */
class DeckTrait_Draw_Test extends TestCase
{
    public function name(): string
    {
        return 'DeckTrait: playerDrawCard';
    }

    private function host(): DeckTraitHost
    {
        return new DeckTraitHost();
    }

    public function tests(): array
    {
        return [
            'draw takes the top of the faction deck into hand' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $bottom = $host->place(new GenericRisk('Bottom'), $deck, 1);
                $top = $host->place(new GenericRisk('Top'), $deck, 1);

                $host->notify->messages = [];
                $drawn = $host->playerDrawCard(1);

                Assert::same($top->Id, $drawn->Id, 'drew top');
                Assert::same(Game::LOCATION_HAND, $drawn->Location, 'Location Hand');
                Assert::same(Game::LOCATION_HAND, $host->cards->getCard($drawn->Id)['location'], 'deck row Hand');
                Assert::same($deck, $host->cards->getCard($bottom->Id)['location'], 'bottom stays');
                $notifs = $host->notifsOfType('factionDeckCount');
                Assert::count(1, $notifs, 'one count after draw');
                Assert::same(1, $notifs[0]['args']['deckCount'], 'one left in deck');
            },

            'empty faction deck reshuffles discard before drawing' => function () {
                $host = $this->host();
                $discard = $host->getPlayerDiscardDeckName(1);
                $risk = new GenericRisk('Recycled');
                $action = new GenericRiskAction();
                $action->Used = true;
                $risk->addAction($action, $host, false);
                $card = $host->place($risk, $discard, 1);

                $host->notify->messages = [];
                $drawn = $host->playerDrawCard(1);

                Assert::same($card->Id, $drawn->Id, 'drew recycled');
                Assert::false($action->Used, 'Used cleared on reshuffle');
                Assert::count(1, $host->notifsOfType('playerDiscardShuffled'), 'shuffle announce');
                // WHY: beginFactionDeckCountBatch nests shuffle's batch inside draw's — one final count.
                Assert::count(1, $host->notifsOfType('factionDeckCount'), 'batched count');
                Assert::same(0, $host->notifsOfType('factionDeckCount')[0]['args']['deckCount'], 'deck empty after pick');
            },

            'mid-gamble draw skips revealed tops for the duel actor' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $under = $host->place(new GenericRisk('Under Peek'), $deck, 1);
                $peek2 = $host->place(new GenericRisk('Peek 2'), $deck, 1);
                $peek1 = $host->place(new GenericRisk('Peek 1'), $deck, 1);

                $actor = $host->place(new GenericCharacter('Actor'), Game::LOCATION_CITY_FORUM, 1);
                $host->theah->duelActor = $actor;

                $host->globals->set(Game::IN_DUEL, true);
                $host->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $host->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);
                $host->globals->set(Game::DUEL_GAMBLED, false);

                $drawn = $host->playerDrawCard(1);

                Assert::same($under->Id, $drawn->Id, 'card under the peek');
                Assert::same(2, (int) $host->globals->get(Game::GAMBLE_REVEAL_COUNT), 'count unchanged');
                Assert::same($deck, $host->cards->getCard($peek1->Id)['location'], 'peek1 still deck');
                Assert::same($deck, $host->cards->getCard($peek2->Id)['location'], 'peek2 still deck');
            },

            'mid-gamble draw does not skip when drawer is not the duel actor controller' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(2);
                $bottom = $host->place(new GenericRisk('P2 Bottom'), $deck, 2);
                $top = $host->place(new GenericRisk('P2 Top'), $deck, 2);

                $actor = $host->place(new GenericCharacter('P1 Actor'), Game::LOCATION_CITY_FORUM, 1);
                $host->theah->duelActor = $actor;

                $host->globals->set(Game::IN_DUEL, true);
                $host->globals->set(Game::GAMBLE_REVEAL_COUNT, 2);
                $host->globals->set(Game::DUEL_GAMBLED, false);

                $drawn = $host->playerDrawCard(2);
                Assert::same($top->Id, $drawn->Id, 'normal top pick for non-actor');
                unset($bottom);
            },

            'mid-gamble draw does not skip after DUEL_GAMBLED is set' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $under = $host->place(new GenericRisk('Would Skip'), $deck, 1);
                $top = $host->place(new GenericRisk('Normal Top'), $deck, 1);

                $actor = $host->place(new GenericCharacter('Actor'), Game::LOCATION_CITY_FORUM, 1);
                $host->theah->duelActor = $actor;

                $host->globals->set(Game::IN_DUEL, true);
                $host->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $host->globals->set(Game::DUEL_GAMBLED, true);

                $drawn = $host->playerDrawCard(1);
                Assert::same($top->Id, $drawn->Id, 'post-choose draws top normally');
                unset($under);
            },

            'mid-gamble draw does not skip bottom reveals (Devil Jonah)' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $bottom = $host->place(new GenericRisk('Bottom'), $deck, 1);
                $top = $host->place(new GenericRisk('Top'), $deck, 1);

                $actor = $host->place(new GenericCharacter('Actor'), Game::LOCATION_CITY_FORUM, 1);
                $host->theah->duelActor = $actor;

                $host->globals->set(Game::IN_DUEL, true);
                $host->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $host->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);
                $host->globals->set(Game::DUEL_GAMBLED, false);

                $drawn = $host->playerDrawCard(1);
                Assert::same($top->Id, $drawn->Id, 'from-bottom reveal is not on the draw edge');
                unset($bottom);
            },

            'mid-gamble with nothing under peek takes a revealed card and shrinks count' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $only = $host->place(new GenericRisk('Only Peek'), $deck, 1);

                $actor = $host->place(new GenericCharacter('Actor'), Game::LOCATION_CITY_FORUM, 1);
                $host->theah->duelActor = $actor;

                $host->globals->set(Game::IN_DUEL, true);
                $host->globals->set(Game::GAMBLE_REVEAL_COUNT, 1);
                $host->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, false);
                $host->globals->set(Game::DUEL_GAMBLED, false);

                $drawn = $host->playerDrawCard(1);

                Assert::same($only->Id, $drawn->Id, 'took the reveal');
                Assert::same(0, (int) $host->globals->get(Game::GAMBLE_REVEAL_COUNT), 'Ivy-style shrink');
            },
        ];
    }
}
