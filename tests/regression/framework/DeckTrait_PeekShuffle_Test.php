<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\DeckTraitHost;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;

/**
 * DeckTrait peeks + discard reshuffles for faction and city decks.
 */
class DeckTrait_PeekShuffle_Test extends TestCase
{
    public function name(): string
    {
        return 'DeckTrait: peek tops/bottoms + discard reshuffles';
    }

    private function host(): DeckTraitHost
    {
        return new DeckTraitHost();
    }

    public function tests(): array
    {
        return [
            'getCardsOnTopOfPlayerFactionDeck returns top-first without reshuffling when deep enough' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $c1 = $host->place(new GenericRisk('1'), $deck, 1);
                $c2 = $host->place(new GenericRisk('2'), $deck, 1);
                $c3 = $host->place(new GenericRisk('3'), $deck, 1);

                $tops = $host->getCardsOnTopOfPlayerFactionDeck(1, 2);
                Assert::count(2, $tops, 'two');
                Assert::same($c3->Id, $tops[0]['id'], 'topmost first');
                Assert::same($c2->Id, $tops[1]['id'], 'second');
                Assert::count(0, $host->notifsOfType('playerDiscardShuffled'), 'no reshuffle');
                unset($c1);
            },

            'short faction deck reshuffles discard and restacks already-revealed tops' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $discard = $host->getPlayerDiscardDeckName(1);

                $revealed = $host->place(new GenericRisk('Already Revealed'), $deck, 1);
                $fromDiscard = $host->place(new GenericRisk('From Discard'), $discard, 1);

                $tops = $host->getCardsOnTopOfPlayerFactionDeck(1, 2);

                Assert::count(1, $host->notifsOfType('playerDiscardShuffled'), 'reshuffled');
                Assert::same($revealed->Id, $tops[0]['id'], 'prior top restacked on top');
                Assert::same($fromDiscard->Id, $tops[1]['id'], 'reshuffled card under');
            },

            'getCardsOnBottomOfPlayerFactionDeck returns lowest location_arg first' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $bottom = $host->place(new GenericRisk('Bottom'), $deck, 1);
                $mid = $host->place(new GenericRisk('Mid'), $deck, 1);
                $top = $host->place(new GenericRisk('Top'), $deck, 1);

                $bottoms = $host->getCardsOnBottomOfPlayerFactionDeck(1, 2);
                Assert::count(2, $bottoms, 'two');
                Assert::same($bottom->Id, $bottoms[0]['id'], 'true bottom');
                Assert::same($mid->Id, $bottoms[1]['id'], 'next up');
                unset($top);
            },

            'getCardsOnBottomOfPlayerFactionDeck triggers reshuffle when short' => function () {
                $host = $this->host();
                $discard = $host->getPlayerDiscardDeckName(1);
                $host->place(new GenericRisk('A'), $discard, 1);
                $host->place(new GenericRisk('B'), $discard, 1);

                $bottoms = $host->getCardsOnBottomOfPlayerFactionDeck(1, 2);
                Assert::count(2, $bottoms, 'two after reshuffle');
                Assert::count(1, $host->notifsOfType('playerDiscardShuffled'), 'reshuffled via top helper');
            },

            'shufflePlayerDiscardIntoPlayerFactionDeck clears Used and batches counts' => function () {
                $host = $this->host();
                $discard = $host->getPlayerDiscardDeckName(1);
                $risk = new GenericRisk('Used Risk');
                $action = new GenericRiskAction();
                $action->Used = true;
                $risk->addAction($action, $host, false);
                $card = $host->place($risk, $discard, 1);

                $host->notify->messages = [];
                $host->shufflePlayerDiscardIntoPlayerFactionDeck(1);

                Assert::false($action->Used, 'cleared');
                Assert::same($host->getPlayerFactionDeckName(1), $card->Location, 'Location synced');
                Assert::count(1, $host->notifsOfType('playerDiscardShuffled'), 'announce');
                Assert::count(1, $host->notifsOfType('factionDeckCount'), 'batched count');
                Assert::same(1, $host->notifsOfType('factionDeckCount')[0]['args']['deckCount'], 'size');
                Assert::true(in_array($host->getPlayerFactionDeckName(1), $host->deckShuffles, true), 'shuffled');
            },

            'getCardsOnTopOfCityDeck returns tops when deep enough' => function () {
                $host = $this->host();
                $a = $host->place(new GenericRisk('City A'), Game::LOCATION_CITY_DECK, 0);
                $b = $host->place(new GenericRisk('City B'), Game::LOCATION_CITY_DECK, 0);

                $tops = $host->getCardsOnTopOfCityDeck(1);
                Assert::count(1, $tops, 'one');
                Assert::same($b->Id, $tops[0]['id'], 'top');
                unset($a);
            },

            'short city deck reshuffles city discard and restacks revealed tops' => function () {
                $host = $this->host();
                $revealed = $host->place(new GenericRisk('City Revealed'), Game::LOCATION_CITY_DECK, 0);
                $fromDiscard = $host->place(new GenericRisk('City Discarded'), Game::LOCATION_CITY_DISCARD, 0);

                $tops = $host->getCardsOnTopOfCityDeck(2);

                Assert::count(1, $host->notifsOfType('cityDiscardShuffled'), 'city reshuffle');
                Assert::same($revealed->Id, $tops[0]['id'], 'prior top restacked');
                Assert::same($fromDiscard->Id, $tops[1]['id'], 'from discard under');
                Assert::same(Game::LOCATION_CITY_DECK, $fromDiscard->Location, 'Location synced via moveCard');
            },

            'shuffleCityDiscardIntoCityDeck clears Used flags' => function () {
                $host = $this->host();
                $risk = new GenericRisk('City Used');
                $action = new GenericRiskAction();
                $action->Used = true;
                $risk->addAction($action, $host, false);
                $card = $host->place($risk, Game::LOCATION_CITY_DISCARD, 0);

                $host->shuffleCityDiscardIntoCityDeck();

                Assert::false($action->Used, 'cleared');
                Assert::same(Game::LOCATION_CITY_DECK, $card->Location, 'in city deck');
                Assert::count(1, $host->notifsOfType('cityDiscardShuffled'), 'announce');
            },
        ];
    }
}
